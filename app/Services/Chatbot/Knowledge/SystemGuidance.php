<?php

namespace App\Services\Chatbot\Knowledge;

use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;

/** Procedimientos verificados en vistas/controladores; las rutas son solo navegación GET. */
final class SystemGuidance
{
    public function for(I $intent, R $role): ?array
    {
        if ($intent === I::HowToRecordEvaluations) {
            return $role === R::Catequista
                ? ['route' => 'catequista.evaluaciones.index', 'steps' => [
                    'Entra a Evaluaciones y selecciona el grupo asignado y la unidad a evaluar.',
                    'Captura lo obtenido y el total posible de cada rubro; la pantalla calcula la calificación.',
                    'Revisa los valores y pulsa Guardar calificaciones en el módulo.',
                ]]
                : ['route' => 'secretaria.evaluaciones.index', 'steps' => [
                    'Entra a Evaluaciones y selecciona periodo, grupo y unidad.',
                    'Captura las calificaciones de los alumnos por rubro en la tabla.',
                    'Revisa los valores y pulsa Guardar evaluaciones en el módulo.',
                ]];
        }

        return match ($intent) {
            I::HowToRegisterStudent => $this->registration('alumnos', 'Alumnos', 'Nuevo alumno', 'nombre, apellidos, comunidad y fecha de nacimiento según el formulario'),
            I::HowToRegisterTutor => $this->registration('tutores', 'Tutores', 'Nuevo tutor', 'nombre, apellidos, teléfono y alumno asignado según el formulario'),
            I::HowToRegisterInscription => $this->registration('inscripciones', 'Inscripciones', 'Nueva inscripción', 'alumno y grupo; comprueba antes el periodo de trabajo seleccionado'),
            I::HowToAssignGroup => $this->registration('asigna_grupo', 'Asignación de grupos', 'Nueva asignación', 'comunidad, grupo, nivel y catequista; comprueba el periodo de trabajo'),
            I::HowToManageCommunities => $this->registration('comunidades', 'Comunidades', 'Nueva comunidad', 'nombre de la comunidad'),
            I::HowToManagePeriods => $this->registration('periodos', 'Periodos', 'Nuevo periodo', 'fecha de inicio, fecha de fin y estado'),
            I::HowToManageLevels => $this->registration('niveles', 'Niveles', 'Nuevo nivel', 'nombre del nivel'),
            I::HowToManageUnits => $this->registration('unidades', 'Unidades', 'Nueva unidad', 'nivel, número y nombre de la unidad'),
            I::HowToManageRubrics => $this->registration('rubros', 'Rubros', 'Nuevo rubro', 'nombre y valor del rubro, entre 0 y 100'),
            I::HowToManageUsers => ['route' => 'secretaria.usuarios.pendientes', 'steps' => [
                'Entra a Usuarios pendientes y revisa la solicitud y el rol solicitado.',
                'Usa Aprobar para aceptar el rol solicitado o Bloquear para impedir el acceso.',
                'Confirma la acción en la pantalla; el chatbot no modifica cuentas.',
            ]],
            default => null,
        };
    }

    private function registration(string $route, string $module, string $button, string $fields): array
    {
        return ['route' => 'secretaria.'.$route.'.index', 'steps' => [
            'Entra al módulo '.$module.'.',
            'Selecciona '.$button.'.',
            'Completa '.$fields.'.',
            'Revisa el formulario y pulsa Guardar en el módulo.',
        ]];
    }
}
