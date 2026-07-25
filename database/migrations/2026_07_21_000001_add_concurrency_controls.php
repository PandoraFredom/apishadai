<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIdempotencyKeysTable();
        $this->createTicketCountersTable();
        $this->addWorkDateColumn();
        $this->archiveAndRemoveDuplicatePermisos();
        $this->addUniqueIndexes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('permisos', 'permisos_unique_user_module_view_action');
        $this->dropIndexIfExists('tikets', 'tikets_ntiket_unique');
        $this->dropIndexIfExists('worklunch', 'worklunch_unique_user_work_date');

        if (Schema::hasColumn('worklunch', 'work_date')) {
            Schema::table('worklunch', function (Blueprint $table) {
                $table->dropColumn('work_date');
            });
        }

        Schema::dropIfExists('ticket_counters');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('permisos_duplicate_archives');
    }

    private function createIdempotencyKeysTable(): void
    {
        if (Schema::hasTable('idempotency_keys')) {
            return;
        }

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('key');
            $table->string('method', 10);
            $table->string('route');
            $table->char('request_hash', 64);
            $table->string('status', 20);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key'], 'idempotency_user_key_unique');
            $table->index('expires_at', 'idempotency_expires_at_index');
        });
    }

    private function createTicketCountersTable(): void
    {
        if (!Schema::hasTable('ticket_counters')) {
            Schema::create('ticket_counters', function (Blueprint $table) {
                $table->string('name', 50)->primary();
                $table->unsignedBigInteger('current_value')->default(0);
                $table->timestamps();
            });
        }

        if (!DB::table('ticket_counters')->where('name', 'tikets')->exists()) {
            DB::table('ticket_counters')->insert([
                'name' => 'tikets',
                'current_value' => (int) DB::table('tikets')->max('ntiket'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addWorkDateColumn(): void
    {
        if (!Schema::hasColumn('worklunch', 'work_date')) {
            Schema::table('worklunch', function (Blueprint $table) {
                $table->date('work_date')->nullable()->after('wkstart_time');
            });
        }

        DB::table('worklunch')
            ->whereNull('work_date')
            ->whereNotNull('wkstart_time')
            ->update(['work_date' => DB::raw('DATE(wkstart_time)')]);
    }

    private function archiveAndRemoveDuplicatePermisos(): void
    {
        if (!Schema::hasTable('permisos_duplicate_archives')) {
            Schema::create('permisos_duplicate_archives', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('original_id');
                $table->unsignedBigInteger('usuario');
                $table->unsignedBigInteger('modulo');
                $table->unsignedBigInteger('vista');
                $table->unsignedBigInteger('actionvista');
                $table->unsignedBigInteger('tipo_tiempo')->nullable();
                $table->unsignedBigInteger('lifetime')->nullable();
                $table->timestamp('original_created_at')->nullable();
                $table->timestamp('original_updated_at')->nullable();
                $table->timestamp('archived_at');
            });
        }

        DB::statement('ALTER TABLE permisos_duplicate_archives MODIFY lifetime BIGINT UNSIGNED NULL');

        DB::statement(<<<'SQL'
            INSERT INTO permisos_duplicate_archives (
                original_id,
                usuario,
                modulo,
                vista,
                actionvista,
                tipo_tiempo,
                lifetime,
                original_created_at,
                original_updated_at,
                archived_at
            )
            SELECT
                p.id,
                p.usuario,
                p.modulo,
                p.vista,
                p.actionvista,
                p.tipo_tiempo,
                p.lifetime,
                p.created_at,
                p.updated_at,
                NOW()
            FROM permisos p
            JOIN (
                SELECT
                    usuario,
                    modulo,
                    vista,
                    actionvista,
                    CAST(SUBSTRING_INDEX(
                        GROUP_CONCAT(id ORDER BY (lifetime IS NULL) DESC, lifetime DESC, id DESC),
                        ',',
                        1
                    ) AS UNSIGNED) AS keep_id
                FROM permisos
                GROUP BY usuario, modulo, vista, actionvista
                HAVING COUNT(*) > 1
            ) keepers
                ON keepers.usuario = p.usuario
                AND keepers.modulo = p.modulo
                AND keepers.vista = p.vista
                AND keepers.actionvista = p.actionvista
            WHERE p.id <> keepers.keep_id
                AND NOT EXISTS (
                    SELECT 1
                    FROM permisos_duplicate_archives a
                    WHERE a.original_id = p.id
                )
        SQL);

        DB::statement(<<<'SQL'
            DELETE p
            FROM permisos p
            JOIN (
                SELECT
                    usuario,
                    modulo,
                    vista,
                    actionvista,
                    CAST(SUBSTRING_INDEX(
                        GROUP_CONCAT(id ORDER BY (lifetime IS NULL) DESC, lifetime DESC, id DESC),
                        ',',
                        1
                    ) AS UNSIGNED) AS keep_id
                FROM permisos
                GROUP BY usuario, modulo, vista, actionvista
                HAVING COUNT(*) > 1
            ) keepers
                ON keepers.usuario = p.usuario
                AND keepers.modulo = p.modulo
                AND keepers.vista = p.vista
                AND keepers.actionvista = p.actionvista
            WHERE p.id <> keepers.keep_id
        SQL);
    }

    private function addUniqueIndexes(): void
    {
        if (!$this->indexExists('permisos', 'permisos_unique_user_module_view_action')) {
            Schema::table('permisos', function (Blueprint $table) {
                $table->unique(
                    ['usuario', 'modulo', 'vista', 'actionvista'],
                    'permisos_unique_user_module_view_action'
                );
            });
        }

        if (!$this->indexExists('tikets', 'tikets_ntiket_unique')) {
            Schema::table('tikets', function (Blueprint $table) {
                $table->unique('ntiket', 'tikets_ntiket_unique');
            });
        }

        if (!$this->indexExists('worklunch', 'worklunch_unique_user_work_date')) {
            Schema::table('worklunch', function (Blueprint $table) {
                $table->unique(['usuario', 'work_date'], 'worklunch_unique_user_work_date');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn(array $item) => $item['name'] === $index);
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!$this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($index) {
            $table->dropIndex($index);
        });
    }
};
