<?php

use App\Models\Pos\PermisoUsuarioPos;
use App\Models\Pos\RolPos;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rolespos', fn (Blueprint $table) => $table->unsignedInteger('nivel')->default(0));
        Schema::create('permisosusuariospos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario')->constrained('usuariospos');
            $table->unsignedBigInteger('modulo');
            $table->unsignedBigInteger('vista');
            $table->unsignedBigInteger('actionvista');
            $table->unsignedInteger('tipo_tiempo')->nullable();
            $table->unsignedBigInteger('lifetime')->nullable();
            $table->timestamps();
            $table->unique(['usuario', 'actionvista']);
            $table->foreign(['vista', 'modulo'])->references(['id', 'modulo_id'])->on('vistaspos');
            $table->foreign(['actionvista', 'vista'])->references(['id', 'vista_id'])->on('botonespos');
        });
        DB::transaction(function (): void {
            // Preserve existing effective access once. New users never inherit role grants.
            $legacy = DB::table('permisospos as p')->join('usuariospos as u', 'u.rol_id', '=', 'p.rol_id')
                ->where('p.activo', true)->select('u.id as usuario', 'p.modulo_id as modulo', 'p.vista_id as vista', 'p.boton_id as actionvista')->get();
            foreach (RolPos::orderBy('id')->get() as $role) {
                app(ChangeRecorder::class)->record($role, 'UPDATE');
            }
            foreach ($legacy as $grant) {
                PermisoUsuarioPos::create((array) $grant + ['tipo_tiempo' => null, 'lifetime' => null]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisosusuariospos');
        Schema::table('rolespos', fn (Blueprint $table) => $table->dropColumn('nivel'));
    }
};
