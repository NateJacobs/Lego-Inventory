<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The relational theme_id / subtheme_id columns replaced the free-text
     * `theme` / `sub_theme` columns, and CatalogItemObserver no longer writes
     * the strings. They were still declared NOT NULL with no default, so under
     * MySQL strict mode inserting a catalog item failed with
     * "Field 'theme' doesn't have a default value". Make them nullable.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_items', 'theme')) {
                $table->string('theme', 100)->nullable()->change();
            }

            if (Schema::hasColumn('catalog_items', 'sub_theme')) {
                $table->string('sub_theme', 100)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_items', 'theme')) {
                $table->string('theme', 100)->nullable(false)->change();
            }

            if (Schema::hasColumn('catalog_items', 'sub_theme')) {
                $table->string('sub_theme', 100)->nullable(false)->change();
            }
        });
    }
};
