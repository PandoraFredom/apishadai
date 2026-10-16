<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headquarters_matches', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('ip', 45);
            $table->integer('stock_id')->nullable()->index();
            $table->char('client_id', 64);
            $table->char('registration_key_hash', 64)->unique();
            $table->string('approval_code_hash', 255);
            $table->char('key_hash', 64)->nullable()->unique();
            $table->unsignedBigInteger('headquarters_device_id')->nullable()->index();
            $table->enum('status', ['PENDING', 'ACTIVE', 'REVOKED'])->default('PENDING');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->foreign('stock_id')->references('id')->on('stocks')->restrictOnDelete();
            $table->foreign('headquarters_device_id')->references('id')->on('headquarters_devices')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('headquarters_matches');
    }
};
