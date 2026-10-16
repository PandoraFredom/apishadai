<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class PosManagementRequest extends BaseRequest
{
    public const PERMISSIONS = ['configurar', 'usuarios.administrar', 'caja.operar', 'caja.cerrar', 'inventario.operar',
        'credito.cobrar', 'factura.emitir', 'factura.anular', 'devolucion.operar', 'precio.modificar', 'transferencia.ver', 'transferencia.enviar', 'transferencia.recibir', 'transferencia.anular', 'transferencia.imprimir', 'transferencia.enviadas.ver', 'transferencia.recibidas.ver', 'transferencia.enviadas.filtrar', 'transferencia.recibidas.filtrar', 'transferencia.enviadas.enviar', 'transferencia.enviadas.anular', 'transferencia.enviadas.imprimir', 'transferencia.recibidas.recibir'];

    public function rules(): array
    {
        $kind = $this->route('resource');
        $rules = ['activo' => ['required', 'boolean']];
        if ($kind === 'roles') {
            return $rules + ['nombre' => ['required', 'string', 'max:80', Rule::unique('rolespos')->ignore($this->route('id'))],
                'nivel' => ['required', 'integer', 'between:0,1000'], 'permisos' => ['missing'], 'botones' => ['missing']];
        }
        if ($kind === 'usuarios') {
            return $rules + ['stock_id' => ['missing'], 'nombre' => ['required', 'string', 'max:200'], 'vendedor' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:200'], 'rol_id' => ['required', 'integer', 'exists:rolespos,id'],
                'usuario' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:200', 'regex:/\A[a-zA-Z0-9._@-]+\z/'],
                'password' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'min:6', 'max:1024']];
        }
        $rules['stock_id'] = ['required', 'integer', 'exists:stocks,id'];
        $rules += ['tipo' => ['required', Rule::in(['IMPUESTO', 'MEDIO_PAGO', 'UNIDAD', 'POLITICA', 'PRECIO'])],
            'codigo' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z0-9._-]+\z/'], 'datos' => ['required', 'array']];
        $fields = match ($this->input('tipo')) {
            'IMPUESTO' => ['codigo' => 'required|string|max:30', 'descripcion' => 'required|string|max:200', 'tasa' => 'required|numeric|between:0,100|decimal:0,4',
                'tratamiento' => 'required|in:GRAVADO,EXENTO,EXONERADO', 'vigente_desde' => 'required|date_format:Y-m-d', 'vigente_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:datos.vigente_desde'],
            'MEDIO_PAGO' => ['codigo' => 'required|string|max:30', 'descripcion' => 'required|string|max:100', 'es_efectivo' => 'required|boolean', 'requiere_referencia' => 'required|boolean'],
            'UNIDAD' => ['producto_id' => 'required|integer|min:1|exists:productos,id', 'abreviatura' => 'required|string|max:30', 'factor_inventario' => 'required|numeric|gt:0|max:999999999999999|decimal:0,4'],
            'POLITICA' => ['producto_id' => 'required|integer|min:1|exists:productos,id', 'impuesto_id' => 'required|integer|exists:catalogospos,id', 'unidad_id' => 'required|integer|exists:catalogospos,id',
                'controla_existencia' => 'required|boolean', 'permite_venta' => 'required|boolean', 'descuento_maximo' => 'required|numeric|between:0,100|decimal:0,4', 'permite_precio_manual' => 'required|boolean'],
            'PRECIO' => ['producto_id' => 'required|integer|min:1|exists:productos,id', 'lote_id' => 'required|integer|min:1|exists:lotes,id', 'unidad_id' => 'required|integer|exists:catalogospos,id', 'precio' => 'required|numeric|min:0|max:999999999999999|decimal:0,4', 'incluye_isv' => 'required|boolean'],
            default => [],
        };
        $rules['datos'] = ['required', 'array:'.implode(',', array_keys($fields))];
        foreach ($fields as $name => $rule) {
            $rules['datos.'.$name] = $rule;
        }

        return $rules;
    }
}
