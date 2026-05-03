<?php

namespace App\Utils;

use App\Interfaces\Config\DeviceService;
use App\Utils\Services\SingleHashService;
use Illuminate\Http\Request;

class DeviceUtility
{
    public function __construct(
        private DeviceService $deviceService,
        private SingleHashService $hashService
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
