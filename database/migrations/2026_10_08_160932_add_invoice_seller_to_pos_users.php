<?php

use App\Models\Pos\BotonPos;
use App\Models\Pos\UsuarioPos;
use App\Models\Pos\VistaPos;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('usuariospos', 'vendedor')) {
            Schema::table('usuariospos', function (Blueprint $table): void {
                $table->string('vendedor', 200)->nullable();
            });
        }
        DB::transaction(function (): void {
            $view = VistaPos::where('codigo', 'pos.facturas')->lockForUpdate()->firstOrFail();
            $button = BotonPos::firstOrCreate(['codigo' => 'factura.imprimir'], [
                'vista_id' => $view->id, 'nombre' => 'Imprimir facturas', 'activo' => true,
            ]);
            if ($button->vista_id !== $view->id) {
                throw new LogicException('La acción de impresión pertenece a otra vista.');
            }
            foreach (UsuarioPos::orderBy('id')->get() as $user) {
                app(ChangeRecorder::class)->record($user, 'UPDATE');
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('El vendedor y los permisos usados por facturas históricas se conservan.');
    }
};
