<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['modulospos', 'vistaspos', 'botonespos', 'sync_changes', 'sync_entity_versions', 'hq_outbox'];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return;
        $engines = [];
        // Validate every prerequisite before any non-transactional DDL.
        foreach (self::TABLES as $name) {
            if (! Schema::hasTable($name)) throw new LogicException('Falta una tabla del catálogo/publicación POS: '.$name);
            $engine = strtolower((string) $this->engine($name));
            if (! in_array($engine, ['innodb', 'myisam'], true)) {
                throw new LogicException('Motor no reconocido para el catálogo/publicación POS: '.$name);
            }
            $engines[$name] = $engine;
        }
        foreach ($engines as $name => $engine) {
            if ($engine === 'innodb') continue;
            $count = DB::table($name)->count();
            $columns = $this->columns($name);
            $indexes = $this->indexes($name);
            $digest = $this->digest($name, $indexes);
            // Fixed whitelist only; never truncate or rewrite business rows.
            DB::statement('ALTER TABLE `'.$name.'` ENGINE=InnoDB');
            if (strtolower((string) $this->engine($name)) !== 'innodb'
                || DB::table($name)->count() !== $count
                || $this->columns($name) !== $columns || $this->indexes($name) !== $indexes
                || $this->digest($name, $indexes) !== $digest) {
                throw new LogicException('La conversión debe conservar filas, columnas e índices POS: '.$name);
            }
        }
    }

    private function engine(string $name): ?string
    {
        return DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $name)->value('ENGINE');
    }

    private function columns(string $name): array
    {
        return array_map(fn (object $column): array => (array) $column,
            DB::select('SHOW FULL COLUMNS FROM `'.$name.'`'));
    }

    private function indexes(string $name): array
    {
        $indexes = array_map(fn (object $index): array => [
            $index->Key_name, (int) $index->Seq_in_index, $index->Column_name,
            (int) $index->Non_unique, $index->Sub_part, $index->Index_type, $index->Collation,
        ], DB::select('SHOW INDEX FROM `'.$name.'`'));
        sort($indexes);
        return $indexes;
    }

    private function digest(string $name, array $indexes): string
    {
        $hash = hash_init('sha256');
        $primary = array_values(array_filter($indexes, fn (array $index): bool => $index[0] === 'PRIMARY'));
        if ($primary === []) throw new LogicException('Se requiere clave primaria para verificar filas POS: '.$name);
        usort($primary, fn (array $left, array $right): int => $left[1] <=> $right[1]);
        $query = DB::table($name);
        foreach ($primary as $column) $query->orderBy($column[2]);
        foreach ($query->cursor() as $row) {
            $values = (array) $row;
            ksort($values);
            hash_update($hash, json_encode($values, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
        }
        return hash_final($hash);
    }

    public function down(): void
    {
        throw new LogicException('Conservar los motores transaccionales, permisos e historial de publicación POS.');
    }
};
