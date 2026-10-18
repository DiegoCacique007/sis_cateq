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
            // Desconocimiento expresado con «no sé cómo»: pide orientación, no niega.
            ['No sé cómo registrar un alumno', I::HowToRegisterStudent],
            ['no se como registrar un alumno', I::HowToRegisterStudent],
            ['No entiendo cómo capturar calificaciones', I::HowToRecordEvaluations],
            ['no recuerdo cómo asignar un grupo', I::HowToAssignGroup],
            ['¿Cómo doy de alta a un alumno?', I::HowToRegisterStudent],
            ['cómo registro a un alumno', I::HowToRegisterStudent],
            ['No sé cómo dar de alta al alumno', I::HowToRegisterStudent],
            // Reporte de problema: se atiende con la consulta u orientación existente.
            ['No puedo ver mis alumnos', I::ViewGroupStudents],
            ['no me salen mis alumnos', I::ViewGroupStudents],
            ['no veo a mis alumnos', I::ViewGroupStudents],
            ['no me aparecen los alumnos', I::ViewGroupStudents],
            ['No me aparecen las calificaciones', I::ViewEvaluations],
            ['no me muestra las calificaciones', I::ViewEvaluations],
            ['no me cargan las evaluaciones', I::ViewEvaluations],
            ['No encuentro mi grupo', I::ViewMyGroups],
            ['no logro ver mi grupo', I::ViewMyGroups],
            ['ver mi grupo', I::ViewMyGroups],
            ['No me deja registrar un alumno', I::HowToRegisterStudent],
            ['no me dejan dar de alta a un alumno', I::HowToRegisterStudent],
            // Efectos aceptados de la misma excepción.
            ['No puedo descargar la lista de asistencia', I::ViewAttendanceList],
            ['No puedo descargar la boleta', I::ViewBoleta],
            ['No me deja editar un alumno', I::ModifyStudent],
            // Reporte inmediatamente antes del verbo de escritura: solo orientación, nunca registro.
            ['No me deja registrar calificaciones', I::HowToRecordEvaluations],
            ['No puedo capturar las evaluaciones', I::HowToRecordEvaluations],
            ['No logro guardar las calificaciones de mi grupo', I::HowToRecordEvaluations],
            ['no me dejan capturar la evaluación', I::HowToRecordEvaluations],
            ['no me permite poner calificaciones', I::HowToRecordEvaluations],
            ['no podemos registrar las evaluaciones', I::HowToRecordEvaluations],
            ['No puedo subir las calificaciones', I::HowToRecordEvaluations],
            ['No me deja cargar las evaluaciones', I::HowToRecordEvaluations],
            // Preguntas de posibilidad (propias o impersonales), causa y lugar.
            ['¿Puedo registrar calificaciones?', I::HowToRecordEvaluations],
            ['puedo registrar calificaciones', I::HowToRecordEvaluations],
            ['¿Se puede registrar calificaciones?', I::HowToRecordEvaluations],
            ['¿se pueden capturar evaluaciones?', I::HowToRecordEvaluations],
            ['¿podemos poner las calificaciones?', I::HowToRecordEvaluations],
            ['¿Puedo ver mis alumnos?', I::ViewGroupStudents],
            ['¿Se puede dar de alta a un alumno?', I::HowToRegisterStudent],
            ['¿Por qué no puedo ver mis alumnos?', I::ViewGroupStudents],
            ['¿Por qué no veo mis calificaciones?', I::ViewEvaluations],
            ['¿Por qué no me deja registrar calificaciones?', I::HowToRecordEvaluations],
            ['¿Dónde registro las calificaciones?', I::HowToRecordEvaluations],
            ['¿Dónde capturo calificaciones?', I::HowToRecordEvaluations],
            ['¿Dónde veo mis alumnos?', I::ViewGroupStudents],
            ['No sé dónde dar de alta a un alumno', I::HowToRegisterStudent],
            ['no recuerdo dónde registro un alumno', I::HowToRegisterStudent],
            // «dónde» en las demás guías que comparten $how.
            ['¿Dónde inscribo a un alumno?', I::HowToRegisterInscription],
            ['¿Dónde registro un tutor?', I::HowToRegisterTutor],
            ['¿Dónde asigno un grupo?', I::HowToAssignGroup],
            ['¿Dónde administro periodos?', I::HowToManagePeriods],
            ['¿Dónde registro un alumno?', I::HowToRegisterStudent],
            ['¿dónde puedo crear niveles?', I::HowToManageLevels],
            // Reportes impersonales («no se» + verbo conjugado), de carencia y verbos nuevos.
            ['No se guardan las calificaciones', I::HowToRecordEvaluations],
            ['no se guarda la calificación', I::HowToRecordEvaluations],
            ['No se ven mis alumnos', I::ViewGroupStudents],
            ['No se cargan las evaluaciones', I::ViewEvaluations],
            ['no se muestran mis alumnos', I::ViewGroupStudents],
            ['No sale mi grupo', I::ViewMyGroups],
            ['No aparece el alumno', I::ViewGroupStudents],
            ['No tengo grupo', I::ViewMyGroups],
            ['No tengo grupos asignados', I::ViewMyGroups],
            ['no tengo ningún grupo asignado', I::ViewMyGroups],
            ['No tengo alumnos', I::ViewGroupStudents],
            ['no tengo ningún alumno', I::ViewGroupStudents],
            ['No puedo meter las calificaciones', I::HowToRecordEvaluations],
            ['¿puedo meter calificaciones?', I::HowToRecordEvaluations],
            ['No me deja que registre las calificaciones', I::HowToRecordEvaluations],
            ['no me dejan que capture la evaluación', I::HowToRecordEvaluations],
            ['no me permite que meta las calificaciones', I::HowToRecordEvaluations],
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
            ['No quiero consultar alumnos'], ['no sé si quiero ver alumnos'],
            ['nunca sé cómo registrar un alumno'], ['no sé cómo no ver mis alumnos'],
            ['no sé cómo, pero no quiero ver alumnos'], ['no se como registra la asistencia'],
            ['no puedo, no quiero ver alumnos'], ['nunca encuentro mi grupo'],
            ['tampoco me aparecen las calificaciones'], ['no puedo no ver mis alumnos'],
            // Escritura de calificaciones sin reporte inmediatamente anterior al verbo.
            ['guarda las calificaciones de mi grupo'], ['pon 9 de calificación a un alumno'],
            ['no quiero registrar calificaciones'], ['registra las calificaciones, que yo no puedo'],
            ['no puedo registrar la asistencia'], ['guarda las calificaciones porque no me deja registrar'],
            ['no puedo hacer el registro de calificaciones'], ['nunca puedo registrar calificaciones'],
            ['no puedo no registrar calificaciones'], ['no sé si puedo registrar calificaciones'],
            // Pedir al chatbot que registre (segunda persona) sigue bloqueado, igual que la asistencia.
            ['puedes registrar las calificaciones de mi grupo'], ['¿me puedes registrar calificaciones?'],
            ['¿podrías registrar las calificaciones?'], ['¿por qué no registras las calificaciones?'],
            ['no quiero ver mis alumnos'], ['¿dónde registro la asistencia?'],
            ['registra las calificaciones donde falten'], ['guarda las calificaciones porque puedo registrar'],
            ['no sé dónde, pero no quiero ver alumnos'], ['nunca sé dónde registrar calificaciones'],
            // Sin objeto: no corresponden a ninguna intención.
            ['No me sale nada'], ['No funciona'],
            // Órdenes, negaciones reales y frases que el impersonal no debe abrir.
            ['mete las calificaciones de mi grupo'], ['no metas calificaciones'],
            ['no quiero que se guarden las calificaciones'], ['no se registren calificaciones'],
            ['no se guarda la asistencia'], ['tampoco se ven mis alumnos'],
            ['no se registra la calificación'], ['no se captura la evaluación'], ['no se pone la calificación'],
            ['no tengo que ver alumnos'], ['no tengo ganas de ver mis alumnos'],
            ['no se ven mis alumnos y no quiero'], ['no sé ver mis alumnos'], ['tengo un alumno nuevo'],
        ];
    }

    public static function impersonalGradeWrites(): array
    {
        return [['no se registra la calificación'], ['no se captura la evaluación']];
    }

    #[DataProvider('impersonalGradeWrites')]
    public function test_impersonal_exemption_only_covers_guarda_in_write_guard(string $message): void
    {
        $result = (new RuleBasedIntentResolver)->resolve($message);
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('UNSUPPORTED-EVALUATION-WRITE', $result->matchedPatterns);
    }

    public function test_impersonal_pone_is_blocked_by_negation_only(): void
    {
        // Hueco conocido: UNSUPPORTED-EVALUATION-WRITE no reconoce «pone»; se cerrará en otra tarea.
        $result = (new RuleBasedIntentResolver)->resolve('no se pone la calificación');
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('NEGATED-REQUEST', $result->matchedPatterns);
        $this->assertNotContains('UNSUPPORTED-EVALUATION-WRITE', $result->matchedPatterns);
    }

    public function test_impersonal_and_unknowing_no_se_remain_distinct(): void
    {
        $resolver = new RuleBasedIntentResolver;
        $this->assertSame(I::HowToRegisterStudent, $resolver->resolve('no sé cómo registrar un alumno')->intent);
        $this->assertSame(I::HowToRegisterStudent, $resolver->resolve('no sé dónde dar de alta a un alumno')->intent);
        $this->assertSame(I::ViewGroupStudents, $resolver->resolve('no se ven mis alumnos')->intent);
        $this->assertSame('UNSUPPORTED_REQUEST', $resolver->resolve('no sé si quiero ver alumnos')->code);
    }

    public function test_saved_attendance_report_stays_blocked_by_attendance_guard(): void
    {
        $result = (new RuleBasedIntentResolver)->resolve('no se guarda la asistencia');
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('UNSUPPORTED-ATTENDANCE', $result->matchedPatterns);
    }

    public static function botDirectedWrites(): array
    {
        return [['puedes registrar las calificaciones de mi grupo'], ['¿me puedes registrar calificaciones?'], ['¿podrías registrar las calificaciones?']];
    }

    #[DataProvider('botDirectedWrites')]
    public function test_asking_the_bot_to_record_grades_is_blocked_by_write_guard(string $message): void
    {
        $result = (new RuleBasedIntentResolver)->resolve($message);
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('UNSUPPORTED-EVALUATION-WRITE', $result->matchedPatterns);
    }

    public function test_where_question_about_attendance_stays_blocked(): void
    {
        $result = (new RuleBasedIntentResolver)->resolve('¿dónde registro la asistencia?');
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('UNSUPPORTED-ATTENDANCE', $result->matchedPatterns);
    }

    public function test_evaluation_write_guard_blocks_verb_not_immediately_after_report(): void
    {
        $result = (new RuleBasedIntentResolver)->resolve('guarda las calificaciones porque no me deja registrar');
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('UNSUPPORTED-EVALUATION-WRITE', $result->matchedPatterns);
        $this->assertNotContains('NEGATED-REQUEST', $result->matchedPatterns);
    }

    public function test_cargar_distinguishes_missing_grades_from_recording_problem(): void
    {
        $resolver = new RuleBasedIntentResolver;
        $this->assertSame(I::ViewEvaluations, $resolver->resolve('no me cargan las calificaciones')->intent);
        $this->assertSame(I::HowToRecordEvaluations, $resolver->resolve('no puedo cargar las calificaciones')->intent);
    }

    public function test_group_students_phrasing_is_not_ambiguous_with_singular_group(): void
    {
        foreach (['ver los alumnos de mi grupo', 'quiero ver los alumnos de mi grupo', 'no encuentro los alumnos de mi grupo'] as $message) {
            $result = (new RuleBasedIntentResolver)->resolve($message);
            $this->assertFalse($result->ambiguous, $message);
            $this->assertSame(I::ViewGroupStudents, $result->intent, $message);
            $this->assertNotContains('VIEW-GROUPS', $result->matchedPatterns, $message);
        }
    }

    public function test_real_negation_remains_blocked_as_unsupported(): void
    {
        $result = (new RuleBasedIntentResolver)->resolve('No quiero consultar alumnos');
        $this->assertSame(I::Unknown, $result->intent);
        $this->assertSame('UNSUPPORTED_REQUEST', $result->code);
        $this->assertContains('NEGATED-REQUEST', $result->matchedPatterns);
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
