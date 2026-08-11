<?php

namespace App\Utils;

use App\Interfaces\Config\DeviceInfoService;
use App\Interfaces\Config\DeviceService;
use App\Models\Device;
use App\Utils\Services\SingleHashService;
use Illuminate\Http\Request;

class DeviceUtility implements DeviceInfoService
{
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly SingleHashService $hashService
    ) {}


    public function get_DeviceInfo(Request  $request)
    {

        $info = $this->getIpAndDeviceName($request);

        if (empty($info)) {
            return null;
        }
        $device = $this->deviceService->whereFirst([
            'ip' => $this->hashService->genHash($info['ip']),
            'name' => $this->hashService->genHash($info['name']),
        ]);

        return $device;
    }

    public function getSingleInfo(Request  $request): ?array
    {
        $info = $this->getIpAndDeviceName($request);

        if (empty($info)) {
            return null;
        }

        return [
            'ip' => $this->hashService->genHash($info['ip']),
            'name' => $this->hashService->genHash($info['name']),
        ];
    }

    public function authenticatedDeviceId(Request $request): ?int
    {
        $device = $request->attributes->get('authenticated_device') ?? $this->get_DeviceInfo($request);

        return $device instanceof Device ? (int) $device->getKey() : null;
    }

    public function stockId(mixed $device): ?int
    {
        return $device instanceof Device && (int) $device->stock > 0
            ? (int) $device->stock
            : null;
    }

    private function getIpAndDeviceName(Request $request): array
    {
        $ip = trim((string) $request->header('X-Device-Ip', ''));
        $name = trim((string) $request->header('X-Device-Name', ''));

        if ($ip === '' || $name === '') {
            return [];
        }

        return [
            'ip' => $ip,
            'name' => $name,
        ];
    }
}
