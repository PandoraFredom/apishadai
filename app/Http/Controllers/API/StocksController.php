<?php

namespace App\Http\Controllers\API;

use App\DTOs\StockDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest;
use App\Http\Resources\StocksResource;
use App\Interfaces\Config\StockRepositoryInterface;
use Illuminate\Http\Request;


class StocksController extends Controller
{

    public function __construct(private StockRepositoryInterface $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = $this->service->getAll();
        if (!$list) {
            return $this->sendResponse(null, 'No data found', 404);
        }
        return $this->sendResponse(StocksResource::collection($list), 'ok', 200, false);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockRequest $request)
    {
        $dto = StockDTO::fromRequest($request);
        $created = $this->service->create($dto->toArray());
        if (!$created) {
            return $this->sendResponse(false, 'Error creating stock', 500);
        }
        return $this->sendResponse(true, 'Stock created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = $this->service->findById($id);
        if (!$item) {
            return $this->sendResponse(null, 'Stock not found', 404);
        }
        return $this->sendResponse(StocksResource::make($item), 'ok');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $dto = StockDTO::fromRequest($request);
        $updated = $this->service->update($dto->id, $dto->toArray());
        if (!$updated) {
            return $this->sendResponse(false, 'Error updating stock', 500);
        }
        return $this->sendResponse(true, 'Stock updated successfully', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = $this->service->delete($id);
        if (!$deleted) {
            return $this->sendResponse(false, 'Error deleting stock', 500);
        }
        return $this->sendResponse(true, 'Stock deleted successfully', 200);
    }
}
