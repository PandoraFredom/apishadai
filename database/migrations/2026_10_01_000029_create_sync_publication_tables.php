<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_stream_state', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->string('name', 50)->primary();
            $table->unsignedBigInteger('published_sequence')->default(0);
            // Last purged publication sequence; cursors below it require bootstrap.
            $table->unsignedBigInteger('retention_floor')->default(0);
            $table->timestamps();
        });
        DB::table('sync_stream_state')->insert([
            'name' => 'headquarters', 'published_sequence' => 0, 'retention_floor' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Schema::create('sync_publications', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('sequence')->primary();
            $table->unsignedBigInteger('sync_change_id')->unique();
            $table->timestamp('created_at');
            $table->foreign('sync_change_id')->references('id')->on('sync_changes')->restrictOnDelete();
        });
        Schema::create('sync_batches', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->uuid('uuid')->primary();
            $table->unsignedBigInteger('sync_consumer_id')->index();
            $table->unsignedBigInteger('from_sequence');
            $table->unsignedBigInteger('to_sequence');
            $table->timestamp('expires_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('created_at');
            $table->foreign('sync_consumer_id')->references('id')->on('sync_consumers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batches');
        Schema::dropIfExists('sync_publications');
        Schema::dropIfExists('sync_stream_state');
    }
};
