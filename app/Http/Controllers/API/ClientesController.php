<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientesResource;
use App\Models\Clientes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{

    public function index()
    {
        //last 20 clientes
        $clientes = Clientes::orderBy('created_at', 'desc')->take(20)->get();
        if ($clientes) {
            return $this->sendResponse(ClientesResource::collection($clientes), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'docid' => 'required|string|max:20|unique:clientes,docid',
            'pnombre' => 'required|string|max:50',
            'snombre' => 'required|string|max:50',
            'papellido' => 'required|string|max:50',
            'spaellido' => 'required|string|max:50',
            'edad' => 'required|integer',
            'telefono' => 'required|string|unique:clientes,telefono|max:10',
            'genero' => 'required|string|max:1',
            'municipio.id' => 'required|integer|exists:municipios,id',
            'departamento.id' => 'required|integer|exists:departamentos,id',
        ]);

        if (Clientes::where('docid', $request->docid)->exists()) {
            return $this->sendResponse(null, 'El numero de identidad ya existe', 422);
        }



        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors()->first(), 422);
        }
        $input = $request->all();
        $input['municipio'] = $input['municipio']['id'];
        $input['departamento'] = $input['departamento']['id'];
        $cliente = Clientes::create($input);
        if ($cliente) {
            return $this->sendResponse(null, 'Cliente creado');
        }
        return $this->sendResponse(null, 'No se pudo crear la informacion', 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cliente = Clientes::find($id);
        if ($cliente) {
            return $this->sendResponse(ClientesResource::make($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validate = Validator::make($request->all(), [
            'docid' => "required|string|max:20|unique:clientes,docid,$id",
            'pnombre' => 'required|string|max:50',
            'snombre' => 'required|string|max:50',
            'papellido' => 'required|string max:50',
            'spaellido' => 'required|string|max:50',
            'edad' => 'required|integer|min:1|max:2',
            'telefono' => "required|string|unique:clientes,telefono,$id|max:10",
            'genero' => 'required|string|max:1',
            'municipio.id' => 'required|integer|exists:municipios,id',
            'departamento.id' => 'required|integer|exists:departamentos,id',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors()->first(), 422);
        }
        $input = $request->all();
        $input['municipio'] = $input['municipio']['id'];
        $input['departamento'] = $input['departamento']['id'];
        $cliente = Clientes::find($id);
        if ($cliente) {
            if ($cliente->update($input)) {
                return $this->sendResponse(null, 'Cliente actualizado');
            }
            return $this->sendResponse(null, 'No se pudo actualizar la informacion', 500);
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cliente = Clientes::find($id);
        if ($cliente) {
            if ($cliente->delete()) {
                return $this->sendResponse(null, 'Cliente eliminado');
            }
            return $this->sendResponse(null, 'No se pudo eliminar la informacion', 500);
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    //filter by docid like 
    public function filterbydocid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|string|in:docid',
            'filterItem.*.value' => 'required|string|max:20',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if ($request->filterItem[0]['key'] != 'docid') {
                $validator->errors()->add('filterItem', 'El filtro solo permite docid');
            }
            if (strlen($request->filterItem[0]['value']) < 5) {
                $validator->errors()->add('filterItem', 'El filtro debe tener al menos 5 caracteres');
            }
            if (strlen($request->filterItem[0]['value']) > 20) {
                $validator->errors()->add('filterItem', 'El filtro no puede tener mas de 20 caracteres');
            }
        });

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where('docid', 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
    public function filterbyname(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|string|in:pnombre,snombre,papellido,spaellido',
            'filterItem.*.value' => 'required|string|max:50',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if (strlen($request->filterItem[0]['value']) < 3) {
                $validator->errors()->add('filterItem', 'El filtro debe tener al menos 3 caracteres');
            }
            if (strlen($request->filterItem[0]['value']) > 50) {
                $validator->errors()->add('filterItem', 'El filtro no puede tener mas de 50 caracteres');
            }
        });
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where($request->filterItem[0]['key'], 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
    public function filterbyphone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|string|in:telefono',
            'filterItem.*.value' => 'required|string|max:10',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if (strlen($request->filterItem[0]['value']) < 8) {
                $validator->errors()->add('filterItem', 'El filtro debe tener al menos 8 caracteres');
            }
            if (strlen($request->filterItem[0]['value']) > 10) {
                $validator->errors()->add('filterItem', 'El filtro no puede tener mas de 10 caracteres');
            }
        });
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where($request->filterItem[0]['key'], 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
    public function filterbydepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|int|in:departamento',
            'filterItem.*.value' => 'required|int|exists:departamentos,id',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if (!is_numeric($request->filterItem[0]['value'])) {
                $validator->errors()->add('filterItem', 'El filtro debe ser un numero');
            }
            if (!is_int($request->filterItem[0]['value'])) {
                $validator->errors()->add('filterItem', 'El filtro debe ser un numero entero');
            }
        });
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where($request->filterItem[0]['key'], 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
    public function filterbymunicipio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|int|in:municipio',
            'filterItem.*.value' => 'required|int|exists:municipios,id',
        ])->after(function ($validator) use ($request) {

            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if (!is_numeric($request->filterItem[0]['value'])) {
                $validator->errors()->add('filterItem', 'El filtro debe ser un numero');
            }
            if (!is_int($request->filterItem[0]['value'])) {
                $validator->errors()->add('filterItem', 'El filtro debe ser un numero entero');
            }
        });
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where($request->filterItem[0]['key'], 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
    public function filterbygender(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|string|in:genero',
            'filterItem.*.value' => 'required|string|max:1',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if (strlen($request->filterItem[0]['value']) < 1) {
                $validator->errors()->add('filterItem', 'El filtro debe tener al menos 1 caracteres');
            }
            if (strlen($request->filterItem[0]['value']) > 1) {
                $validator->errors()->add('filterItem', 'El filtro no puede tener mas de 1 caracteres');
            }
        });
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where($request->filterItem[0]['key'], 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
    public function filterByAge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' =>  'required|int|in:edad',
            'filterItem.*.value' => 'required|int|min:1|max:2',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 1) {
                $validator->errors()->add('filterItem', 'Solo se permite un filtro');
            }
            if (!is_numeric($request->filterItem[0]['value'])) {
                $validator->errors()->add('filterItem', 'El filtro debe ser un numero');
            }
            if (!is_int($request->filterItem[0]['value'])) {
                $validator->errors()->add('filterItem', 'El filtro debe ser un numero entero');
            }
        });
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }
        $cliente = Clientes::where($request->filterItem[0]['key'], 'like', "%{$request->filterItem[0]['value']}%")->get();
        if ($cliente) {
            return $this->sendResponse(ClientesResource::collection($cliente), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    public function lastclientes()
    {
        $clientes = Clientes::orderBy('created_at', 'desc')->take(20)->get();
        if ($clientes) {
            return $this->sendResponse(ClientesResource::collection($clientes), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    //use multiple filters


    public function filter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' => 'required',
            'filterItem.*.value' => 'required',
        ])->after(function ($validator) use ($request) {
            if (count($request->filterItem) > 10) {
                $validator->errors()->add('filterItem', 'Solo se permite un maximo de 10 filtros');
            }
            if (count($request->filterItem) < 1) {
                $validator->errors()->add('filterItem', 'Se requiere al menos un filtro');
            }
            foreach ($request->filterItem as $item) {
                if (!is_string($item['key'])) {
                    $validator->errors()->add('filterItem', 'El filtro debe ser una cadena');
                }
                if (!is_string($item['value'])) {
                    $validator->errors()->add('filterItem', 'El valor del filtro debe ser una cadena');
                }
            }
           
        });

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }

        $query = Clientes::query();

        if (count($request->filterItem) === 1) {

            $searchValue = $request->filterItem[0]['value'];

            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('docid', 'like', "%$searchValue%")
                    ->orWhere('pnombre', 'like', "%$searchValue%")
                    ->orWhere('snombre', 'like', "%$searchValue%");
            });
        } else {

            $filters = collect($request->filterItem)->groupBy(fn($item) => explode('.', $item['key'])[0]);

            foreach ($filters as $column => $filterGroup) {
                $start = $filterGroup->firstWhere('key', "$column.start");
                $end = $filterGroup->firstWhere('key', "$column.end");

                if ($start && $end) {
                    $query->whereBetween($column, [
                        Carbon::parse($start['value'])->startOfDay(),
                        Carbon::parse($end['value'])->endOfDay(),
                    ]);
                } else {
                    foreach ($filterGroup as $filter) {
                        $key = $filter['key'];
                        $value = $filter['value'];

                        switch ($column) {
                            case 'docid':
                            case 'pnombre':
                            case 'snombre':
                            case 'telefono':
                            case 'genero':
                                $query->where(function ($q) use ($value) {
                                    $q->orWhere('docid', 'like', "%$value%")
                                        ->orWhere('pnombre', 'like', "%$value%")
                                        ->orWhere('snombre', 'like', "%$value%");
                                });
                                break;

                            case 'edad':
                                if (is_array($value) && count($value) === 2) {
                                    $query->whereBetween($column, [$value[0], $value[1]]);
                                } else {
                                    $query->where($column, $value);
                                }
                                break;

                            case 'departamento':
                            case 'municipio':
                                $query->where($column, $value);
                                break;

                            case 'created_at':
                                if (is_array($value) && count($value) === 2) {
                                    $query->whereBetween($column, [
                                        Carbon::parse($value[0])->startOfDay(),
                                        Carbon::parse($value[1])->endOfDay(),
                                    ]);
                                } else {
                                    $query->whereDate($column, Carbon::parse($value)->toDateString());
                                }
                                break;
                        }
                    }
                }
            }
        }

        $clientes = $query->get();

        if ($clientes->isEmpty()) {
            return $this->sendResponse(null, 'No se encontró información ', 404);
        }

        return $this->sendResponse(ClientesResource::collection($clientes), 'success');
    }
}
