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
            'work_date' => $this->work_date,
            'wkstart_time' => $this->wkstart_time,
            'wkend_time' => $this->wkend_time,
            'lunch_start_time' => $this->lunch_start_time,
            'lunch_end_time' => $this->lunch_end_time,
            'work_status' => $this->wkend_time
                ? 'completed'
                : ($this->wkstart_time ? 'working' : 'not_started'),
            'lunch_status' => $this->lunch_end_time
                ? 'completed'
                : ($this->lunch_start_time ? 'on_lunch' : 'not_started'),
        ];
    }
}
