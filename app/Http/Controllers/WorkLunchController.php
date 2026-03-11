<?php

namespace App\Http\Controllers;

use App\Interfaces\WorkLunch\WorkLunchService;
use App\Http\Resources\WorkLunchResource;
use App\Utils\DeviceUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkLunchController extends Controller
{
    public function __construct(
        private DeviceUtility $deviceUtility,
        private WorkLunchService $workLunchService
    ) {}

    public function index()
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function store(Request $request)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function show(string $id)
    {
        $obj = $this->workLunchService->findById((int) $id);
        if ($obj) {
            return $this->sendResponse(WorkLunchResource::make($obj), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    public function update(Request $request, string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function destroy(string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function work(Request $request)
    {
        try {
            $device = $this->deviceUtility->get_DeviceInfo($request);
            if (!$device) {
                return $this->sendResponse(null, 'Invalid Device!', 500);
            }

            $session = $this->workLunchService->workService(
                (int) Auth::id(),
                now()->format('Y-m-d H:i:s'),
                $device->id
            );
            $message = $session->wkend_time ? 'Salida Exitosa' : 'Registro Exitoso';

            return $this->sendResponse(null, $message, 200);
        } catch (\DomainException $e) {
            return $this->sendResponse(null, $e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            return $this->sendResponse(null, 'No se pudo registrar: ' . $e->getMessage(), 500);
        }
    }

    public function lunch(Request $request)
    {
        try {
            $device = $this->deviceUtility->get_DeviceInfo($request);
            if (!$device) {
                return $this->sendResponse(null, 'Invalid Device!', 500);
            }

            $this->workLunchService->lunchService(
                (int) Auth::id(),
                now()->format('Y-m-d H:i:s')
            );

            return $this->sendResponse(null, 'Registro Exitoso', 200);
        } catch (\DomainException $e) {
            return $this->sendResponse(null, $e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            return $this->sendResponse(null, 'No se pudo registrar: ' . $e->getMessage(), 500);
        }
    }
}
