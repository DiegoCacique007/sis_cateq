<?php

namespace Tests\Unit\Chatbot;

use App\Enums\ChatbotIntent as I;
use App\Services\Chatbot\Contracts\IntentResolver;
use App\Services\Chatbot\Intent\MessageNormalizer;
use App\Services\Chatbot\Intent\RuleBasedIntentResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RuleBasedIntentResolverTest extends TestCase
{
    public static function phrases(): array
    {
        return [
            ['muéstrame mis grupos', I::ViewMyGroups],
            ['qué grupos tengo asignados', I::ViewMyGroups],
            ['grupo asignado', I::ViewMyGroups],
            ['grupos asignados', I::ViewMyGroups],
            ['muéstrame mis alumnos', I::ViewGroupStudents],
            ['quiero ver los alumnos de mi grupo', I::ViewGroupStudents],
            ['lista de alumnos', I::ViewGroupStudents],
            ['cómo veo mi lista de asistencia', I::ViewAttendanceList],
            ['genera mi pdf de asistencia', I::ViewAttendanceList],
            ['consultar asistencia', I::ViewAttendanceList],
            ['generar asistencia', I::ViewAttendanceList],
            ['muéstrame las calificaciones', I::ViewEvaluations],
            ['consultar evaluaciones', I::ViewEvaluations],
            ['calificaciones de mis alumnos', I::ViewEvaluations],
            ['cómo registro calificaciones', I::HowToRecordEvaluations],
            ['cómo capturo una evaluación', I::HowToRecordEvaluations],
            ['cómo registrar calificaciones', I::HowToRecordEvaluations],
            ['cómo evaluar', I::HowToRecordEvaluations],
            ['cómo poner una calificación', I::HowToRecordEvaluations],
            ['cómo registro un alumno', I::HowToRegisterStudent],
            ['cómo agregar alumno', I::HowToRegisterStudent],
            ['dar de alta alumno', I::HowToRegisterStudent],
            ['cómo registro un tutor', I::HowToRegisterTutor],
            ['agregar tutor', I::HowToRegisterTutor],
            ['cómo hago una inscripción', I::HowToRegisterInscription],
            ['registrar inscripción', I::HowToRegisterInscription],
            ['cómo inscribir alumno', I::HowToRegisterInscription],
            ['cómo asigno un catequista a un grupo', I::HowToAssignGroup],
            ['cómo asignar grupo', I::HowToAssignGroup],
            ['cómo administro periodos', I::HowToManagePeriods],
            ['cómo administro usuarios', I::HowToManageUsers],
            ['cómo gestionar comunidades', I::HowToManageCommunities],
            ['cómo crear niveles', I::HowToManageLevels],
            ['cómo registrar unidades', I::HowToManageUnits],
            ['cómo agregar rubros', I::HowToManageRubrics],
            ['ver mi boleta', I::ViewBoleta],
            ['modifica los datos de este alumno', I::ModifyStudent],
            ['cambiar alumno', I::ModifyStudent],
            ['editar alumno', I::ModifyStudent],
            ['actualizar alumno', I::ModifyStudent],
            ['quiero corregir información administrativa', I::ModifyAdministrativeData],
            ['modificar usuarios', I::ModifyAdministrativeData],
            ['qué puedes hacer', I::HelpSystem],
            ['ayuda', I::HelpSystem],
            ['cómo funciona el sistema', I::HelpSystem],
            ['qué puedo consultar', I::HelpSystem],
            ['¿CÓMO puedo VER   mi Lista de Asistencia?', I::ViewAttendanceList],
            ['COMO REGISTRO UN ALUMNO', I::HowToRegisterStudent],
            ["co\u{0301}mo registro una evaluacio\u{0301}n", I::HowToRecordEvaluations],
            ['soy secretaria, cómo registro un alumno', I::HowToRegisterStudent],
            ['soy parroco, cómo registro un alumno', I::HowToRegisterStudent],
            ['ayuda para registrar un alumno', I::HowToRegisterStudent],
        ];
    }

    #[DataProvider('phrases')]
    public function test_explicit_spanish_patterns(string $message, I $intent): void
    {
        $resolver = new RuleBasedIntentResolver;
        $this->assertInstanceOf(IntentResolver::class, $resolver);
        $result = $resolver->resolve($message);
        $this->assertSame($intent, $result->intent);
        $this->assertFalse($result->ambiguous);
        $this->assertSame('INTENT_RESOLVED', $result->code);
        $this->assertNotEmpty($result->matchedPatterns);
    }

    public static function unknownPhrases(): array
    {
        return [
            ['cuál es el clima'], ['alumnoscopio'], ['recalificaciones'], ['soy secretaria'], ['intercambiar alumno'],
            ['soy parroco'], ['registra la asistencia'], ['registra la lista de asistencia'],
            ['cómo registrar asistencia'], ['registra calificaciones'], ['no muestres mis alumnos'],
            ['no quiero ver alumnos'], ['ver alumnos y registra asistencia'],
        ];
    }

    #[DataProvider('unknownPhrases')]
    public function test_unknown_or_unavailable_requests_are_not_converted_to_reads(string $message): void
    {
        $this->assertSame(I::Unknown, (new RuleBasedIntentResolver)->resolve($message)->intent);
    }

    public static function ambiguousPhrases(): array
    {
        return [
            ['muéstrame alumnos y calificaciones'],
            ['quiero ver alumnos y calificaciones'],
            ['cómo registro un alumno y cómo registro un tutor'],
            ['cómo administro periodos y usuarios'],
            ['cómo registro un alumno y un tutor'],
            ['modifica este alumno y muéstrame calificaciones'],
            ['ver alumnos; consultar evaluaciones'],
        ];
    }

    #[DataProvider('ambiguousPhrases')]
    public function test_multiple_intents_require_clarification(string $message): void
    {
        $result = (new RuleBasedIntentResolver)->resolve($message);
        $this->assertSame(I::Unknown, $result->intent);
        $this->assertTrue($result->ambiguous);
        $this->assertSame('AMBIGUOUS_INTENT', $result->code);
        $this->assertCount(2, $result->candidates);
    }

    public function test_repetition_is_not_ambiguity(): void
    {
        $result = (new RuleBasedIntentResolver)->resolve('ver alumnos y muéstrame mis alumnos');
        $this->assertSame(I::ViewGroupStudents, $result->intent);
        $this->assertFalse($result->ambiguous);
    }

    public function test_normalization_preserves_negation_identifiers_and_enye(): void
    {
        $normalizer = new MessageNormalizer;
        $this->assertSame('no cambies al niño 42; año 2026', $normalizer->normalize('¿NO cambies al NIÑO 42; año 2026?'));
        $text = $normalizer->normalize("¿CÓMO   puedo VER\tmi Lista de Asistencia?");
        $this->assertSame('como puedo ver mi lista de asistencia', $text);
        $this->assertSame($text, $normalizer->normalize($text));
    }

    public function test_empty_invalid_and_oversized_messages_have_stable_codes(): void
    {
        $resolver = new RuleBasedIntentResolver;
        $this->assertSame('EMPTY_MESSAGE', $resolver->resolve(' ¿? ')->code);
        $this->assertSame('INVALID_MESSAGE', $resolver->resolve(str_repeat('a', 2001))->code);
        $this->assertSame('INVALID_MESSAGE', $resolver->resolve("\xFF")->code);
    }

    public function test_resolution_is_immutable_and_keeps_no_message_text(): void
    {
        $result = (new RuleBasedIntentResolver)->resolve('ver alumnos de Juan Perez');
        $this->assertStringNotContainsString('Juan', json_encode($result));
        $this->expectException(\Error::class);
        $result->intent = I::HelpSystem;
    }
}
