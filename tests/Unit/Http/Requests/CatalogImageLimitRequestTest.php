<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Farma\Laboratorios\LaboratorioRequest;
use App\Http\Requests\Farma\Laboratorios\LaboratorioUpdateRequest;
use App\Http\Requests\Farma\Proveedores\ProveedoresRequest;
use App\Http\Requests\Farma\Proveedores\ProveedoresUpdateRequest;
use PHPUnit\Framework\TestCase;

class CatalogImageLimitRequestTest extends TestCase
{
    public function test_provider_and_laboratory_requests_allow_two_megabytes_of_base64(): void
    {
        foreach ([
            new ProveedoresRequest,
            new ProveedoresUpdateRequest,
            new LaboratorioRequest,
            new LaboratorioUpdateRequest,
        ] as $request) {
            $this->assertContains('max:2796204', $request->rules()['imagen']);
            $this->assertStringContainsString('2 MB', $request->messages()['imagen.max']);
        }
    }
}
