<?php

namespace App\Http\Controllers;

use App\Http\Requests\VersionRequest;
use App\Interfaces\Config\AppConfigService;

class AppConfigController extends Controller
{
    public function __construct(private AppConfigService $appConfigService) {}

    public function checkVersion(VersionRequest $request)
    {
        $exists = $this->appConfigService->existVersion($request->version);

        return $this->sendResponse($exists, $exists ? 'Version exists' : 'Version does not exist');
    }
}
