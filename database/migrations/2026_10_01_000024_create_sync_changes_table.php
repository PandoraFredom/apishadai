<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_changes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('entity', 100);
            // Legacy entities use signed INT; clientepos and users use unsigned BIGINT.
            $table->unsignedBigInteger('entity_id');
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE']);
            $table->unsignedBigInteger('version');
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'entity_id']);
            $table->index('created_at');
        });

        // SQLite does not support ADD CONSTRAINT; model validation also covers tests.
        $connection = Schema::getConnection();
        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $table = $connection->getQueryGrammar()->wrapTable('sync_changes');
            $connection->statement("ALTER TABLE {$table} ADD CONSTRAINT sync_changes_version_positive CHECK (version > 0)");
            $connection->statement("ALTER TABLE {$table} ADD CONSTRAINT sync_changes_schema_version_positive CHECK (schema_version > 0)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_changes');
    }
};
