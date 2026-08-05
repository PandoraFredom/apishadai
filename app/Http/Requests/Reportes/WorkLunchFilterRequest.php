<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WorkLunchFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => [
                'bail',
                'required',
                'integer',
                'exists:users,id',
            ],
            'work_date' => [
                'required',
                'array',
                'size:2',
            ],
            'work_date.0' => [
                'required',
                'date_format:Y-m-d',
            ],
            'work_date.1' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:work_date.0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.integer' => 'El usuario debe ser un número entero.',
            'usuario.exists' => 'El usuario seleccionado no existe.',
            'work_date.required' => 'El rango de fechas es obligatorio.',
            'work_date.array' => 'El rango de work_date debe ser un arreglo.',
            'work_date.size' => 'El rango de work_date debe contener la fecha inicial y final.',
            'work_date.0.required' => 'La fecha inicial es obligatoria.',
            'work_date.0.date_format' => 'La fecha inicial debe usar el formato Y-m-d.',
            'work_date.1.required' => 'La fecha final es obligatoria.',
            'work_date.1.date_format' => 'La fecha final debe usar el formato Y-m-d.',
            'work_date.1.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'code' => 400,
            'data' => null,
        ], 400));
    }
}
