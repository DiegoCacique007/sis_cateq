<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Models\User;
use Tests\Support\CatequesisWebTestCase;

/** Los mensajes de validación de las calificaciones nunca incluyen claves enviadas por el cliente. */
class CatequistaEvaluationValidationTest extends CatequesisWebTestCase
{
    public function test_malicious_grade_keys_never_reach_validation_messages(): void
    {
        $context = $this->context(R::Catequista);
        $ids = $this->records($context);
        $this->actingAs(User::findOrFail($context->userId));
        $key = '<svg/onload=alert(1)>';

        $response = $this->from(route('catequista.evaluaciones.index'))->post(route('catequista.evaluaciones.guardar'), [
            'asignacion_id' => $ids['assignment'],
            'unidad_id' => 1,
            'calificaciones' => [$key => [$key => 'abc']],
        ]);

        $response->assertRedirect(route('catequista.evaluaciones.index'))->assertSessionHasErrors();
        $messages = session('errors')->all();
        $this->assertContains('Cada calificación debe ser numérica.', $messages);
        foreach ($messages as $message) {
            $this->assertStringNotContainsString('<svg', $message);
            $this->assertStringNotContainsString('calificaciones.', $message);
        }
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('evaluaciones')->where('calificacion', 'abc')->count());
    }

    public function test_empty_student_grades_get_spanish_message_without_field_path(): void
    {
        $context = $this->context(R::Catequista);
        $ids = $this->records($context);
        $this->actingAs(User::findOrFail($context->userId));

        $this->from(route('catequista.evaluaciones.index'))->post(route('catequista.evaluaciones.guardar'), [
            'asignacion_id' => $ids['assignment'],
            'unidad_id' => 1,
            'calificaciones' => ['<b>x</b>' => 'no-es-arreglo'],
        ])->assertSessionHasErrors();

        $messages = session('errors')->all();
        $this->assertContains('El formato de calificaciones no es válido.', $messages);
        foreach ($messages as $message) {
            $this->assertStringNotContainsString('<b>', $message);
        }
    }
}
