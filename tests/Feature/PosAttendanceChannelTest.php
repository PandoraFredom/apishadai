<?php

namespace Tests\Feature;

use App\Jobs\SendWorkLunchAlertJob;
use App\Models\WorkLunch;
use App\Services\Headquarters\PosAttendanceAlerts;
use App\Services\Headquarters\PosAttendanceService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class PosAttendanceChannelTest extends TestCase
{
    private const UUID = '10000000-0000-4000-8000-000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'queue.connections.database.connection' => 'sqlite', 'queue.connections.database.table' => 'jobs',
            'headquarters.secret' => str_repeat('t', 64), 'headquarters.pos_sender_ips' => ['127.0.0.1']]);
        DB::purge('sqlite');
        foreach (['device_estado', 'stock_estado'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('descripcion');
            });
            DB::table($name)->insert(['id' => 1, 'descripcion' => 'ACTIVO']);
        }
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->integer('estado');
        });
        DB::table('stocks')->insert(['id' => 1, 'estado' => 1]);
        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->string('hq_client_uuid');
            $table->integer('estado');
            $table->integer('stock');
        });
        DB::table('devices')->insert(['id' => 1, 'hq_client_uuid' => self::UUID, 'estado' => 1, 'stock' => 1]);
        foreach (['rolespos', 'modulospos', 'vistaspos', 'botonespos', 'usuariospos'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->boolean('activo');
                $table->string('codigo')->nullable();
                if ($name === 'usuariospos') {
                    $table->integer('rol_id');
                }
                if ($name === 'vistaspos') {
                    $table->integer('modulo_id');
                }
                if ($name === 'botonespos') {
                    $table->integer('vista_id');
                }
            });
        }
        DB::table('rolespos')->insert(['id' => 1, 'activo' => 1]);
        DB::table('usuariospos')->insert(['id' => 7, 'rol_id' => 1, 'activo' => 1]);
        DB::table('modulospos')->insert(['id' => 1, 'codigo' => 'asistencia', 'activo' => 1]);
        DB::table('vistaspos')->insert(['id' => 1, 'modulo_id' => 1, 'codigo' => 'asistencia.registro', 'activo' => 1]);
        DB::table('botonespos')->insert([['id' => 1, 'vista_id' => 1, 'codigo' => 'asistencia.jornada', 'activo' => 1],
            ['id' => 2, 'vista_id' => 1, 'codigo' => 'asistencia.break', 'activo' => 1]]);
        Schema::create('permisosusuariospos', function (Blueprint $table): void {
            $table->id();
            $table->integer('usuario');
            $table->integer('modulo');
            $table->integer('vista');
            $table->integer('actionvista');
            $table->integer('lifetime')->nullable();
        });
        DB::table('permisosusuariospos')->insert([['usuario' => 7, 'modulo' => 1, 'vista' => 1, 'actionvista' => 1],
            ['usuario' => 7, 'modulo' => 1, 'vista' => 1, 'actionvista' => 2]]);
        Schema::create('worklunch', function (Blueprint $table): void {
            $table->id();
            $table->integer('usuario')->nullable();
            $table->integer('usuario_pos')->nullable();
            $table->integer('device');
            $table->date('work_date');
            $table->dateTime('wkstart_time');
            $table->dateTime('wkend_time')->nullable();
            $table->dateTime('lunch_start_time')->nullable();
            $table->dateTime('lunch_end_time')->nullable();
            $table->timestamps();
            $table->unique(['usuario_pos', 'work_date']);
        });
        Schema::create('pos_asistencia_solicitudes', function (Blueprint $table): void {
            $table->uuid('request_id')->primary();
            $table->integer('device_id');
            $table->integer('usuario_pos_id');
            $table->string('huella');
            $table->text('datos');
            $table->text('respuesta');
            $table->timestamp('created_at');
            $table->timestamp('correo_encolado_at')->nullable();
            $table->timestamp('correo_enviado_at')->nullable();
        });
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue');
            $table->text('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        Schema::create('hq_pos_request_nonces', function (Blueprint $table): void {
            $table->uuid('nonce')->primary();
            $table->timestamp('expires_at');
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    private function payload(string $action, int $hour): array
    {
        return ['version' => 1, 'request_id' => (string) Str::uuid(), 'usuario_pos_id' => '7', 'equipo' => 'TEST-POS',
            'fecha' => '2026-10-01', 'registrado_at' => sprintf('2026-10-01T%02d:00:00-06:00', $hour), 'accion' => $action];
    }

    private function record(array $payload): array
    {
        return app(PosAttendanceService::class)->execute(1, self::UUID, 'registrar', $payload);
    }

    public function test_explicit_actions_and_exact_replay_do_not_toggle_or_duplicate_attendance(): void
    {
        $entry = $this->payload('ENTRADA', 8);
        $first = $this->record($entry);
        $this->assertTrue($first['aceptada']);
        $this->assertSame($first, $this->record($entry));
        $this->assertNull(WorkLunch::first()->wkend_time);
        $this->assertDatabaseCount('worklunch', 1);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('pos_asistencia_solicitudes', ['request_id' => $entry['request_id']]);
        $this->assertTrue($this->record($this->payload('INICIAR_BREAK', 12))['aceptada']);
        $this->assertFalse($this->record($this->payload('SALIDA', 12))['aceptada']);
        $this->assertTrue($this->record($this->payload('FINALIZAR_BREAK', 13))['aceptada']);
        $this->assertTrue($this->record($this->payload('SALIDA', 17))['aceptada']);
        $this->assertFalse($this->record($this->payload('INICIAR_BREAK', 18))['aceptada']);
        $this->assertDatabaseCount('jobs', 4);
        $this->assertDatabaseHas('worklunch', ['usuario' => null, 'usuario_pos' => 7, 'wkend_time' => '2026-10-01 17:00:00']);
    }

    public function test_changed_replay_is_rejected_and_does_not_rewrite_times(): void
    {
        $entry = $this->payload('ENTRADA', 8);
        $this->record($entry);
        $entry['accion'] = 'SALIDA';
        try {
            $this->record($entry);
            $this->fail('Replay changed.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertNull(WorkLunch::first()->wkend_time);
    }

    public function test_each_permission_is_individual_and_rejection_is_idempotent(): void
    {
        $this->record($this->payload('ENTRADA', 8));
        DB::table('permisosusuariospos')->where('actionvista', 2)->delete();
        $break = $this->payload('INICIAR_BREAK', 12);
        $first = $this->record($break);
        $this->assertFalse($first['aceptada']);
        $this->assertSame($first, $this->record($break));
        $this->assertNull(WorkLunch::first()->lunch_start_time);
    }

    public function test_duplicate_entry_from_another_terminal_cannot_become_an_exit(): void
    {
        $this->record($this->payload('ENTRADA', 8));
        $duplicate = $this->payload('ENTRADA', 9);
        $duplicate['equipo'] = 'TEST-POS2';
        $this->assertFalse($this->record($duplicate)['aceptada']);
        $this->assertNull(WorkLunch::first()->wkend_time);
    }

    public function test_inactive_registered_device_cannot_publish(): void
    {
        DB::table('devices')->where('id', 1)->update(['hq_client_uuid' => (string) Str::uuid()]);
        $this->expectException(HttpExceptionInterface::class);
        $this->record($this->payload('ENTRADA', 8));
    }

    public function test_queue_failure_rolls_back_the_mark_and_journal_so_original_request_can_retry(): void
    {
        $entry = $this->payload('ENTRADA', 8);
        Schema::drop('jobs');
        try {
            $this->record($entry);
            $this->fail('Missing queue must fail the transaction.');
        } catch (QueryException) {
            $this->assertDatabaseCount('worklunch', 0);
            $this->assertDatabaseCount('pos_asistencia_solicitudes', 0);
        }
    }

    public function test_recovery_queues_original_snapshot_once_even_after_later_marks(): void
    {
        $entry = $this->payload('ENTRADA', 8);
        $this->record($entry);
        $this->record($this->payload('SALIDA', 17));
        DB::table('jobs')->delete();
        DB::table('pos_asistencia_solicitudes')->where('request_id', $entry['request_id'])->update(['correo_encolado_at' => null]);
        Bus::fake([SendWorkLunchAlertJob::class]);
        $this->assertTrue(app(PosAttendanceAlerts::class)->enqueue($entry['request_id']));
        $this->assertFalse(app(PosAttendanceAlerts::class)->enqueue($entry['request_id']));
        Bus::assertDispatched(SendWorkLunchAlertJob::class, function (SendWorkLunchAlertJob $job) use ($entry): bool {
            $snapshot = (new \ReflectionProperty($job, 'snapshot'))->getValue($job);

            return $job->attendanceRequestId === $entry['request_id'] && $job->event === 'work_started'
                && $snapshot['wkstart_time'] === '2026-10-01 08:00:00' && $snapshot['wkend_time'] === null;
        });
        Bus::assertDispatchedTimes(SendWorkLunchAlertJob::class, 1);
        $this->assertDatabaseCount('worklunch', 1);
    }

    public function test_signed_endpoint_reaches_worklunch_without_a_portal_login(): void
    {
        $path = '/api/headquarters/pos/asistencia/registrar';
        $body = json_encode(['device_id' => 1, 'client_uuid' => self::UUID, 'payload' => $this->payload('ENTRADA', 8)], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $nonce = (string) Str::uuid();
        $signature = hash_hmac('sha256', "POST\n$path\n$timestamp\n$nonce\n".hash('sha256', $body), str_repeat('t', 64));
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'X-HQ-Key-Id' => 'apishadai',
            'X-HQ-Timestamp' => $timestamp, 'X-HQ-Nonce' => $nonce, 'X-HQ-Signature' => $signature];
        $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()->assertJsonPath('data.aceptada', true);
        $headers['X-HQ-Signature'] = str_repeat('0', 64);
        $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertUnauthorized();
    }
}
