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

    public function totalRetail()
    {
        $sets = \App\Models\CatalogItem::join(
            'sets', 'catalog_items.id', '=', 'sets.catalog_item_id'
            )->get();
        $sets_used = $sets->sum('retail_price');

        $bulk_brick = \App\Models\BulkBrick::all();
        $bulk_used = $bulk_brick->sum('value');

        $bricklink = \App\Models\BricklinkOrder::all();
        $bricklink_used = $bricklink->sum('total_cost');

        dd($sets_used + $bulk_used + $bricklink_used);
    }

    public function test()
    {

        // $sets = \App\Models\CatalogItem::join('sets', 'catalog_items.id', '=', 'sets.catalog_item_id')
        //     ->whereBetween('purchase_date', [Carbon::now()->subDays(60), Carbon::now()->subDays(30)])
        //     ->sum('current_value_new');
        // dd($sets);
        // $sets = Set::whereHas('catalogItem', function($query) {
        //     $query->where('set_number', '71024');
        // })->whereNull('purchase_date')->get();

        // foreach ($sets as $set) {
        //     // $set->purchase_date = Carbon::now()->subMonths(5);
        //     // $set->save();
        // }
    }

    public function blmini()
    {
        $client = new BLClient();
        $client->setAuth([
        	'consumer_key' => getenv('MURSTEN_STOCK_CONSUMER_KEY'),
        	'consumer_secret' => getenv('MURSTEN_STOCK_CONSUMER_SECRET'),
        	'token' => getenv('MURSTEN_STOCK_TOKEN'),
        	'token_secret' => getenv('MURSTEN_STOCK_TOKEN_SECRET'),
        ]);

        $items = new BLPrice($client);

        $subtheme = [
            'DFB Series' => 'coldfb',
            'Disney Series 2' => 'coldis2',
            'Team GB' => 'coltgb',
            'The LEGO Batman Movie' => 'coltlbm',
            'The LEGO Batman Movie series 2' => 'coltlbm2',
            'The LEGO Movie' => 'coltlm',
            'The LEGO Movie 2: The Second Part' => 'coltlm2',
            'The LEGO Ninjago Movie' => 'coltlnm',
            'The Simpsons' => 'colsim',
            'The Simpsons series 2' => 'colsim2',
            'Wizarding World' => 'colhp',
            'Series 01' => 'col01',
            'Series 02' => 'col02',
            'Series 03' => 'col03',
            'Series 04' => 'col04',
            'Series 05' => 'col05',
            'Series 06' => 'col06',
            'Series 07' => 'col07',
            'Series 08' => 'col08',
            'Series 09' => 'col09',
            'Series 10' => 'col10',
            'Series 11' => 'col11',
            'Series 12' => 'col12',
            'Series 13' => 'col13',
            'Series 14' => 'col14',
            'Series 15' => 'col15',
            'Series 16' => 'col16',
            'Series 17' => 'col17',
            'Series 18' => 'col18',
            'Blind bags series 1' => 'coluni1',
        ];

        $subtheme_flipped = array_flip($subtheme);

        $sets = CatalogItem::where([
            ['theme', 'Collectable Minifigures'],
            ['bricklink_id', ''],
        ])->skip(0)->take(50)->get();

        foreach ($sets as $set) {
            $number = array_search($set->sub_theme, $subtheme_flipped);

            $number = $number.'-'.$set->set_number_variant;

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

    public function bricklink()
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

    public function getMinifigs()
    {
        $set_search = new SetSearch();

        $set_response = $set_search->getSets(
            [
                'theme' => 'Collectable Minifigures',
                'owned' => 1,
                'userHash' => '0S4BR8g5yW',
                'pageSize' => 20,
                'pageNumber' => 25
            ]
        );

        foreach($set_response as $item) {
            // get image details
            if ('true' === $item->images['image']) {
                $name = $item->images['imageFilename'];

                $full_image = file_get_contents($item->images['imageURL']);
                Storage::put('public/set-images/full-'.$name.'.jpg', $full_image);
                $full_image_path = 'public/set-images/full-'.$name.'.jpg';

                $thumbnail_image = file_get_contents($item->images['thumbnailURL']);
                Storage::put('public/set-images/thumb-'.$name.'.jpg', $thumbnail_image);
                $thumbnail_image_path = 'public/set-images/thumb-'.$name.'.jpg';
            } else {
                $full_image_path = '';
                $thumbnail_image_path = '';
            }

            $set_info = CatalogItem::firstOrCreate([
               'brickset_id' => (int) $item->itemNumbers['setID'],
               'set_number' => $item->itemNumbers['number'],
               'set_number_variant' => (int) $item->itemNumbers['numberVariant'],
               'name' => $item->name,
               'piece_count' => (int) $item->pieces,
               'minifig_count' => empty($item->minifigs) ? 0 : $item->minifigs,
               'retail_price' => empty($item->prices['USRetailPrice']) ? '3.99' : $item->prices['USRetailPrice'],
               'year' => $item->year,
               'theme' => $item->themeDetails['theme'],
               'sub_theme' => $item->themeDetails['subtheme'],
               'theme_group' => $item->themeDetails['themeGroup'],
               'image_path' => $full_image_path,
               'thumbnail_path' => $thumbnail_image_path,
               'brickset_url' => $item->bricksetURL,
            ]);

            $x = 1;
            while($x <= $item->userCollection['quantityOwned']) {
                // save rows to SetList
                $set_info->sets()->create([
                    'current_condition' => 'Assembled'
                ]);

                $x++;
            }
        }
        // return $set_search_response;
    }

    public function summary()
    {
        // $sets = CatalogItem::where('theme', 'The Hobbit')->withCount('setList')->get();
        $sets = CatalogItem::withCount('sets')->get();
        // $sets = CatalogItem::where('set_number', 3461)->get();

        // $sets = SetList::where('set_info_id', 1130)->get();
        // $totalPrice = $sets->reduce(function($carry, $current){
        //     return round($carry + $current->setList->sum('CatalogItem.retail_price'), 2);
        // },0);

        $price_totals = [
            'retail' => $sets->sum('total_retail_price'),
            'used' => $sets->sum('total_used_value'),
            'new' => $sets->sum('total_new_value'),
            'pieces' => $sets->sum('total_set_pieces'),
        ];

        return response($price_totals);
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

    public function load()
    {
        // set up the MurstenTrack class
        $sets = new Set();

        // read the CSV set list
        $csv = Reader::createFromPath(public_path('storage/set-list.csv'), 'r')
            ->setHeaderOffset(0);

        // limit the data returned to query at one time
        $data = (new Statement())->offset(0)->limit(24)->process($csv);

        // loop through each set
        foreach( $data as $row ) {
           // query Brickset for the set
            $set_response = $sets->getSets(['setNumber' => $row['SetNumber'].'-1']);

            // ensure a record is returned
            if ($set_response instanceof \NateJacobs\MurstenTrack\Exceptions\ResponseException) {
                // do nothing
            } else {
                // save images
                if ('true' === $set_response[0]->images['image']) {
                    $name = $set_response[0]->images['imageFilename'];

                    $full_image = file_get_contents($set_response[0]->images['imageURL']);
                    Storage::put('public/set-images/full-'.$name.'.jpg', $full_image);
                    $full_image_path = 'public/set-images/full-'.$name.'.jpg';

                    $thumbnail_image = file_get_contents($set_response[0]->images['thumbnailURL']);
                    Storage::put('public/set-images/thumb-'.$name.'.jpg', $thumbnail_image);
                    $thumbnail_image_path = 'public/set-images/thumb-'.$name.'.jpg';
                } else {
                    $full_image_path = '';
                    $thumbnail_image_path = '';
                }

                // save details to CatalogItem
                $set_info = CatalogItem::firstOrCreate([
                   'brickset_id' => (int) $set_response[0]->itemNumbers['setID'],
                   'set_number' => $set_response[0]->itemNumbers['number'],
                   'set_number_variant' => (int) $set_response[0]->itemNumbers['numberVariant'],
                   'name' => $set_response[0]->name,
                   'piece_count' => (int) $set_response[0]->pieces,
                   'minifig_count' => empty($set_response[0]->minifigs) ? 0 : $set_response[0]->minifigs,
                   'retail_price' => empty($set_response[0]->prices['USRetailPrice']) ? $row['Price'] : $set_response[0]->prices['USRetailPrice'],
                   'year' => $set_response[0]->year,
                   'theme' => $set_response[0]->themeDetails['theme'],
                   'sub_theme' => $set_response[0]->themeDetails['subtheme'],
                   'theme_group' => $set_response[0]->themeDetails['themeGroup'],
                   'image_path' => $full_image_path,
                   'thumbnail_path' => $thumbnail_image_path,
                   'brickset_url' => $set_response[0]->bricksetURL,
                ]);

                // determine quatity of set
                $x = 1;
                while($x <= $row['Quantity']) {
                    if (!empty($row['MISB'])) {
                        $condition = 'MISB';
                    } else {
                        $condition = 'Parted Out';
                    }

                    // save rows to SetList
                    $set_info->sets()->create([
                        'current_condition' => $condition
                    ]);

                    $x++;
                }
            }
        }
    }
}
