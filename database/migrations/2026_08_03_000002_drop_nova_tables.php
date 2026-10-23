<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nova was replaced by Filament, but its tables outlived it: action_events
     * holds Nova's own log of edits made through the old panel, and the three
     * nova_* tables backed its file uploads and in-panel notifications. Nothing
     * in the application has referenced any of them since the move, and Nova is
     * gone from composer.json and vendor/.
     *
     * Nova's migrations came from its own package directory rather than this
     * folder, so they sit in the migrations table as rows with no file. Clear
     * those too, otherwise reinstalling Nova would skip the very migrations that
     * build these tables. The list is every orphaned row found across the
     * databases this app runs on; an older one carries only the action_events
     * pair, and note that create_field_attachments_table is Nova's despite not
     * saying so, which is why these are named rather than pattern-matched.
     */
    protected array $tables = [
        'action_events',
        'nova_field_attachments',
        'nova_notifications',
        'nova_pending_field_attachments',
    ];

    protected array $migrations = [
        '2018_01_01_000000_create_action_events_table',
        '2019_05_10_000000_add_fields_to_action_events_table',
        '2021_08_25_193039_create_nova_notifications_table',
        '2022_04_26_000000_add_fields_to_nova_notifications_table',
        '2022_12_19_000000_create_field_attachments_table',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        DB::table('migrations')->whereIn('migration', $this->migrations)->delete();
    }

    /**
     * Not reversible. Nova owns the shape of these tables and the rows in
     * action_events cannot be reconstructed, so rebuilding a hollow copy here
     * would only look like a rollback. Reinstalling Nova republishes its
     * migrations and builds them properly, which is why the rows above are
     * cleared rather than left behind.
     */
    public function down(): void
    {
        //
    }
};
