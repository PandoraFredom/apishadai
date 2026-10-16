<?php

use App\Services\Headquarters\EntityContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach ([...array_keys(EntityContract::FIELDS), 'prod_activo'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE `$table` ENGINE=InnoDB");
                }
            }
        }
        Schema::create('sync_entity_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->string('entity', 100);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('version')->default(0);
            $table->primary(['entity', 'entity_id']);
        });
        Schema::create('hq_outbox', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('sync_change_id')->unique()->constrained('sync_changes')->restrictOnDelete();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->uuid('claim_token')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->unsignedSmallInteger('last_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hq_outbox');
        Schema::dropIfExists('sync_entity_versions');
    }
};
