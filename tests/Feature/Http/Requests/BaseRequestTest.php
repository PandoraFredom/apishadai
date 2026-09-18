<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\VersionRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BaseRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('request_test_roles', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('request_test_members', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
        });
        DB::table('request_test_roles')->insert(['id' => 1]);
        DB::table('request_test_members')->insert(['email' => 'registrado@example.com']);

        Route::post('/api/request-test/member', [RequestTestController::class, 'store']);
        Route::post('/api/request-test/version', [RequestTestController::class, 'version']);
        Route::post('/api/request-test/login', [RequestTestController::class, 'login']);
        // Sin el renderer genérico de /api/* para comprobar la autorización existente.
        Route::post('/request-test/register', [RequestTestController::class, 'register']);
        Route::post('/request-test/update-user', [RequestTestController::class, 'updateUser']);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_required_field_uses_default_messages_without_custom_hooks(): void
    {
        $this->assertValidationResponse(
            $this->postJson('/api/request-test/version', []),
            ['version' => ['El campo version es obligatorio.']],
        );
    }

    public function test_invalid_email_uses_the_spanish_default_message(): void
    {
        $this->assertValidationResponse(
            $this->postJson('/api/request-test/member', $this->validInput(['email' => 'correo-invalido'])),
            ['email' => ['El campo correo electrónico debe ser una dirección de correo electrónico válida.']],
        );
    }

    public function test_duplicate_record_is_rejected(): void
    {
        $this->assertValidationResponse(
            $this->postJson('/api/request-test/member', $this->validInput(['email' => 'registrado@example.com'])),
            ['email' => ['El valor del campo correo electrónico ya está registrado.']],
        );
        $this->assertDatabaseCount('request_test_members', 1);
    }

    public function test_nonexistent_relation_is_rejected(): void
    {
        $this->assertValidationResponse(
            $this->postJson('/api/request-test/member', $this->validInput(['role' => 999])),
            ['role' => ['El valor seleccionado para role no existe.']],
        );
    }

    public function test_existing_child_messages_override_generic_messages(): void
    {
        $this->assertValidationResponse(
            $this->postJson('/api/request-test/login', ['password' => 'secreto']),
            ['name' => ['El campo nombre es obligatorio.']],
        );
    }

    public function test_custom_attributes_are_used_without_changing_error_keys(): void
    {
        $input = $this->validInput();
        unset($input['email']);

        $this->assertValidationResponse(
            $this->postJson('/api/request-test/member', $input),
            ['email' => ['El campo correo electrónico es obligatorio.']],
        );
    }

    public function test_response_includes_first_message_and_every_field_error(): void
    {
        $this->assertValidationResponse(
            $this->postJson('/api/request-test/member', []),
            [
                'name' => ['Indica el nombre del miembro.'],
                'email' => ['El campo correo electrónico es obligatorio.'],
                'role' => ['El campo role es obligatorio.'],
            ],
        );
    }

    public function test_non_json_clients_receive_the_same_json_validation_contract(): void
    {
        $this->assertValidationResponse(
            $this->post('/api/request-test/version', [], ['Accept' => 'text/html']),
            ['version' => ['El campo version es obligatorio.']],
        );
    }

    public function test_valid_input_is_normalized_and_reaches_the_controller(): void
    {
        $this->postJson('/api/request-test/member', $this->validInput(['name' => '  Ana  ', 'ignored' => 'extra']))
            ->assertCreated()->assertExactJson([
                'message' => 'creado',
                'code' => 201,
                'data' => $this->validInput(),
            ]);
    }

    public function test_existing_authorization_overrides_remain_effective(): void
    {
        $this->postJson('/request-test/register', [])->assertForbidden();
        $this->postJson('/request-test/update-user', [])->assertForbidden();
    }

    private function validInput(array $overrides = []): array
    {
        return array_replace(['name' => 'Ana', 'email' => 'ana@example.com', 'role' => 1], $overrides);
    }

    private function assertValidationResponse(TestResponse $response, array $errors): void
    {
        $response->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'message' => reset($errors)[0],
                'code' => 422,
                'data' => ['errors' => $errors],
                'meta' => null,
            ]);
    }
}

class MemberTestRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:request_test_members,email'],
            'role' => ['required', 'integer', 'exists:request_test_roles,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    protected function customMessages(): array
    {
        return ['name.required' => 'Indica el nombre del miembro.'];
    }

    protected function customAttributes(): array
    {
        return ['email' => 'correo electrónico'];
    }
}

class RequestTestController
{
    public function store(MemberTestRequest $request): JsonResponse
    {
        return response()->json(['message' => 'creado', 'code' => 201, 'data' => $request->validated()], 201);
    }

    public function version(VersionRequest $request): JsonResponse
    {
        return response()->json($request->validated());
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json($request->validated());
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        return response()->json($request->validated());
    }

    public function updateUser(UpdateUserRequest $request): JsonResponse
    {
        return response()->json($request->validated());
    }
}
