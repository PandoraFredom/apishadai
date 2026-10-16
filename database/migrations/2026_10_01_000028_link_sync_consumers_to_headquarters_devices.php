<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_consumers', function (Blueprint $table): void {
            $table->unsignedBigInteger('headquarters_device_id')->nullable()->unique();
            $table->foreign('headquarters_device_id')->references('id')->on('headquarters_devices')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sync_consumers', function (Blueprint $table): void {
            $table->dropForeign(['headquarters_device_id']);
            $table->dropUnique(['headquarters_device_id']);
            $table->dropColumn('headquarters_device_id');
        });
    }
};
