<?php

namespace App\Utils\Repositories;

use App\Utils\Services\CryptoService;

class CryptoRepository implements CryptoService
{

    /**
     * @inheritDoc
     */
    public function encrypt(string $data, string $ip): string
    {

        $key = $this->generateKeyFromIp($ip);

        // Combina el texto con la IP antes de encriptar
        $dataToEncrypt = json_encode([
            'text' => $data,
            'ip' => $ip,
            'timestamp' => now()->timestamp,
            'key_hash' => hash('sha256', $key) // Almacenamos un hash de la clave para verificación
        ]);

        // Usamos openssl_encrypt para usar nuestra clave personalizada
        $encrypted = openssl_encrypt(
            $dataToEncrypt,
            'AES-256-CBC',
            $key,
            0,
            substr($key, 0, 16) // Usamos los primeros 16 bytes como IV
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Error al encriptar los datos');
        }

        return base64_encode($encrypted);
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $encryptedData, string $ip): string|null
    {
        try {

            $key = $this->generateKeyFromIp($ip);

            $encrypted = base64_decode($encryptedData);
            $decrypted = openssl_decrypt(
                $encrypted,
                'AES-256-CBC',
                $key,
                0,
                substr($key, 0, 16) // Usamos los mismos 16 bytes como IV
            );

            if ($decrypted === false) {
                return null;
            }

            $data = json_decode($decrypted, true);

            // Verificamos que la IP y la clave coincidan
            if ($data['ip'] !== $ip || hash('sha256', $key) !== $data['key_hash']) {
                return null;
            }

            return $data['text'];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateKeyFromIp(string $ip): string
    {
        // Combina la IP con el salt de la aplicación y genera una clave de 32 bytes
        return hash('sha256', $ip . config('app.key'), true);
    }
}
