<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The three create_*_jobs migrations skip a table that already exists, so a
     * database that gained its queue tables by hand keeps whatever shape they
     * were made with. jobs and job_batches have not changed shape, but
     * failed_jobs has twice: Laravel 8 added the uuid the failed-job provider
     * writes on every failure, and connection/queue went from text to varchar.
     * Left alone, the deploy passes and the queue breaks later, the first time
     * a job fails.
     *
     * jobs only differs in one place: queue:table built attempts as a tinyint
     * before Laravel widened it to a smallint. 255 retries is far beyond
     * anything the queue would reach, but widening costs nothing and cannot
     * lose data.
     *
     * Everything here is additive and individually guarded, so it is safe on a
     * table that is already current and on one left half-converted by an earlier
     * failed run.
     */
    public function up(): void
    {
        $this->widenJobAttempts();

        if (! Schema::hasTable('failed_jobs')) {
            return;
        }

        $this->addUuid();
        $this->narrowToVarchar();
        $this->addIndexes();
    }

    protected function widenJobAttempts(): void
    {
        if (! Schema::hasTable('jobs') || $this->typeName('jobs', 'attempts') !== 'tinyint') {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedSmallInteger('attempts')->nullable(false)->change();
        });
    }

    /**
     * Nullable first, because existing rows need a value before the column can
     * carry a unique index.
     */
    protected function addUuid(): void
    {
        if (Schema::hasColumn('failed_jobs', 'uuid')) {
            return;
        }

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
        });

        DB::table('failed_jobs')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('failed_jobs')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->change();
        });
    }

    /**
     * MySQL cannot index a text column without a prefix length, so the
     * connection/queue index below needs these narrowed first. Queue and
     * connection names are short enough that this never truncates, but check
     * rather than assume.
     */
    protected function narrowToVarchar(): void
    {
        foreach (['connection', 'queue'] as $column) {
            if ($this->typeName('failed_jobs', $column) !== 'text') {
                continue;
            }

            if (DB::table('failed_jobs')->whereRaw("CHAR_LENGTH({$column}) > 255")->exists()) {
                continue;
            }

            Schema::table('failed_jobs', function (Blueprint $table) use ($column) {
                $table->string($column)->nullable(false)->change();
            });
        }
    }

    protected function addIndexes(): void
    {
        if (! Schema::hasIndex('failed_jobs', 'failed_jobs_uuid_unique')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }

        // Only a lookup index, so skip it rather than fail the deploy if the
        // columns could not be narrowed above.
        $indexable = $this->typeName('failed_jobs', 'connection') !== 'text'
            && $this->typeName('failed_jobs', 'queue') !== 'text';

        if ($indexable && ! Schema::hasIndex('failed_jobs', 'failed_jobs_connection_queue_failed_at_index')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->index(['connection', 'queue', 'failed_at']);
            });
        }
    }

    protected function typeName(string $table, string $column): ?string
    {
        foreach (Schema::getColumns($table) as $definition) {
            if ($definition['name'] === $column) {
                return $definition['type_name'];
            }
        }

        return null;
    }

    /**
     * Left alone: this only ever adds what was missing, and dropping the uuid
     * again would break the queue on a database that now depends on it.
     */
    public function down(): void
    {
        //
    }
};
