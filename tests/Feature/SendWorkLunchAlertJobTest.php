<?php

namespace Tests\Feature;

use App\Jobs\SendWorkLunchAlertJob;
use App\Notifications\AlertMail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SendWorkLunchAlertJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('stock');
            $table->timestamps();
        });
        Schema::create('worklunch', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario');
            $table->unsignedBigInteger('device');
            $table->dateTime('wkstart_time');
            $table->date('work_date')->nullable();
            $table->dateTime('wkend_time')->nullable();
            $table->dateTime('lunch_start_time')->nullable();
            $table->dateTime('lunch_end_time')->nullable();
            $table->timestamps();
        });
        Schema::create('appconfig', function (Blueprint $table): void {
            $table->id();
            $table->string('mail_alert')->nullable();
            $table->string('mail_cc1')->nullable();
            $table->string('mail_cc2')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 7,
            'nombre' => 'Ana López',
        ]);
        DB::table('stocks')->insert([
            'id' => 4,
            'descripcion' => 'Sucursal Centro',
        ]);
        DB::table('devices')->insert([
            'id' => 3,
            'stock' => 4,
        ]);
        DB::table('worklunch')->insert([
            'id' => 15,
            'usuario' => 7,
            'device' => 3,
            'work_date' => '2026-07-29',
            'wkstart_time' => '2026-07-29 08:00:00',
        ]);
        DB::table('appconfig')->insert([
            'id' => 1,
            'mail_alert' => 'alerts@example.test',
            'mail_cc1' => 'supervisor@example.test',
            'mail_cc2' => 'rrhh@example.test',
        ]);

        Notification::fake();
    }

    protected function tearDown(): void
    {
        foreach (['appconfig', 'worklunch', 'devices', 'stocks', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_it_sends_the_exact_work_event_to_the_configured_recipient(): void
    {
        $job = new SendWorkLunchAlertJob(
            15,
            'work_started',
            [
                'wkstart_time' => '2026-07-29 08:00:00',
                'wkend_time' => null,
                'lunch_start_time' => null,
                'lunch_end_time' => null,
            ],
        );

        $job->handle();

        Notification::assertSentOnDemand(
            AlertMail::class,
            function (AlertMail $notification, array $channels, object $notifiable): bool {
                $data = $notification->toArray($notifiable);
                $mail = $notification->toMail($notifiable);

                return $channels === ['mail']
                    && $notifiable->routeNotificationFor('mail') === 'supervisor@example.test'
                    && $data['data']['event'] === 'work_started'
                    && $data['data']['event_time'] === '2026-07-29 08:00:00'
                    && $data['data']['stock'] === 'Sucursal Centro'
                    && $mail->subject === 'Entrada registrada · Ana López';
            },
        );
    }

    public function test_it_does_not_send_when_the_primary_recipient_is_invalid(): void
    {
        DB::table('appconfig')->update(['mail_cc1' => 'correo-invalido']);

        (new SendWorkLunchAlertJob(
            15,
            'lunch_started',
            [
                'wkstart_time' => '2026-07-29 08:00:00',
                'wkend_time' => null,
                'lunch_start_time' => '2026-07-29 12:00:00',
                'lunch_end_time' => null,
            ],
        ))->handle();

        Notification::assertNothingSent();
    }

    public function test_it_logs_a_final_delivery_failure(): void
    {
        Log::spy();
        $job = new SendWorkLunchAlertJob(
            15,
            'work_started',
            [
                'wkstart_time' => '2026-07-29 08:00:00',
                'wkend_time' => null,
                'lunch_start_time' => null,
                'lunch_end_time' => null,
            ],
        );

        $job->failed(new RuntimeException('SMTP no disponible'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'El correo WorkLunch agotó sus reintentos.',
                \Mockery::on(fn (array $context): bool => ($context['work_lunch_id'] ?? null) === 15
                    && ($context['event'] ?? null) === 'work_started'
                    && ($context['exception'] ?? null) === RuntimeException::class),
            );
    }
}
