<?php

namespace App\Http\Requests\Device;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;


class DeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ip' => 'required|string|max:100|unique:devices,ip',
            'ip2' => 'required|string|max:100|unique:devices,ip2',
            'displayname' => 'required|string|max:60|unique:devices,displayname',
            'name' => 'required|string|max:100|unique:devices,name',
            'stock.id' => 'required|integer|exists:stocks,id',
            'estado.id' => 'required|integer|exists:device_estado,id',
        ];
    }


    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // IP
            'ip.required' => 'El campo IP es obligatorio.',
            'ip.string' => 'El campo IP debe ser una cadena de texto.',
            'ip.max' => 'El campo IP no debe exceder 100 caracteres.',
            'ip.unique' => 'La IP ya está en uso.',
            // IP2
            'ip2.required' => 'El campo IP2 es obligatorio.',
            'ip2.string' => 'El campo IP2 debe ser una cadena de texto.',
            'ip2.max' => 'El campo IP2 no debe exceder 100 caracteres.',
            'ip2.unique' => 'La IP2 ya está en uso.',
            // Displayname
            'displayname.required' => 'El nombre mostrado es obligatorio.',
            'displayname.string' => 'El nombre mostrado debe ser una cadena de texto.',
            'displayname.max' => 'El nombre mostrado no debe exceder 60 caracteres.',
            'displayname.unique' => 'El nombre mostrado ya está en uso.',

            // Name
            'name.required' => 'El campo Nombre es obligatorio.',
            'name.string' => 'El campo Nombre debe ser una cadena de texto.',
            'name.max' => 'El campo Nombre no debe exceder 100 caracteres.',
            'name.unique' => 'El nombre ya está en uso.',
            // Stock
            'stock.id.required' => 'El stock es obligatorio.',
            'stock.id.integer' => 'El stock debe ser un número entero.',
            'stock.id.exists' => 'El stock seleccionado no existe.',
            // Estado
            'estado.id.required' => 'El estado es obligatorio.',
            'estado.id.integer' => 'El estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no existe.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
            // Estado
            'estado.id.required' => 'El estado es obligatorio.',
            'estado.id.integer' => 'El estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no existe.',
        ];
    }

    /**
     * Summary of failedValidation
     * @param Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function failedValidation(Validator $validator)
    {
        $response = [
            'message' => $validator->errors()->first(),
            'code' => 422,
            'data' => false,
        ];

        return response()->json($response, 422);
    }
}
