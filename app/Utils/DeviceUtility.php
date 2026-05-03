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

    public function getSingleInfo(Request  $request)
    {
        $info = $this->getIpAndDeviceName($request);
        return [
            'ip' => $this->hashService->genHash($info['ip']),
            'name' => $this->hashService->genHash($info['name']),
        ];
    }

    private function getIpAndDeviceName(Request $request): array
    {
        if ($request->hasHeader('X-Device-Ip') && $request->hasHeader('X-Device-Name')) {
            return [
                'ip' => $request->header('X-Device-Ip'),
                'name' => $request->header('X-Device-Name'),
            ];
        }
        return [];
    }
}
