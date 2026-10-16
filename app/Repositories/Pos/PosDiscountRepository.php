<?php

namespace App\Repositories\Pos;

use App\Models\Pos\CatalogoPos;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosDiscountRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return CatalogoPos::where('tipo', 'DESCUENTO')->orderBy('id')->paginate(30);
    }

    public function find(int $id): ?CatalogoPos
    {
        return CatalogoPos::where('tipo', 'DESCUENTO')->find($id);
    }

    public function write(string $requestId, int $user, string $operation, ?int $id, array $data): array
    {
        $digest = hash('sha256', json_encode([$user, $operation, $id, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($requestId, $user, $operation, $id, $data, $digest): array {
            // Serialize uncertain retries before inspecting the durable journal or allocating an identity.
            $control = DB::table('pos_descuento_control')->where('id', 1)->lockForUpdate()->first();
            if ($control === null) {
                throw new \LogicException('Falta el despliegue de descuentos.');
            }
            $saved = DB::table('pos_descuento_solicitudes')->where('solicitud_id', $requestId)->first();
            if ($saved !== null) {
                abort_unless(hash_equals($saved->huella, $digest) && (int) $saved->usuario_id === $user, 409, 'Reintenta con la solicitud original.');

                return json_decode($saved->respuesta, true, 512, JSON_THROW_ON_ERROR);
            }
            if ($operation === 'GUARDAR' && array_key_exists('stock_id', $data)) {
                throw ValidationException::withMessages(['stock_id' => 'Las reglas se aplican en todos los stocks. No selecciones un stock.']);
            }
            if ($operation === 'GUARDAR' && ! isset($data['regla'])) {
                throw ValidationException::withMessages(['form' => 'Selecciona una regla general para todos los descuentos.']);
            }
            $model = $id === null ? new CatalogoPos : CatalogoPos::where('tipo', 'DESCUENTO')->lockForUpdate()->findOrFail($id);
            $original = $model->datos ?? [];
            if ($id !== null && (int) ($original['revision'] ?? 0) !== (int) $data['revision_esperada']) {
                throw ValidationException::withMessages(['form' => 'La regla cambió. Vuelve a abrirla antes de guardar.']);
            }
            if ($operation === 'ELIMINAR') {
                $response = $model->attributesToArray();
                $model->delete();
            } elseif ($operation === 'DESACTIVAR') {
                $model->activo = false;
                $model->datos = array_replace($original, ['revision' => (int) $original['revision'] + 1]);
            } else {
                $data['referencia_id'] = (string) (int) $data['referencia_id'];
                if ($id !== null && ($original['alcance'] !== $data['alcance'] || (string) $original['referencia_id'] !== (string) $data['referencia_id'])) {
                    throw ValidationException::withMessages(['form' => 'La categoría o producto se conserva al editar.']);
                }
                $referenceTable = $data['alcance'] === 'CATEGORIA' ? 'prod_categorias' : 'productos';
                $reference = DB::table($referenceTable)->where('id', $data['referencia_id'])->lockForUpdate()->first();
                if ($reference === null) {
                    throw ValidationException::withMessages(['referencia_id' => 'Selecciona una referencia del catálogo.']);
                }
                $code = strtolower($data['alcance']).'-'.$data['referencia_id'];
                if ($id === null && CatalogoPos::where('tipo', 'DESCUENTO')->where('codigo', $code)->exists()) {
                    throw ValidationException::withMessages(['referencia_id' => 'Ya existe esta regla global. Edita o reactiva la existente.']);
                }
                $model->fill(['stock_id' => null, 'tipo' => 'DESCUENTO', 'codigo' => $code, 'activo' => $data['activo'],
                    'datos' => ['version' => 2, 'alcance' => $data['alcance'], 'referencia_id' => (string) $data['referencia_id'],
                        'categoria_id' => $data['alcance'] === 'CATEGORIA' ? (string) $data['referencia_id'] : null,
                        'producto_id' => $data['alcance'] === 'PRODUCTO' ? (string) $data['referencia_id'] : null,
                        'descripcion' => $reference->nombre ?? $reference->descripcion,
                        'revision' => (int) ($original['revision'] ?? 0) + 1, 'regla' => $data['regla']]]);
            }
            if ($operation !== 'ELIMINAR') {
                $model->save();
                $response = $model->attributesToArray();
            }
            DB::table('pos_descuento_solicitudes')->insert(['solicitud_id' => $requestId, 'usuario_id' => $user,
                'huella' => $digest, 'respuesta' => json_encode($response, JSON_THROW_ON_ERROR), 'created_at' => now()]);

            return $response;
        }, 3);
    }
}
