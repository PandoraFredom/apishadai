<?php

namespace App\Interfaces\Config;

use Illuminate\Http\Request;

interface DeviceInfoService
{
    public function get_DeviceInfo(Request $request);
    public function getSingleInfo(Request $request): ?array;
    public function authenticatedDeviceId(Request $request): ?int;
    public function stockId(mixed $device): ?int;
}
