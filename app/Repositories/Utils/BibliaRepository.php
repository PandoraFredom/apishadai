<?php

namespace App\Repositories\Utils;

use App\DTO\Utils\BibliaDTO;
use App\Interfaces\Utils\BibliaService;
use Illuminate\Support\Facades\Http;
use LogicException;
use UnexpectedValueException;
use function is_array;

final class BibliaRepository implements BibliaService
{
    public function getRandom(): BibliaDTO
    {
        $url = trim((string) config('services.biblia.url'));

        if ($url === '') {
            throw new LogicException('BIBLE_API no esta configurada.');
        }

        $payload = Http::withOptions(['verify' => $this->resolveCaBundle()])
            ->acceptJson()
            ->connectTimeout((int) config('services.biblia.connect_timeout', 5))
            ->timeout((int) config('services.biblia.timeout', 10))
            ->get($url)
            ->throw()
            ->json();

        if (! is_array($payload)) {
            throw new UnexpectedValueException('BIBLE_API no retorno un objeto JSON.');
        }

        return BibliaDTO::fromApiResponse($payload);
    }

    private function resolveCaBundle(): bool|string
    {
        $caBundle = trim((string) config('services.biblia.ca_bundle'));

        if ($caBundle === '') {
            return true;
        }

        if (! is_file($caBundle) || ! is_readable($caBundle)) {
            throw new LogicException('BIBLE_API_CA_BUNDLE no apunta a un archivo CA legible.');
        }

        return $caBundle;
    }
}
