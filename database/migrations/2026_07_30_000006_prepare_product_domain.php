<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                DB::statement('ALTER TABLE productos MODIFY id INT NOT NULL AUTO_INCREMENT');
                DB::statement('ALTER TABLE productos MODIFY imagen MEDIUMBLOB NULL');
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        Schema::table('prod_unidades', function (Blueprint $table): void {
            $table->unique(
                ['abreviatura_c', 'abreviatura_v', 'cantidad_c', 'cantidad_v'],
                'uq_prod_unidades_conversion',
            );
        });
        Schema::table('prod_estado', function (Blueprint $table): void {
            $table->unique('descripcion', 'uq_prod_estado_descripcion');
        });
        Schema::table('prod_categorias', function (Blueprint $table): void {
            $table->unique('nombre', 'uq_prod_categorias_nombre');
        });
        Schema::table('fam_presentacion', function (Blueprint $table): void {
            $table->unique('descripcion', 'uq_fam_presentacion_descripcion');
        });
        Schema::table('fam_administracion', function (Blueprint $table): void {
            $table->unique('descripcion', 'uq_fam_administracion_descripcion');
        });
        Schema::table('familia', function (Blueprint $table): void {
            $table->unique(
                ['presentacion', 'administracion', 'descripcion'],
                'uq_familia_composicion',
            );
        });
        Schema::table('concentraciones', function (Blueprint $table): void {
            $table->unique('valor', 'uq_concentraciones_valor');
        });
        Schema::table('principal_activos', function (Blueprint $table): void {
            $table->unique(['nombre', 'concentracion'], 'uq_principal_activo');
        });
        Schema::table('productos', function (Blueprint $table): void {
            $table->unique('codigo', 'uq_productos_codigo');
            $table->unique('codigobar', 'uq_productos_codigobar');
        });
        Schema::table('prod_activo', function (Blueprint $table): void {
            $table->unique(['producto', 'pactivo'], 'uq_prod_activo');
        });
    }

    public function down(): void
    {
        Schema::table('prod_activo', fn (Blueprint $table) => $table->dropUnique('uq_prod_activo'));
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique('uq_productos_codigo');
            $table->dropUnique('uq_productos_codigobar');
        });
        Schema::table('principal_activos', fn (Blueprint $table) => $table->dropUnique('uq_principal_activo'));
        Schema::table('concentraciones', fn (Blueprint $table) => $table->dropUnique('uq_concentraciones_valor'));
        Schema::table('familia', fn (Blueprint $table) => $table->dropUnique('uq_familia_composicion'));
        Schema::table('fam_administracion', fn (Blueprint $table) => $table->dropUnique('uq_fam_administracion_descripcion'));
        Schema::table('fam_presentacion', fn (Blueprint $table) => $table->dropUnique('uq_fam_presentacion_descripcion'));
        Schema::table('prod_categorias', fn (Blueprint $table) => $table->dropUnique('uq_prod_categorias_nombre'));
        Schema::table('prod_estado', fn (Blueprint $table) => $table->dropUnique('uq_prod_estado_descripcion'));
        Schema::table('prod_unidades', fn (Blueprint $table) => $table->dropUnique('uq_prod_unidades_conversion'));
    }
};
