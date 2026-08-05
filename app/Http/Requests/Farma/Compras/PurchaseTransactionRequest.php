<?php

namespace App\Http\Requests\Farma\Compras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class PurchaseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('nro')) {
            $this->merge(['nro' => filled($this->input('nro')) ? trim(strip_tags((string) $this->input('nro'))) : null]);
        }
    }

    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            ...($updating ? ['id' => ['required', 'integer', 'exists:compra_transc,id']] : []),
            'compra' => ['required', 'integer', 'exists:compras,id'],
            'tipo' => ['required', 'integer', 'exists:transc_tipo,id'],
            'nro' => ['nullable', 'string', 'max:100'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'img' => ['nullable', 'string', 'max:1398104', static function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || base64_decode($value, true) === false) {
                    $fail('El documento debe ser una imagen base64 válida.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            'valor.min' => 'El valor debe ser mayor que cero.',
            'img.max' => 'El documento escaneado no debe exceder 1 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $description = (string) DB::table('transc_tipo')->where('id', $this->integer('tipo'))->value('descripcion');
            $normalized = Str::lower($description);
            $requiresDocument = Str::contains($normalized, ['abono', 'nota de crédito', 'nota de credito']);

            if (! $requiresDocument || filled($this->input('img'))) {
                return;
            }

            $hasStoredDocument = $this->isMethod('PUT') || $this->isMethod('PATCH')
                ? DB::table('compra_transc')->where('id', $this->integer('id'))->whereNotNull('img')->exists()
                : false;

            if (! $hasStoredDocument) {
                $validator->errors()->add('img', 'El comprobante es obligatorio para abonos y notas de crédito.');
            }
        });
    }
}
