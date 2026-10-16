<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientepos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 180)->index();
            $table->string('rtn', 20)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientepos');
    }
};
