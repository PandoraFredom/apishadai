<?php

namespace App\Services;


use App\Utils\DeviceUtility;

class EncryptionService
{
    public function __construct(private DeviceUtility $deviceUtility) {}


    /**
     * Encripta un texto usando la IP como parte de la clave
     *
     * @param string $text Texto a encriptar
     * @return string Texto encriptado
     */
    public function encrypt(string $text,   $request): string
    {
        $device = $this->deviceUtility->get_DeviceInfo($request);

        if (!$device) {
            throw new \RuntimeException('Dispositivo no registrado');
        }

        $key = $this->generateKeyFromIp($device->ip);

        // Combina el texto con la IP antes de encriptar
        $dataToEncrypt = json_encode([
            'text' => $text,
            'ip' => $device->ip,
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
     * Desencripta un texto usando la IP como parte de la clave
     *
     * @param string $encryptedText Texto encriptado
     * @return string|null Texto desencriptado o null si la IP no coincide
     */
    public function decrypt(string $encryptedText, $request)
    {
        try {
            $device = $this->deviceUtility->get_DeviceInfo($request);
            if (!$device) {
                return null;
            }

            $ip = $device->ip;
            $key = $this->generateKeyFromIp($ip);

            $encrypted = base64_decode($encryptedText);
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

    /**
     * Genera una clave única basada en la IP
     *
     * @param string $ip
     * @return string
     */
    private function generateKeyFromIp(string $ip): string
    {
        // Combina la IP con el salt de la aplicación y genera una clave de 32 bytes
        return hash('sha256', $ip . config('app.key'), true);
    }

    public function genHash($var): string
    {
        $key = config('app.key') ?? env('APP_KEY');
        return hash_hmac('sha256', $var, $key);
    }
}
