<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\HorasLab\HorasLabRequest;
use App\Http\Requests\HorasLab\HorasLabUpdateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class HorasLabRequestTest extends TestCase
{
    public function test_lunch_minutes_accept_only_values_between_1_and_160(): void
    {
        $requests = [
            new HorasLabRequest,
            new HorasLabUpdateRequest,
        ];

        foreach ($requests as $request) {
            foreach ([1, 60, 160] as $validMinutes) {
                $validator = Validator::make([
                    'horas_lab' => 8,
                    'horas_lunch' => $validMinutes,
                ], $request->rules());

                $this->assertFalse(
                    $validator->errors()->has('horas_lunch'),
                    "{$validMinutes} minutos deben ser válidos.",
                );
            }

            foreach ([0, 161] as $invalidMinutes) {
                $validator = Validator::make([
                    'horas_lab' => 8,
                    'horas_lunch' => $invalidMinutes,
                ], $request->rules());

                $this->assertTrue(
                    $validator->errors()->has('horas_lunch'),
                    "{$invalidMinutes} minutos deben ser rechazados.",
                );
            }
        }
    }
}
