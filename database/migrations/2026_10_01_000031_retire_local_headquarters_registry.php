<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['headquarters_devices', 'headquarters_matches', 'sync_consumers', 'sync_publications', 'sync_batches'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Transfiera los registros existentes a hq-api antes de retirar '.$table.'.');
            }
        }
        foreach (['sync_batches', 'sync_publications', 'sync_stream_state', 'sync_consumers', 'headquarters_matches', 'headquarters_devices'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        foreach (['000025_create_sync_consumers_table', '000026_create_headquarters_devices_table', '000027_create_headquarters_matches_table',
            '000028_link_sync_consumers_to_headquarters_devices', '000029_create_sync_publication_tables'] as $migration) {
            (require __DIR__.'/2026_10_01_'.$migration.'.php')->up();
        }
    }
};
