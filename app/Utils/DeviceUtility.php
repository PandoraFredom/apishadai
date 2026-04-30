<?php

namespace App\Utils;

use App\Interfaces\Config\DeviceService;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceUtility
{
    public function __construct(private DeviceService $deviceService, private EncryptionService $encryptionService) {}


    public function get_DeviceInfo(Request $request)
    {

        $info = $this->getIpAndDeviceName($request);

            if (empty($info)) {
                return null;
            }
        $device = $this->deviceService->whereFirst([
            'ip' => $this->encryptionService->genHash($info['ip']),
            'name' => $this->encryptionService->genHash($info['name']),
        ]);
    // Log::info("Device Info - IP: {$this->encryptionService->genHash($info['ip'])}, IP2: {$this->encryptionService->genHash($request->ip())}, Name: {$this->encryptionService->genHash($info['name'])}");
        return $device;
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
