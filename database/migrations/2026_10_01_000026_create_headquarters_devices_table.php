<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headquarters_devices', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->uuid('uuid')->unique();
            $table->char('client_id', 64)->unique();
            $table->string('name', 100);
            $table->string('ip', 45);
            $table->integer('stock_id')->nullable()->index();
            $table->enum('status', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->timestamp('registered_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->foreign('stock_id')->references('id')->on('stocks')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('headquarters_devices');
    }
};
