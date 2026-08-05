<?php

namespace App\Http\Requests\HorasLab;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HorasLabUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:horas_lab,id',
            'horas_lab' => 'required|integer|min:1|max:24',
            'horas_lunch' => 'required|integer|min:1|max:160',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El id de la configuración es obligatorio.',
            'id.integer' => 'El id de la configuración debe ser un número entero.',
            'id.exists' => 'La configuración de horas seleccionada no existe.',
            'horas_lab.required' => 'Las horas laborales son obligatorias.',
            'horas_lab.integer' => 'Las horas laborales deben ser un número entero.',
            'horas_lab.min' => 'Las horas laborales deben ser al menos 1.',
            'horas_lab.max' => 'Las horas laborales no pueden exceder 24.',
            'horas_lunch.required' => 'Los minutos de almuerzo son obligatorios.',
            'horas_lunch.integer' => 'Los minutos de almuerzo deben ser un número entero.',
            'horas_lunch.min' => 'Los minutos de almuerzo deben ser al menos 1.',
            'horas_lunch.max' => 'Los minutos de almuerzo no pueden exceder 160.',
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
