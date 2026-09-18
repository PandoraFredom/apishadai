<?php

namespace App\Http\Requests\HorasLab;

use App\Http\Requests\BaseRequest;

class HorasLabRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'usuario.id' => 'required|integer|exists:users,id',
            'horas_lab' => 'required|integer|min:1|max:24',
            'horas_lunch' => 'required|integer|min:1|max:160',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'usuario.id.required' => 'El usuario es obligatorio.',
            'usuario.id.integer' => 'El usuario debe ser un número entero.',
            'usuario.id.exists' => 'El usuario seleccionado no existe.',
            'usuario.id.unique' => 'El usuario ya tiene una configuración de horas.',
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
}
