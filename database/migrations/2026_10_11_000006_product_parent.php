<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $id = collect(Schema::getColumns('productos'))->firstWhere('name', 'id');
        $type = strtolower($id['type_name']);
        $unsigned = str_contains(strtolower($id['type']), 'unsigned');
        Schema::table('productos', function (Blueprint $table) use ($type, $unsigned): void {
            $column = match ($type) {
                'int', 'integer' => $table->integer('producto_padre_id'),
                'bigint' => $table->bigInteger('producto_padre_id'),
                default => throw new RuntimeException('El identificador de productos necesita una migración compatible.'),
            };
            $column->unsigned($unsigned)->nullable()->index();
            $table->foreign('producto_padre_id')->references('id')->on('productos')->restrictOnDelete();
        });
        Schema::create('producto_relacion_mutex', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
        });
        DB::table('producto_relacion_mutex')->insert(['id' => 1]);
    }

    public function down(): void
    {
        if (DB::table('productos')->whereNotNull('producto_padre_id')->exists()) {
            throw new RuntimeException('Conserva las relaciones de productos antes de retirar el esquema.');
        }
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropForeign(['producto_padre_id']);
            $table->dropColumn('producto_padre_id');
        });
        Schema::dropIfExists('producto_relacion_mutex');
    }
};
