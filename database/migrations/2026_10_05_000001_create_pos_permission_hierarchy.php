<?php

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\PermisoPos;
use App\Models\Pos\RolPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Refuse an unmappable legacy grant before MySQL executes any DDL.
        $known = App\Http\Requests\Pos\PosManagementRequest::PERMISSIONS;
        foreach (DB::table('rolespos')->select('permisos')->get() as $role) {
            $permissions = json_decode($role->permisos, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($permissions) || array_diff($permissions, $known) !== []) {
                throw new LogicException('Hay permisos POS anteriores sin correspondencia. Revisa su catálogo antes de migrar.');
            }
        }
        Schema::create('modulospos', function (Blueprint $table) {
            $table->id(); $table->string('codigo', 80)->unique(); $table->string('nombre', 100);
            $table->boolean('activo')->default(true); $table->timestamps();
        });
        Schema::create('vistaspos', function (Blueprint $table) {
            $table->id(); $table->foreignId('modulo_id')->constrained('modulospos');
            $table->string('codigo', 80)->unique(); $table->string('nombre', 100);
            $table->boolean('activo')->default(true); $table->timestamps(); $table->unique(['id', 'modulo_id']);
        });
        Schema::create('botonespos', function (Blueprint $table) {
            $table->id(); $table->foreignId('vista_id')->constrained('vistaspos');
            $table->string('codigo', 80)->unique(); $table->string('nombre', 100);
            $table->boolean('activo')->default(true); $table->timestamps(); $table->unique(['id', 'vista_id']);
        });
        Schema::create('permisospos', function (Blueprint $table) {
            $table->id(); $table->foreignId('rol_id')->constrained('rolespos');
            $table->unsignedBigInteger('modulo_id'); $table->unsignedBigInteger('vista_id'); $table->unsignedBigInteger('boton_id');
            $table->boolean('activo')->default(true); $table->timestamps(); $table->unique(['rol_id', 'boton_id']);
            $table->foreign(['vista_id', 'modulo_id'])->references(['id', 'modulo_id'])->on('vistaspos');
            $table->foreign(['boton_id', 'vista_id'])->references(['id', 'vista_id'])->on('botonespos');
        });
        DB::transaction(function (): void {
            $catalog = [
                'pos' => ['Punto de venta', [
                    'pos.facturas' => ['Facturas', ['factura.emitir' => 'Emitir facturas', 'factura.anular' => 'Anular facturas', 'precio.modificar' => 'Modificar precios']],
                    'pos.caja' => ['Caja', ['caja.operar' => 'Operar caja', 'caja.cerrar' => 'Cerrar caja']],
                    'pos.credito' => ['Crédito', ['credito.cobrar' => 'Cobrar crédito']],
                    'pos.devoluciones' => ['Devoluciones', ['devolucion.operar' => 'Registrar devoluciones']],
                ]],
                'inventario' => ['Inventario', ['inventario.operaciones' => ['Operaciones de inventario', ['inventario.operar' => 'Operar inventario']]]],
                'transferencias' => ['Transferencias', ['transferencias.documentos' => ['Documentos de transferencia', ['transferencia.enviar' => 'Enviar transferencias', 'transferencia.recibir' => 'Recibir transferencias', 'transferencia.anular' => 'Anular transferencias']]]],
                'compras' => ['Compras', ['compras.documentos' => ['Documentos de compra', []]]],
                'configuracion' => ['Configuración', [
                    'configuracion.establecimiento' => ['Establecimiento y cajas', ['configurar' => 'Guardar configuración']],
                    'configuracion.usuarios' => ['Administración de usuarios', ['usuarios.administrar' => 'Administrar usuarios']],
                ]],
            ];
            foreach ($catalog as $moduleCode => [$name, $views]) {
                $module = ModuloPos::create(['codigo' => $moduleCode, 'nombre' => $name, 'activo' => true]);
                foreach ($views as $viewCode => [$viewName, $buttons]) {
                    $view = VistaPos::create(['modulo_id' => $module->id, 'codigo' => $viewCode, 'nombre' => $viewName, 'activo' => true]);
                    foreach ($buttons as $code => $buttonName) {
                        BotonPos::create(['vista_id' => $view->id, 'codigo' => $code, 'nombre' => $buttonName, 'activo' => true]);
                    }
                }
            }
            foreach (RolPos::orderBy('id')->lockForUpdate()->get() as $role) {
                foreach ($role->permisos as $code) {
                    $button = BotonPos::where('codigo', $code)->with('vista')->firstOrFail();
                    PermisoPos::create(['rol_id' => $role->id, 'modulo_id' => $button->vista->modulo_id,
                        'vista_id' => $button->vista_id, 'boton_id' => $button->id, 'activo' => true]);
                }
            }
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('permisospos'); Schema::dropIfExists('botonespos');
        Schema::dropIfExists('vistaspos'); Schema::dropIfExists('modulospos');
    }
};
