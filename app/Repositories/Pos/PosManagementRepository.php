<?php

namespace App\Repositories\Pos;

use App\DTO\PosManagementDTO;
use App\Models\Pos\CatalogoPos;
use App\Models\Pos\RolPos;
use App\Models\Pos\UsuarioPos;
use App\Services\Headquarters\PosLocalCredentials;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosManagementRepository
{
    public function model(string $kind): Model
    {
        return match ($kind) {
            'usuarios' => new UsuarioPos, 'roles' => new RolPos, 'catalogos' => new CatalogoPos
        };
    }

    public function paginate(string $kind): LengthAwarePaginator
    {
        $query = $this->model($kind)->newQuery();
        if ($kind === 'catalogos') {
            $query->where('tipo', '!=', 'DESCUENTO');
        }
        if ($kind === 'usuarios') {
            $query->with(['rol:id,nombre,nivel']);
        }

        return $query->orderBy('id')->paginate(30);
    }

    public function find(string $kind, int $id): ?Model
    {
        $query = $this->model($kind)->newQuery();
        if ($kind === 'catalogos') {
            $query->where('tipo', '!=', 'DESCUENTO');
        }
        if ($kind === 'usuarios') {
            $query->with(['rol:id,nombre,nivel']);
        }

        return $query->find($id);
    }

    public function save(string $kind, ?int $id, PosManagementDTO $dto): ?Model
    {
        return DB::transaction(function () use ($kind, $id, $dto): ?Model {
            $model = $id === null ? $this->model($kind) : $this->model($kind)->newQuery()->lockForUpdate()->find($id);
            if ($model === null) {
                return null;
            }
            $data = $dto->data;
            if ($kind === 'catalogos' && $model->tipo === 'DESCUENTO') {
                return null;
            }
            if (isset($data['nombre']) && $data['nombre'] === '') {
                throw ValidationException::withMessages(['nombre' => 'El nombre es obligatorio.']);
            }
            if (array_key_exists('vendedor', $data) && $data['vendedor'] === '') {
                throw ValidationException::withMessages(['vendedor' => 'El vendedor es obligatorio.']);
            }
            if ($kind === 'roles') {
                $model->fill($data);
                $model->save();

                return $model;
            }
            if ($kind === 'usuarios') {
                $credentials = $model->exists ? $model->credenciales : [];
                $username = $data['usuario'] ?? base64_decode($credentials['usuario'], true);
                $lookup = hash('sha256', $username);
                if (UsuarioPos::where('usuario_lookup', $lookup)->when($id !== null, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                    throw ValidationException::withMessages(['usuario' => 'El usuario POS ya está registrado.']);
                }
                $credentials = PosLocalCredentials::create($username, $data['password'] ?? null, $credentials);
                unset($data['usuario'], $data['password']);
                $model->fill($data + ['usuario_lookup' => $lookup, 'credenciales' => $credentials]);
            }
            if ($kind === 'catalogos') {
                if ($model->exists && ($model->tipo !== $data['tipo'] || $model->stock_id !== $data['stock_id'])) {
                    throw ValidationException::withMessages(['tipo' => 'El tipo y stock de un catálogo existente se conservan.']);
                }
                if ($data['tipo'] === 'PRECIO' && ! DB::table('lotes')->where('id', $data['datos']['lote_id'])->where('producto', $data['datos']['producto_id'])->exists()) {
                    throw ValidationException::withMessages(['datos.lote_id' => 'El lote debe pertenecer al producto indicado.']);
                }
                $identity = match ($data['tipo']) {
                    'IMPUESTO', 'MEDIO_PAGO' => ['codigo'], 'UNIDAD' => ['producto_id', 'abreviatura'],
                    'POLITICA' => ['producto_id'], 'PRECIO' => ['producto_id', 'lote_id', 'unidad_id'],
                };
                foreach ($identity as $field) {
                    if ($model->exists && (string) $model->datos[$field] !== (string) $data['datos'][$field]) {
                        throw ValidationException::withMessages(['datos.'.$field => 'La identidad de un catálogo existente se conserva. Desactívalo y crea otro registro.']);
                    }
                }
                // Lock the stock to serialize natural identity allocation, including inactive records.
                DB::table('stocks')->where('id', $data['stock_id'])->lockForUpdate()->first();
                $duplicate = CatalogoPos::where('stock_id', $data['stock_id'])->where('tipo', $data['tipo'])
                    ->when($id !== null, fn ($q) => $q->where('id', '!=', $id));
                foreach ($identity as $field) {
                    $duplicate->where('datos->'.$field, $data['datos'][$field]);
                }
                if ($duplicate->exists() || CatalogoPos::where('stock_id', $data['stock_id'])->where('tipo', $data['tipo'])
                    ->where('codigo', $data['codigo'])->when($id !== null, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                    throw ValidationException::withMessages(['codigo' => 'La identidad o código de este catálogo ya existe en el stock.']);
                }
                foreach (['impuesto_id' => 'IMPUESTO', 'unidad_id' => 'UNIDAD'] as $field => $type) {
                    if (isset($data['datos'][$field])) {
                        $parent = CatalogoPos::whereKey($data['datos'][$field])->where('stock_id', $data['stock_id'])->where('tipo', $type)->where('activo', true)->first();
                        if ($parent === null || ($field === 'unidad_id' && $parent->datos['producto_id'] != $data['datos']['producto_id'])) {
                            throw ValidationException::withMessages(['datos.'.$field => 'La referencia debe pertenecer al tipo, stock y producto correspondientes.']);
                        }
                    }
                }
                if ($data['tipo'] === 'IMPUESTO' && $data['datos']['tratamiento'] !== 'GRAVADO' && (float) $data['datos']['tasa'] !== 0.0) {
                    throw ValidationException::withMessages(['datos.tasa' => 'Exento y exonerado requieren tasa cero.']);
                }
            }
            if ($kind !== 'usuarios') {
                $model->fill($data);
            }
            $model->save();

            return $model;
        }, 3);
    }

    public function deactivate(string $kind, int $id): bool
    {
        return DB::transaction(function () use ($kind, $id): bool {
            $model = $this->model($kind)->newQuery()->lockForUpdate()->find($id);

            return $model !== null && ! ($kind === 'catalogos' && $model->tipo === 'DESCUENTO') && $model->update(['activo' => false]);
        });
    }
}
