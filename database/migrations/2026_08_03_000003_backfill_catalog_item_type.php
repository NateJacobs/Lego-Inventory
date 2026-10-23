<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * type was missing from CatalogItem::$fillable while the create form asked
     * for it, so mass assignment dropped every value the form collected and the
     * column was left null. BrickLink builds its URL from the item type, so
     * those rows asked for /items///price and came back as a malformed URI —
     * unpriceable both when created and from the panel's refresh action.
     *
     * Fill them in with the value the form offered by default. Almost
     * everything catalogued here is a set, but a book or gear entry created
     * while this was broken would have lost its real type with no way to
     * recover it, so those have to be corrected by hand.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('catalog_items', 'type')) {
            return;
        }

        DB::table('catalog_items')
            ->where(fn ($query) => $query->whereNull('type')->orWhere('type', ''))
            ->update(['type' => 'set']);
    }

    /**
     * Not reversible: which rows were untyped is exactly what this forgets.
     */
    public function down(): void
    {
        //
    }
};
