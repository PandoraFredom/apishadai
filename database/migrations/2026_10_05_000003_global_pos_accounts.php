<?php

use App\Models\Pos\PermisoUsuarioPos;
use App\Models\Pos\UsuarioPos;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Decode every existing verifier and detect cross-stock collisions before changing schema.
        $converted = [];
        $lookups = [];
        foreach (DB::table('usuariospos')->orderBy('id')->get() as $user) {
            $secret = json_decode(Crypt::decryptString($user->secreto), true, 512, JSON_THROW_ON_ERROR);
            $username = strtolower(trim($secret['usuario']));
            $lookup = hash('sha256', $username);
            if (isset($lookups[$lookup])) {
                throw new RuntimeException('Existen usuarios POS repetidos entre stocks. Resuelve sus nombres antes de convertirlos en cuentas globales.');
            }
            if (! preg_match('/\A[a-zA-Z0-9._@-]{1,200}\z/', $username) || ($secret['purpose'] ?? '') !== 'ShPOS'
                || strlen(base64_decode($secret['hash'] ?? '', true) ?: '') !== 64 || strlen(base64_decode($secret['salt'] ?? '', true) ?: '') !== 32
                || ! is_int($secret['iterations'] ?? null) || $secret['iterations'] < 210000 || $secret['iterations'] > 1000000) {
                throw new RuntimeException('Hay un verificador POS incompatible. Se conserva el esquema anterior.');
            }
            $lookups[$lookup] = true;
            $converted[$user->id] = ['usuario_lookup' => $lookup, 'credenciales' => json_encode([
                'version' => 2, 'purpose' => 'ShPOS', 'algorithm' => 'PBKDF2-SHA512', 'usuario' => base64_encode($username),
                'hash' => $secret['hash'], 'salt' => $secret['salt'], 'iterations' => $secret['iterations'],
            ], JSON_THROW_ON_ERROR)];
        }
        Schema::table('usuariospos', function (Blueprint $table): void {
            $table->dropUnique(['stock_id', 'usuario_lookup']);
            $table->dropForeign(['stock_id']);
        });
        DB::transaction(function () use ($converted): void {
            foreach ($converted as $id => $data) {
                DB::table('usuariospos')->where('id', $id)->update($data);
            }
        });
        Schema::table('usuariospos', function (Blueprint $table): void {
            $table->dropColumn(['stock_id', 'secreto']);
            $table->unique('usuario_lookup');
        });
        Schema::dropIfExists('clavespos');
        DB::transaction(function (): void {
            foreach (UsuarioPos::orderBy('id')->get() as $user) {
                app(ChangeRecorder::class)->record($user, 'UPDATE');
            }
            foreach (PermisoUsuarioPos::orderBy('id')->get() as $grant) {
                app(ChangeRecorder::class)->record($grant, 'UPDATE');
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Las cuentas POS globales no pueden volver a vincularse a stocks automáticamente.');
    }
};
