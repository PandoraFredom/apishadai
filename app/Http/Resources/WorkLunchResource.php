<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkLunchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario' => $this->Usuario,
            'device' => $this->Device,
            'wkstart_time' => $this->wkstart_time,
            'wkend_time' => $this->wkend_time,
            'lunch_start_time' => $this->lunch_start_time,
            'lunch_end_time' => $this->lunch_end_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
