<?php

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transferencias_centrales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('transferencia_uuid')->unique();
            $table->uuid('solicitud_envio')->unique();
            $table->char('huella_envio', 64);
            $table->unsignedBigInteger('stock_de')->index();
            $table->unsignedBigInteger('stock_para')->index();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->unsignedBigInteger('usuario_envia_pos')->nullable();
            $table->unsignedBigInteger('usuario_recibe_pos')->nullable();
            $table->uuid('solicitud_recepcion')->nullable()->unique();
            $table->char('huella_recepcion', 64)->nullable();
            $table->longText('datos');
            $table->string('estado', 10)->default('PENDIENTE');
            $table->timestamp('enviado_at');
            $table->timestamp('recibido_at')->nullable();
        });
        Schema::create('hq_pos_request_nonces', function (Blueprint $table): void {
            $table->uuid('nonce')->primary();
            $table->timestamp('expires_at')->index();
        });
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', 'transferencias')->lockForUpdate()->firstOrFail();
            $view = VistaPos::firstOrCreate(['codigo' => 'transferencias.documentos'], [
                'modulo_id' => $module->id, 'nombre' => 'Documentos de transferencia', 'activo' => true,
            ]);
            if ((string) $view->modulo_id !== (string) $module->id) {
                throw new LogicException('La vista de transferencias pertenece a otro módulo.');
            }
            foreach (['ver' => 'Ver transferencias', 'enviar' => 'Enviar a otro stock', 'recibir' => 'Recibir físicamente en el stock destino'] as $action => $name) {
                $button = BotonPos::firstOrCreate(['codigo' => 'transferencia.'.$action], [
                    'vista_id' => $view->id, 'nombre' => $name, 'activo' => true,
                ]);
                if ((string) $button->vista_id !== (string) $view->id) {
                    throw new LogicException('La acción de transferencias pertenece a otra vista.');
                }
            }
        });
    }

    public function down(): void
    {
        throw new LogicException('Conservar transferencias, solicitudes e historial; utilizar una migración nueva.');
    }
};
