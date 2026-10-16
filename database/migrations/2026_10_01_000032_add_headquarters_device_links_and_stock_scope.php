<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach (['devices', 'device_estado', 'distribucion', 'lotes', 'transferencias', 'trasferencia_detalle', 'transferencias_tipo', 'transferencia_estado'] as $table) {
                DB::statement("ALTER TABLE `$table` ENGINE=InnoDB");
            }
        }
        Schema::table('devices', function (Blueprint $table): void {
            $table->uuid('hq_client_uuid')->nullable()->unique();
        });
        DB::table('devices')->orderBy('id')->chunkById(100, function ($devices): void {
            foreach ($devices as $device) {
                DB::table('devices')->where('id', $device->id)->update(['hq_client_uuid' => (string) Str::uuid()]);
            }
        });
        Schema::table('sync_entity_versions', fn (Blueprint $table) => $table->json('stock_ids')->nullable());
        Schema::table('sync_changes', function (Blueprint $table): void {
            $table->json('stock_ids')->nullable();
            $table->json('removed_stock_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sync_changes', fn (Blueprint $table) => $table->dropColumn(['stock_ids', 'removed_stock_ids']));
        Schema::table('sync_entity_versions', fn (Blueprint $table) => $table->dropColumn('stock_ids'));
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropUnique(['hq_client_uuid']);
            $table->dropColumn('hq_client_uuid');
        });
    }
};
