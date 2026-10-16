<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameAction('filtrar por stock');
    }

    public function down(): void
    {
        $this->renameAction('filtrar por dispositivo');
    }

    private function renameAction(string $name): void
    {
        if (Schema::hasTable('actionsvistas')) {
            DB::table('actionsvistas')->where('codigo', 'trfbtn07')->update([
                'nombre' => $name,
                'updated_at' => now(),
            ]);
        }
    }
};
