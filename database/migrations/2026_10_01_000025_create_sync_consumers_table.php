<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_consumers', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            // Match the existing signed INT primary keys, not Laravel's foreignId type.
            $table->integer('device_id')->nullable()->index();
            $table->integer('stock_id')->nullable()->index();
            $table->unsignedBigInteger('last_sync_sequence')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->enum('status', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->restrictOnDelete();
            $table->foreign('stock_id')->references('id')->on('stocks')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_consumers');
    }
};
