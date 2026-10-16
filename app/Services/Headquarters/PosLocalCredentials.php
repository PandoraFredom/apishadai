<?php

namespace App\Services\Headquarters;

final class PosLocalCredentials
{
    /** @param array<string, mixed> $previous
     * @return array<string, mixed>
     */
    public static function create(string $username, ?string $password, array $previous = []): array
    {
        $salt = $password !== null ? random_bytes(32) : base64_decode($previous['salt'], true);

        return ['version' => 2, 'purpose' => 'ShPOS', 'algorithm' => 'PBKDF2-SHA512',
            'usuario' => base64_encode($username), 'salt' => base64_encode($salt),
            'hash' => $password !== null ? base64_encode(hash_pbkdf2('sha512', $password, $salt, 210000, 64, true)) : $previous['hash'],
            'iterations' => $password !== null ? 210000 : $previous['iterations']];
    }
}
