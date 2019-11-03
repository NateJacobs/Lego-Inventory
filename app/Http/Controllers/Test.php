<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use League\Csv\Reader;
use League\Csv\Statement;
use NateJacobs\MurstenTrack\Resources\Set as SetSearch;
use NateJacobs\MurstenStock\Client as BLClient;
use NateJacobs\MurstenStock\Resources\Price as BLPrice;

class Test extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $sets = CatalogItem::where('theme', 'The Hobbit')
            ->orderBy('set_number')
            ->withCount('sets')
            ->get();

        return response()->json($sets);

    }

    public function bricklink()
    {
        $client = new BLClient();

        $client->setAuth([
        	'consumer_key' => getenv('MURSTEN_STOCK_CONSUMER_KEY'),
        	'consumer_secret' => getenv('MURSTEN_STOCK_CONSUMER_SECRET'),
        	'token' => getenv('MURSTEN_STOCK_TOKEN'),
        	'token_secret' => getenv('MURSTEN_STOCK_TOKEN_SECRET'),
        ]);

        $items = new BLPrice($client);

        $price_response_new = $items->getPrice(
            '75827-1',
            'set',
            [
                'guide_type' => 'stock',
                'new_or_used' => 'N',
                'country_code' => 'US',
            ]
        );

        if ( $price_response_new instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
            return response()->json(['blink' => 'There was an error']);
        } else {
            return response()->json($price_response_new);
        }
    }

    public function bricklinkSample()
    {
        $client = new BLClient();
        $client->setAuth([
        	'consumer_key' => getenv('MURSTEN_STOCK_CONSUMER_KEY'),
        	'consumer_secret' => getenv('MURSTEN_STOCK_CONSUMER_SECRET'),
        	'token' => getenv('MURSTEN_STOCK_TOKEN'),
        	'token_secret' => getenv('MURSTEN_STOCK_TOKEN_SECRET'),
        ]);

        $sets = CatalogItem::whereNull('current_value_new')->get();
        $items = new BLPrice($client);

        foreach($sets as $set) {
            $number = $set->set_number .'-'. $set->set_number_variant;

            $price_response_new = $items->getPrice(
                $number,
                $set->type,
                [
                    'guide_type' => 'stock',
                    'new_or_used' => 'N',
                    'country_code' => 'US',
                ]
            );

            if ( $price_response_new instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
                // it is an error
                // do nothing
            } else {
                $price_response_used = $items->getPrice(
                    $number,
                    $set->type,
                    [
                        'guide_type' => 'stock',
                        'new_or_used' => 'U',
                        'country_code' => 'US',
                    ]
                );

                $new = trim(substr($price_response_new[0]->aggregatePrices['averagePrice'], 3));
                $used = trim(substr($price_response_used[0]->aggregatePrices['averagePrice'], 3));

                if ( '0.00' == $new && '0.00' == $used ) {
                    $new = $set->retail_price;
                    $used = $set->retail_price;
                } elseif ( '0.00' == $new ) {
                    $new = $used;
                } elseif ( '0.00' == $used ) {
                    $used = $new;
                }

                $set->bricklink_id = $number;
                $set->current_value_new = $new;
                $set->current_value_used = $used;
                $set->save();
            }
        }
    }

    public function loadBl()
    {
        $csv = Reader::createFromPath(public_path('storage/collection-log.csv'), 'r')
            ->setHeaderOffset(0);

        // limit the data returned to query at one time
        $data = (new Statement())->process($csv);

        foreach( $data as $row ) {
            $date = Carbon::createFromDate($row['date']);
            $used_value = str_replace(',', '', ltrim($row['used_value'], '$'));
            $retail_value = str_replace(',', '', ltrim($row['retail_value'], '$'));
            \App\Models\CollectionLog::firstOrCreate([
                'date' => $date->toDateString(),
                'piece_count' => $row['piece_count'],
                'total_sets' => empty($row['total_sets']) ? 0 : $row['total_sets'],
                'used_value' => empty($used_value) ? '0.00' : $used_value,
                'retail_value' => empty($retail_value) ? '0.00' : $retail_value,
                'new_value' => '0.00',
            ]);
        }
    }
}
