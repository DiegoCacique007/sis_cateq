<?php

namespace Tests\Feature\Chatbot;

use App\Enums\UserRole as R;
use App\Models\ChatbotEvent;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\Contracts\IntentResolver;
use App\Services\Chatbot\Intent\IntentResolution;
use App\Services\Chatbot\Intent\MessageShaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** Bitácora del chatbot. La tabla se crea en SQLite en memoria con su propia migración. */
class ChatbotEventsTest extends CatequesisWebTestCase
{
    private const MIGRATION = '2026_10_18_000001_create_chatbot_events_table.php';

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/'.self::MIGRATION))->up();
    }

    private function login(R $role = R::Catequista): \App\Services\Authorization\AccessContext
    {
        $context = $this->context($role);
        $this->actingAs(User::findOrFail($context->userId));

        return $context;
    }

    /** [mensaje, rol, campos esperados del evento]. */
    public static function responseTypes(): array
    {
        $base = ['guard' => null, 'with_selection' => false, 'message_shape' => null];

        return [
            'resuelta' => ['mis grupos', R::Catequista, ['role' => 'catequista', 'intent' => 'VIEW_MY_GROUPS', 'resolution' => 'INTENT_RESOLVED', 'status' => 'success', 'code' => 'VIEW_MY_GROUPS_READY'] + $base],
            'sin coincidencia' => ['cuál es el clima', R::Secretaria, ['role' => 'secretaria', 'intent' => 'UNKNOWN', 'resolution' => 'NO_MATCH', 'status' => 'unsupported', 'code' => 'UNKNOWN_INTENT', 'message_shape' => 'cual es el …'] + $base],
            'ambigua' => ['muéstrame alumnos y calificaciones', R::Catequista, ['role' => 'catequista', 'intent' => 'UNKNOWN', 'resolution' => 'AMBIGUOUS_INTENT', 'status' => 'ambiguous', 'code' => 'AMBIGUOUS_INTENT'] + $base],
            'bloqueo explicado' => ['registra calificaciones', R::Catequista, ['role' => 'catequista', 'intent' => 'UNKNOWN', 'resolution' => 'UNSUPPORTED_REQUEST', 'status' => 'unsupported', 'code' => 'GRADE_WRITE_UNSUPPORTED', 'guard' => 'UNSUPPORTED-EVALUATION-WRITE'] + $base],
            'negación' => ['No quiero consultar alumnos', R::Parroco, ['role' => 'parroco', 'intent' => 'UNKNOWN', 'resolution' => 'UNSUPPORTED_REQUEST', 'status' => 'unsupported', 'code' => 'UNKNOWN_INTENT', 'guard' => 'NEGATED-REQUEST'] + $base],
            'rechazo por rol' => ['¿Cómo registro un alumno?', R::CoordinadorGeneral, ['role' => 'coordinador_general', 'intent' => 'HOW_TO_REGISTER_STUDENT', 'resolution' => 'INTENT_RESOLVED', 'status' => 'denied', 'code' => 'ROLE_NOT_ALLOWED'] + $base],
        ];
    }

    #[DataProvider('responseTypes')]
    public function test_each_response_records_exactly_one_minimal_event(string $message, R $role, array $expected): void
    {
        $context = $this->login($role);
        $this->records($context);
        $this->postJson(route('chatbot.message'), ['message' => $message])->assertOk();

        $event = ChatbotEvent::sole();
        $this->assertSame(today()->toDateString(), $event->occurred_on->toDateString());
        foreach ($expected as $column => $value) {
            $this->assertSame($value, $event->{$column}, $column);
        }
    }

    public function test_unexpected_error_records_error_event_without_resolution(): void
    {
        $this->login();
        $this->app->instance(IntentResolver::class, new class implements IntentResolver
        {
            public function resolve(string $message): IntentResolution
            {
                throw new \RuntimeException('fallo simulado');
            }
        });
        $this->postJson(route('chatbot.message'), ['message' => 'mis grupos'])->assertStatus(500);

        $event = ChatbotEvent::sole();
        $this->assertSame(['catequista', null, null, 'error', 'CHATBOT_ERROR'],
            [$event->role, $event->intent, $event->resolution, $event->status, $event->code]);
    }

    public function test_unauthorized_context_records_event_without_role(): void
    {
        $context = $this->context();
        DB::table('users')->where('id', $context->userId)->update(['status' => 'bloqueado']);
        $request = Request::create('/chatbot/message', 'POST');
        $request->setUserResolver(fn () => User::findOrFail($context->userId));
        $request->setLaravelSession($this->app['session.store']);
        app(ChatbotService::class)->handle($request, 'mis grupos');

        $event = ChatbotEvent::sole();
        $this->assertSame([null, null, 'denied', 'UNAUTHORIZED_CONTEXT'], [$event->role, $event->intent, $event->status, $event->code]);
    }

    public function test_events_store_no_message_names_ids_user_or_time(): void
    {
        $context = $this->login(R::Secretaria);
        $ids = $this->records($context);
        $this->postJson(route('chatbot.message'), ['message' => 'ver calificaciones de Juan Pérez 4242'])->assertOk();
        $this->postJson(route('chatbot.message'), ['message' => 'quiero saber de Juan Pérez 4242'])->assertOk();
        $this->postJson(route('chatbot.message'), ['message' => 'mis alumnos', 'assignment_id' => $ids['assignment']])->assertOk();

        $this->assertSame(['id', 'occurred_on', 'role', 'intent', 'resolution', 'guard', 'status', 'code', 'with_selection', 'message_shape'],
            Schema::getColumnListing('chatbot_events'));
        $rows = DB::table('chatbot_events')->orderBy('id')->get();
        $this->assertNull($rows[0]->message_shape, 'Solo NO_MATCH guarda la forma.');
        $this->assertSame('quiero saber de … … …', $rows[1]->message_shape);
        $this->assertTrue((bool) $rows[2]->with_selection);
        $dump = json_encode($rows->map(fn ($row) => collect((array) $row)->except('id'))->all(), JSON_UNESCAPED_UNICODE);
        foreach (['juan', 'Juan', 'perez', 'Pérez', '4242', (string) $context->userId.'"', 'mis alumnos'] as $leak) {
            $this->assertStringNotContainsString($leak, $dump);
        }
        foreach ($rows as $row) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $row->occurred_on, 'Solo el día, sin hora.');
        }
    }

    public function test_recorder_failure_never_changes_the_response(): void
    {
        $context = $this->login();
        $this->records($context);
        $expected = $this->postJson(route('chatbot.message'), ['message' => 'mis grupos'])->assertOk()->json();

        Schema::drop('chatbot_events');
        Log::spy();
        $this->postJson(route('chatbot.message'), ['message' => 'mis grupos'])->assertOk()->assertExactJson($expected);
        Log::shouldHaveReceived('warning')->once()->with('chatbot.event_failed', \Mockery::on(
            fn ($context) => array_keys($context) === ['exception_class'] && is_string($context['exception_class'])));
    }

    public static function commonNames(): array
    {
        $names = ['Rosa', 'Luz', 'Paz', 'Ángel', 'Dolores', 'Pilar', 'Mercedes', 'Consuelo', 'Guadalupe', 'Jesús',
            'María', 'José', 'Cruz', 'Esperanza', 'Victoria', 'Amparo', 'Socorro', 'Concepción', 'Asunción', 'Rocío',
            'Soledad', 'Cielo', 'Alba', 'Reyes', 'Santos', 'Trinidad', 'Natividad', 'Rosario', 'Lupe', 'Chuy'];
        $plain = fn (string $name) => strtr($name, ['Á' => 'A', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        return array_merge(...array_map(fn ($name) => [$name => [$name], $plain($name).' (sin acento)' => [$plain($name)]], $names));
    }

    #[DataProvider('commonNames')]
    public function test_common_names_never_pass_the_vocabulary(string $name): void
    {
        $shaper = new MessageShaper;
        $this->assertSame('ver calificaciones de …', $shaper->shape("ver calificaciones de $name"));
        $this->assertSame('…', $shaper->shape(mb_strtoupper($name)));
        $normalized = strtr(mb_strtolower($name), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        $this->assertNotContains($normalized, MessageShaper::vocabulary());
    }

    public function test_purge_deletes_old_events_and_clears_old_shapes(): void
    {
        $row = ['role' => 'catequista', 'intent' => 'UNKNOWN', 'resolution' => 'NO_MATCH', 'status' => 'unsupported',
            'code' => 'UNKNOWN_INTENT', 'with_selection' => false, 'message_shape' => 'ver …'];
        DB::table('chatbot_events')->insert([
            ['occurred_on' => today()->subDays(91)->toDateString()] + $row,
            ['occurred_on' => today()->subDays(31)->toDateString()] + $row,
            ['occurred_on' => today()->subDays(5)->toDateString()] + $row,
        ]);

        $this->assertSame(['deleted' => 1, 'cleared' => 1], ChatbotEvent::purgeExpired());
        $this->assertSame([null, 'ver …'], DB::table('chatbot_events')->orderBy('occurred_on')->pluck('message_shape')->all());
    }

    public function test_report_summarizes_and_purges(): void
    {
        $this->login();
        foreach (['mis grupos', 'cuál es el clima', 'cuál es el clima', 'registra calificaciones'] as $message) {
            $this->postJson(route('chatbot.message'), ['message' => $message])->assertOk();
        }
        DB::table('chatbot_events')->insert(['occurred_on' => today()->subDays(100)->toDateString(), 'role' => 'parroco',
            'status' => 'success', 'code' => 'HELP_READY', 'with_selection' => false]);

        $this->artisan('chatbot:report', ['--days' => 30])
            ->expectsOutputToContain('Limpieza: 1 eventos borrados, 0 formas vaciadas.')
            ->expectsOutputToContain('Eventos: 4. Solicitudes reconocidas: 1 (25 %).')
            ->expectsOutputToContain('cual es el …')
            ->expectsOutputToContain('GRADE_WRITE_UNSUPPORTED')
            ->assertExitCode(0);
        $this->assertSame(4, ChatbotEvent::count());
    }

    public function test_purge_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('chatbot-events-purge')->assertExitCode(0);
    }
}
