<?php

namespace App\Models\Concerns;

use App\Models\Device;
use App\Models\Farma\Distribucion;
use App\Models\Farma\Producto;
use App\Models\Farma\Transferencia;
use App\Models\Farma\TransferenciaDetalle;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Support\Str;

trait PublishesHeadquarters
{
    public function save(array $options = [])
    {
        if ($this->getTable() === 'devices') {
            if (! $this->exists) {
                $this->hq_client_uuid ??= (string) Str::uuid();
            } elseif ($this->isDirty('hq_client_uuid')) {
                throw new \LogicException('La identidad hq-client del dispositivo no puede modificarse.');
            }
        }
        $recorder = app(ChangeRecorder::class);
        if (! $recorder->enabled()) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options, $recorder) {
            $new = ! $this->exists;
            $dirty = $this->isDirty();
            $oldProduct = null;
            if (! $new) {
                $locked = $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $oldProduct = $locked->getAttribute($this->getTable() === 'trasferencia_detalle' ? 'transferencia_id' : 'producto');
            }
            $saved = parent::save($options);
            if ($saved && ($new || $dirty)) {
                $this->publishHeadquarters($recorder, $new ? 'CREATE' : 'UPDATE', $oldProduct);
            }

            return $saved;
        }, 3);
    }

    public function delete()
    {
        $recorder = app(ChangeRecorder::class);
        if (! $recorder->enabled() || ! $this->exists) {
            return parent::delete();
        }

        return $this->getConnection()->transaction(function () use ($recorder) {
            $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $distributions = $this->getTable() === 'lotes' ? Distribucion::where('lote', $this->getKey())->get() : collect();
            $deleted = parent::delete();
            if ($deleted) {
                foreach ($distributions as $distribution) {
                    $recorder->record($distribution, 'DELETE');
                }
                $this->publishHeadquarters($recorder, 'DELETE');
            }

            return $deleted;
        }, 3);
    }

    private function publishHeadquarters(ChangeRecorder $recorder, string $action, mixed $oldProduct = null): void
    {
        if ($this->getTable() === 'device_estado') {
            foreach (Device::where('estado', $this->getKey())->orderBy('id')->lockForUpdate()->get() as $device) {
                $recorder->record($device, 'UPDATE');
            }

            return;
        }
        if ($this->getTable() === 'lotes') {
            $recorder->record($this, $action);
            foreach (Distribucion::where('lote', $this->getKey())->orderBy('id')->lockForUpdate()->get() as $distribution) {
                $recorder->record($distribution, 'UPDATE');
            }
            $ids = TransferenciaDetalle::where('lote', $this->getKey())->distinct()->orderBy('transferencia_id')->pluck('transferencia_id');
            foreach ($ids as $id) {
                $transfer = Transferencia::whereKey($id)->lockForUpdate()->first();
                if ($transfer) {
                    $recorder->record($transfer, 'UPDATE');
                }
            }

            return;
        }
        if ($this->getTable() === 'trasferencia_detalle') {
            $ids = array_unique(array_filter([$oldProduct, $this->transferencia_id]));
            sort($ids);
            foreach ($ids as $id) {
                $transfer = Transferencia::whereKey($id)->lockForUpdate()->first();
                if ($transfer) {
                    $recorder->record($transfer, 'UPDATE');
                }
            }

            return;
        }
        if ($this->getTable() !== 'prod_activo') {
            $recorder->record($this, $action);

            return;
        }
        $ids = array_unique(array_filter([$oldProduct, $this->producto]));
        foreach ($ids as $id) {
            $product = Producto::whereKey($id)->lockForUpdate()->first();
            if ($product) {
                $recorder->record($product, 'UPDATE');
            }
        }
    }
}
