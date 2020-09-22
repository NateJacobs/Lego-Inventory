<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;

class CatalogController extends Controller
{
    public function getDetails(Request $request, $setNumber, $setVariant = 1)
    {
        $whereArray = [
            ['set_number', '=', $setNumber],
            ['set_number_variant', '=', $setVariant],
        ];

        if ('true' !== $request->input('details')) {
            $catalogItem = CatalogItem::where($whereArray)->withCount('sets')->firstOrFail();
        } else {
            $catalogItem = CatalogItem::with(['Sets', 'Sets.StorageLocation', 'Sets.AcquiredLocation'])
            ->withCount('sets')
            ->where($whereArray)->firstOrFail();
        }

        return $catalogItem;
    }
}
