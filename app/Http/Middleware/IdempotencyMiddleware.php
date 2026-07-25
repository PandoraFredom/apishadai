<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use App\Utils\DeviceUtility;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use function in_array;

class IdempotencyMiddleware
{
    private const string HEADER = 'Idempotency-Key';
    private const int TTL_HOURS = 24;

    public function __construct(private DeviceUtility $deviceUtility) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldHandle($request)) {
            return $next($request);
        }

        $key = trim((string) $request->header(self::HEADER, ''));

        if ($key === '') {
            return $next($request);
        }

        if (mb_strlen($key) > 255) {
            return $this->json(null, 'Idempotency-Key demasiado largo', 400);
        }

        $userId = optional($request->user())->id;

        if (!$userId) {
            return $next($request);
        }

        $device = $this->deviceUtility->get_DeviceInfo($request);
        $requestHash = $this->requestHash($request);
        $route = $request->path();
        $record = $this->findOrCreateRecord($request, $key, $requestHash, $route, $userId, $device?->id);

        if ($record->request_hash !== $requestHash || $record->method !== $request->method() || $record->route !== $route) {
            return $this->json(null, 'Idempotency-Key ya fue usada con otra solicitud', 409);
        }

        if ($record->status === 'completed') {
            return response()->json($record->response_body, (int) $record->response_code);
        }

        if (!$record->wasRecentlyCreated && $record->status === 'processing') {
            return $this->json(null, 'Solicitud en proceso', 409);
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed']);
            throw $e;
        }

        $this->storeResponse($record, $response);

        return $response;
    }

    private function shouldHandle(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function findOrCreateRecord(
        Request $request,
        string $key,
        string $requestHash,
        string $route,
        int $userId,
        ?int $deviceId
    ): IdempotencyKey {
        try {
            return DB::transaction(function () use ($request, $key, $requestHash, $route, $userId, $deviceId): IdempotencyKey|stdClass {
                $existing = IdempotencyKey::query()
                    ->where('user_id', $userId)
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                return IdempotencyKey::query()->create([
                    'user_id' => $userId,
                    'device_id' => $deviceId,
                    'key' => $key,
                    'method' => $request->method(),
                    'route' => $route,
                    'request_hash' => $requestHash,
                    'status' => 'processing',
                    'expires_at' => now()->addHours(self::TTL_HOURS),
                ]);
            }, 3);
        } catch (QueryException $e) {
            return IdempotencyKey::query()
                ->where('user_id', $userId)
                ->where('key', $key)
                ->firstOrFail();
        }
    }

    private function requestHash(Request $request): string
    {
        $payload = [
            'method' => $request->method(),
            'path' => $request->path(),
            'body' => $request->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function storeResponse(IdempotencyKey $record, Response $response): void
    {
        $body = $response instanceof JsonResponse
            ? $response->getData(true)
            : ['message' => 'Respuesta no JSON no almacenada', 'code' => $response->getStatusCode(), 'data' => null];

        $record->update([
            'status' => 'completed',
            'response_code' => $response->getStatusCode(),
            'response_body' => $body,
        ]);
    }

    /**
     * Summary of json
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    private function json($result, string $message, int $code): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $result,
        ], $code);
    }
}
