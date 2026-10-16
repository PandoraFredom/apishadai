<?php
namespace Tests\Feature\Pos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class PosPermissionEnginesTest extends TestCase
{
    private function migration(): object
    { return require database_path('migrations/2026_10_13_000002_pos_permission_transaction_engines.php'); }

    private function engineQuery(array $engines): void
    {
        DB::shouldReceive('getDriverName')->andReturn('mysql');
        DB::shouldReceive('getDatabaseName')->andReturn('TEST');
        $schema = Mockery::mock();
        $schema->shouldReceive('hasTable')->andReturn(true);
        Schema::swap($schema);
        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('value')->with('ENGINE')->andReturn(...$engines);
        DB::shouldReceive('table')->with('information_schema.tables')->andReturn($query);
    }

    public function test_existing_innodb_requires_no_conversion(): void
    {
        $this->engineQuery(array_fill(0, 6, 'InnoDB'));
        DB::shouldReceive('statement')->never();
        $this->migration()->up();
        $this->assertTrue(true);
    }

    public function test_unknown_engine_is_rejected_before_any_ddl(): void
    {
        $this->engineQuery(['MyISAM', 'UNKNOWN']);
        DB::shouldReceive('statement')->never();
        $this->expectException(\LogicException::class);
        $this->migration()->up();
    }

    public function test_myisam_conversion_checks_rows_columns_indexes_and_digest(): void
    {
        $this->engineQuery(['MyISAM', 'InnoDB', 'InnoDB', 'InnoDB', 'InnoDB', 'InnoDB', 'InnoDB']);
        $rows = Mockery::mock();
        $rows->shouldReceive('count')->twice()->andReturn(2);
        $rows->shouldReceive('orderBy')->with('id')->twice()->andReturnSelf();
        $rows->shouldReceive('cursor')->twice()->andReturn(collect([(object) ['id' => 1, 'codigo' => 'TEST-A'], (object) ['id' => 2, 'codigo' => 'TEST-B']]));
        DB::shouldReceive('table')->with('modulospos')->andReturn($rows);
        DB::shouldReceive('select')->with('SHOW FULL COLUMNS FROM `modulospos`')->twice()->andReturn([(object) ['Field' => 'id', 'Type' => 'bigint']]);
        DB::shouldReceive('select')->with('SHOW INDEX FROM `modulospos`')->twice()->andReturn([(object) [
            'Key_name' => 'PRIMARY', 'Seq_in_index' => 1, 'Column_name' => 'id', 'Non_unique' => 0,
            'Sub_part' => null, 'Index_type' => 'BTREE', 'Collation' => 'A']]);
        DB::shouldReceive('statement')->with('ALTER TABLE `modulospos` ENGINE=InnoDB')->once()->andReturn(true);
        $this->migration()->up();
        $this->assertTrue(true);
    }

    public function test_different_row_count_stops_instead_of_claiming_success(): void
    {
        $this->engineQuery(['MyISAM', 'InnoDB', 'InnoDB', 'InnoDB', 'InnoDB', 'InnoDB', 'InnoDB']);
        $rows = Mockery::mock();
        $rows->shouldReceive('count')->twice()->andReturn(2, 1);
        $rows->shouldReceive('orderBy')->with('id')->once()->andReturnSelf();
        $rows->shouldReceive('cursor')->once()->andReturn(collect([(object) ['id' => 1], (object) ['id' => 2]]));
        DB::shouldReceive('table')->with('modulospos')->andReturn($rows);
        DB::shouldReceive('select')->with('SHOW FULL COLUMNS FROM `modulospos`')->once()->andReturn([]);
        DB::shouldReceive('select')->with('SHOW INDEX FROM `modulospos`')->once()->andReturn([(object) [
            'Key_name' => 'PRIMARY', 'Seq_in_index' => 1, 'Column_name' => 'id', 'Non_unique' => 0,
            'Sub_part' => null, 'Index_type' => 'BTREE', 'Collation' => 'A']]);
        DB::shouldReceive('statement')->with('ALTER TABLE `modulospos` ENGINE=InnoDB')->once()->andReturn(true);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('conservar filas');
        $this->migration()->up();
    }
}
