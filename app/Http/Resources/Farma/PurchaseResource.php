<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $applied = (float) ($this->total_aplicado ?? $this->transacciones->sum('valor'));
        $balance = max(0, (float) $this->total - $applied);
        $storedStatus = (string) ($this->estado ?: 'pendiente');
        $dueDate = $this->created_at?->copy()->addDays(max(0, (int) $this->plazo))->startOfDay();
        $status = match (true) {
            $storedStatus === 'borrador' => 'borrador',
            (float) $this->total > 0 && $balance <= 0.005 => 'pagada',
            (int) $this->plazo > 0 && $dueDate?->lt(today()) === true => 'vencida',
            default => 'pendiente',
        };

        return [
            'id' => (int) $this->id,
            'tipo_id' => (int) $this->tipo,
            'proveedor_id' => (int) $this->proveedor,
            'usuario_id' => (int) $this->usuario,
            'plazo' => (int) $this->plazo,
            'nro' => (string) $this->nro,
            'items' => (int) $this->items,
            'isv' => (float) $this->isv,
            'subtotal' => (float) $this->subtotal,
            'descuento' => (float) $this->descuento,
            'total' => (float) $this->total,
            'total_aplicado' => $applied,
            'saldo' => $balance,
            'estado' => $status,
            'estado_registrado' => $storedStatus,
            'estado_label' => match ($status) {
                'borrador' => 'Borrador',
                'pagada' => 'Pagada',
                'vencida' => 'Vencida',
                default => 'Pendiente',
            },
            'vence_el' => $dueDate?->toDateString(),
            'nota' => $this->nota,
            'tiene_documento' => (bool) ($this->tiene_documento ?? false),
            'kardex_enviado' => $this->kardex_enviado_at !== null,
            'kardex_enviado_at' => $this->kardex_enviado_at?->toIso8601String(),
            'kardex_usuario_id' => $this->kardex_usuario !== null ? (int) $this->kardex_usuario : null,
            'tipo' => $this->whenLoaded('tipoDetalle', fn (): array => ['id' => (int) $this->tipoDetalle->getKey(), 'descripcion' => (string) $this->tipoDetalle->descripcion]),
            'proveedor' => $this->whenLoaded('proveedorDetalle', fn (): array => ['id' => (int) $this->proveedorDetalle->id, 'nombre' => (string) $this->proveedorDetalle->nombre]),
            'usuario' => $this->whenLoaded('usuarioDetalle', fn (): array => ['id' => (int) $this->usuarioDetalle->id, 'nombre' => (string) ($this->usuarioDetalle->nombre ?? $this->usuarioDetalle->name ?? '')]),
            'kardex_usuario' => $this->whenLoaded('kardexUsuarioDetalle', fn (): array => ['id' => (int) $this->kardexUsuarioDetalle->id, 'nombre' => (string) ($this->kardexUsuarioDetalle->nombre ?? $this->kardexUsuarioDetalle->name ?? '')]),
            'detalles' => PurchaseDetailResource::collection($this->whenLoaded('detalles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
