<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\VersionRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class BaseRequestTest extends TestCase
{
    public function test_every_concrete_request_uses_the_common_validation_response(): void
    {
        $validator = Validator::make([], ['first' => 'required', 'second' => 'required'], [
            'first.required' => 'Primer error.',
            'second.required' => 'Segundo error.',
        ]);
        $validator->fails();

        foreach (File::allFiles(app_path('Http/Requests')) as $file) {
            $class = 'App\\Http\\Requests\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            $this->assertTrue($reflection->isSubclassOf(BaseRequest::class), $class);
            $method = $reflection->getMethod('failedValidation');
            $this->assertSame(BaseRequest::class, $method->getDeclaringClass()->getName(), $class);

            try {
                $method->invoke($reflection->newInstance(), $validator);
                $this->fail($class.' debe lanzar HttpResponseException.');
            } catch (HttpResponseException $exception) {
                $this->assertSame(422, $exception->getResponse()->getStatusCode(), $class);
                $this->assertSame([
                    'message' => 'Primer error.',
                    'code' => 422,
                    'data' => ['errors' => ['first' => ['Primer error.'], 'second' => ['Segundo error.']]],
                    'meta' => null,
                ], json_decode($exception->getResponse()->getContent(), true), $class);
            }
        }
    }

    #[DataProvider('sizeRules')]
    public function test_size_messages_use_the_correct_units(string $type, string $rule, mixed $value, string $expected): void
    {
        if ($type === 'file') {
            $value = UploadedFile::fake()->create('documento.pdf', $value);
        }
        $validator = Validator::make(['campo' => $value], ['campo' => [$type, $rule]], (new VersionRequest)->messages());

        $this->assertSame($expected, $validator->errors()->first('campo'));
    }

    public static function sizeRules(): array
    {
        return [
            'min numeric' => ['numeric', 'min:3', 1, 'El campo campo debe ser al menos 3.'],
            'max numeric' => ['numeric', 'max:3', 5, 'El campo campo no debe ser mayor que 3.'],
            'between numeric' => ['numeric', 'between:2,4', 5, 'El campo campo debe estar entre 2 y 4.'],
            'min string' => ['string', 'min:3', 'a', 'El campo campo debe tener al menos 3 caracteres.'],
            'max string' => ['string', 'max:3', 'abcde', 'El campo campo no debe exceder 3 caracteres.'],
            'between string' => ['string', 'between:2,4', 'abcde', 'El campo campo debe tener entre 2 y 4 caracteres.'],
            'min array' => ['array', 'min:3', [1], 'El campo campo debe contener al menos 3 elementos.'],
            'max array' => ['array', 'max:3', [1, 2, 3, 4], 'El campo campo no debe contener más de 3 elementos.'],
            'between array' => ['array', 'between:2,4', [1], 'El campo campo debe contener entre 2 y 4 elementos.'],
            'min file' => ['file', 'min:3', 1, 'El archivo campo debe pesar al menos 3 kilobytes.'],
            'max file' => ['file', 'max:3', 5, 'El archivo campo no debe pesar más de 3 kilobytes.'],
            'between file' => ['file', 'between:2,4', 5, 'El archivo campo debe pesar entre 2 y 4 kilobytes.'],
        ];
    }

    public function test_rule_messages_can_be_overridden_and_optional_rules_keep_their_behavior(): void
    {
        $request = new class extends BaseRequest
        {
            public function rules(): array
            {
                return ['email' => ['sometimes', 'nullable', 'email']];
            }

            protected function customMessages(): array
            {
                return ['email' => 'Ingresa un correo válido.'];
            }
        };

        foreach ([[], ['email' => null], ['email' => 'ana@example.com']] as $input) {
            $this->assertTrue(Validator::make($input, $request->rules(), $request->messages())->passes());
        }

        $this->assertSame('Ingresa un correo válido.', Validator::make(
            ['email' => 'inválido'], $request->rules(), $request->messages(),
        )->errors()->first());
    }

    #[DataProvider('placeholderRules')]
    public function test_laravel_placeholders_are_replaced(array $input, string $rule, string $expected): void
    {
        $validator = Validator::make($input, ['campo' => $rule], (new VersionRequest)->messages());
        $this->assertSame($expected, $validator->errors()->first('campo'));
    }

    public static function placeholderRules(): array
    {
        return [
            'required_if' => [['otro' => 'si'], 'required_if:otro,si', 'El campo campo es obligatorio cuando otro es si.'],
            'required_with' => [['otro' => 'si'], 'required_with:otro', 'El campo campo es obligatorio cuando se proporciona otro.'],
            'required_without' => [[], 'required_without:otro', 'El campo campo es obligatorio cuando no se proporciona otro.'],
            'decimal' => [['campo' => '1.1'], 'decimal:2,4', 'El campo campo debe tener 2-4 decimales.'],
            'in' => [['campo' => 'c'], 'in:a,b', 'El campo campo debe ser uno de los siguientes valores: a, b.'],
            'date_format' => [['campo' => '18/09/2026'], 'date_format:Y-m-d', 'El campo campo debe tener el formato Y-m-d.'],
        ];
    }
}
