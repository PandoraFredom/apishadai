<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compras') && ! Schema::hasColumn('compras', 'estado')) {
            Schema::table('compras', function (Blueprint $table): void {
                $table->string('estado', 20)->default('pendiente')->after('total')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compras') && Schema::hasColumn('compras', 'estado')) {
            Schema::table('compras', function (Blueprint $table): void {
                $table->dropColumn('estado');
            });
        }
    }
};
