<?php

namespace App\Utils;

use App\Interfaces\Config\DeviceService;


class DeviceUtility
{
    public function __construct(private DeviceService $deviceService) {}


    public function get_DeviceInfo( $request)
    {

        $info = $this->getIpAndDeviceName($request);

            if (empty($info)) {
                return null;
            }
        $device = $this->deviceService->whereFirst([
            'ip' => $this->genHash($info['ip']),
            'name' => $this->genHash($info['name']),
        ]);
    // Log::info("Device Info - IP: {$this->genHash($info['ip'])}, IP2: {$this->genHash($request->ip())}, Name: {$this->genHash($info['name'])}");
        return $device;
    }

    private function getIpAndDeviceName( $request): array
    {
        if ($request->hasHeader('X-Device-Ip') && $request->hasHeader('X-Device-Name')) {
            return [
                'ip' => $request->header('X-Device-Ip'),
                'name' => $request->header('X-Device-Name'),
            ];
        }
        return [];
    }

    private function genHash($var): string
    {
        $key = config('app.key') ?? env('APP_KEY');
        return hash_hmac('sha256', $var, $key);
    }
}
