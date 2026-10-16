<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clavespos', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_id')->unique();
            $table->char('key_id', 64)->unique();
            $table->text('public_key');
            $table->timestamps();
            $table->foreign('stock_id')->references('id')->on('stocks');
        });
        Schema::create('rolespos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->json('permisos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('usuariospos', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_id');
            $table->foreignId('rol_id')->constrained('rolespos');
            $table->string('nombre', 200);
            $table->char('usuario_lookup', 64);
            $table->text('secreto');
            $table->json('credenciales');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['stock_id', 'usuario_lookup']);
            $table->foreign('stock_id')->references('id')->on('stocks');
        });
        Schema::create('catalogospos', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_id');
            $table->string('tipo', 30);
            $table->string('codigo', 80);
            $table->json('datos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['stock_id', 'tipo', 'codigo']);
            $table->foreign('stock_id')->references('id')->on('stocks');
        });
    }

    public function down(): void
    {
        foreach (['usuariospos', 'catalogospos', 'rolespos', 'clavespos'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
