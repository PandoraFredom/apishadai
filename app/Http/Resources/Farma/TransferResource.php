<?php

namespace App\Http\Resources\Farma;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TransferResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $sendStatus = Str::of((string) ($this->estadoDetalle?->descripcion ?? ''))->lower()->ascii()->slug('_')->toString();
        $receiptStatus = Str::of((string) ($this->estadoRecepcionDetalle?->descripcion ?? ''))->lower()->ascii()->slug('_')->toString();
        $device = $request->attributes->get('authenticated_device');
        $currentUserId = $request->user()?->getAuthIdentifier();

        return [
            'id' => (int) $this->id,
            'tipo_id' => (int) $this->tipo,
            'stock_de_id' => (int) $this->stock_de,
            'stock_para_id' => (int) $this->stock_para,
            'usuario_envia_id' => (int) $this->usuario_envia,
            'usuario_recibe_id' => $this->usuario_recibe !== null ? (int) $this->usuario_recibe : null,
            'items' => (int) $this->items,
            'subtotal' => (float) $this->subtotal,
            'isv' => (float) $this->isvtotal,
            'total' => (float) $this->total,
            'estado_id' => (int) $this->estado,
            'estado' => $sendStatus,
            'estado_label' => (string) ($this->estadoDetalle?->descripcion ?? ''),
            'estado_recepcion_id' => (int) $this->estado_recepcion,
            'estado_recepcion' => $receiptStatus,
            'estado_recepcion_label' => (string) ($this->estadoRecepcionDetalle?->descripcion ?? ''),
            'estado_general' => $receiptStatus,
            'enviado_at' => $this->enviado_at?->toIso8601String(),
            'recibido_at' => $this->recibido_at?->toIso8601String(),
            'puede_recibir' => $sendStatus === 'enviada'
                && $receiptStatus === 'pendiente'
                && $this->usuario_recibe === null
                && $device instanceof Device
                && (int) $device->stock === (int) $this->stock_para
                && (int) $currentUserId !== (int) $this->usuario_envia,
            'tipo' => $this->whenLoaded('tipoDetalle', fn (): array => [
                'id' => (int) $this->tipoDetalle->id,
                'descripcion' => (string) $this->tipoDetalle->descripcion,
            ]),
            'stock_de' => $this->whenLoaded('stockOrigen', fn (): array => [
                'id' => (int) $this->stockOrigen->id,
                'descripcion' => (string) $this->stockOrigen->descripcion,
            ]),
            'stock_para' => $this->whenLoaded('stockDestino', fn (): array => [
                'id' => (int) $this->stockDestino->id,
                'descripcion' => (string) $this->stockDestino->descripcion,
            ]),
            'usuario_envia' => $this->whenLoaded('usuarioEnvia', fn (): array => [
                'id' => (int) $this->usuarioEnvia->id,
                'nombre' => (string) ($this->usuarioEnvia->nombre ?? $this->usuarioEnvia->name ?? ''),
            ]),
            'usuario_recibe' => $this->whenLoaded('usuarioRecibe', fn (): ?array => $this->usuarioRecibe === null ? null : [
                'id' => (int) $this->usuarioRecibe->id,
                'nombre' => (string) ($this->usuarioRecibe->nombre ?? $this->usuarioRecibe->name ?? ''),
            ]),
            'detalles' => TransferDetailResource::collection($this->whenLoaded('detalles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
