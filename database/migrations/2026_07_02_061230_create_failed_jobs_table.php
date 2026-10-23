<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarded because the queue tables predate this migration on databases that
     * were set up by hand, before the queue infrastructure was committed. The
     * table is there, the migration never is, and an unguarded create fails the
     * whole deploy with "table already exists". An existing table is brought up
     * to this shape by 2026_08_03_000001_reconcile_queue_tables.
     */
    public function up(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
