<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;

final class IntentPatternCatalog
{
    /** Prioridad local: modificación 30, orientación 20, consulta 10, ayuda 0.
     * @return array<string, array{intent: I, priority: int, pattern: string}>
     */
    public function patterns(): array
    {
        $modify = '(?:cambiar|cambia|modificar|modifica|editar|edita|actualizar|actualiza|corregir|corrige)';
        $register = '(?:registrar|registro|agregar|agrego|dar de alta|doy de alta|capturar|capturo)';
        $how = '(?:como(?: puedo| se| debo)?|ayuda para|ayudame a)';
        $read = '(?:ver|veo|consultar|consulto|muestrame|mostrar|ensename)';
        $manage = '(?:administrar|administro|gestionar|gestiono|manejar|manejo|crear|creo|registrar|registro|agregar|agrego)';
        $gap = '(?:\s+(?:un|una|el|la|los|las|mi|mis|este|esta|de|del|datos|informacion))*\s+';
        $rules = [
            'MOD-STUDENT' => [I::ModifyStudent, 30, "$modify$gap(?:alumno|alumna|alumnos|alumnas)\\b"],
            'MOD-ADMIN' => [I::ModifyAdministrativeData, 30, "$modify$gap(?:administrativ[oa]s?|tutores?|comunidades?|periodos?|niveles?|unidades?|rubros?|usuarios?|grupos?)\\b"],
            'HOW-EVALUATIONS' => [I::HowToRecordEvaluations, 20, "$how\\s+(?:(?:registrar|registro|capturar|capturo|poner|pongo)$gap(?:calificaciones?|evaluaciones?|calificacion|evaluacion)|evaluar\\b)"],
            'HOW-STUDENT' => [I::HowToRegisterStudent, 20, "$how\\s+$register$gap(?:alumnos?|alumnas?)\\b|\\bdar de alta$gap(?:alumnos?|alumnas?)\\b"],
            'HOW-TUTOR' => [I::HowToRegisterTutor, 20, "(?:$how\\s+)?(?:registrar|registro|agregar|agrego)$gap(?:tutor|tutores|tutora|tutoras)\\b"],
            'HOW-INSCRIPTION' => [I::HowToRegisterInscription, 20, "(?:$how\\s+)?(?:inscribir|inscribo)$gap(?:alumnos?|alumnas?)\\b|(?:$how\\s+)?(?:registrar|registro|hacer|hago)$gap(?:inscripcion|inscripciones)\\b"],
            'HOW-ASSIGN' => [I::HowToAssignGroup, 20, "$how\\s+(?:asignar|asigno)$gap(?:catequistas?|grupos?)\\b"],
            'VIEW-GROUPS' => [I::ViewMyGroups, 10, '\bmis grupos\b|\bque grupos tengo\b|\bgrupos? asignados?\b'],
            'VIEW-STUDENTS' => [I::ViewGroupStudents, 10, "(?<!de )\\bmis alumnos\\b|\\balumnos de mi grupo\\b|$read$gap(?:alumnos?|alumnas?)\\b|\\blista de alumnos\\b"],
            'VIEW-ATTENDANCE' => [I::ViewAttendanceList, 10, '\b(?:lista|pdf) de asistencia\b|\b(?:consultar|consulto|generar|genera)\s+(?:(?:mi|la)\s+)?asistencia\b'],
            'VIEW-EVALUATIONS' => [I::ViewEvaluations, 10, "$read$gap(?:calificaciones|evaluaciones|calificacion|evaluacion)\\b|\\bcalificaciones de mis alumnos\\b"],
            'VIEW-BOLETA' => [I::ViewBoleta, 10, "(?:$read|generar|genera|descargar)$gap(?:boleta|boletas)\\b"],
            'HELP' => [I::HelpSystem, 0, '^ayuda$|\bque puedes hacer\b|\bcomo funciona el sistema\b|\bque puedo consultar\b'],
        ];
        foreach ([
            'COMMUNITIES' => [I::HowToManageCommunities, 'comunidad|comunidades'],
            'PERIODS' => [I::HowToManagePeriods, 'periodo|periodos'],
            'LEVELS' => [I::HowToManageLevels, 'nivel|niveles'],
            'UNITS' => [I::HowToManageUnits, 'unidad|unidades'],
            'RUBRICS' => [I::HowToManageRubrics, 'rubro|rubros'],
            'USERS' => [I::HowToManageUsers, 'usuario|usuarios'],
        ] as $id => [$intent, $subject]) {
            $rules['HOW-'.$id] = [$intent, 20, "$how\\s+$manage$gap(?:$subject)\\b"];
        }

        $catalog = [];
        foreach ($rules as $id => [$intent, $priority, $pattern]) {
            $catalog[$id] = ['intent' => $intent, 'priority' => $priority, 'pattern' => '~\b(?:'.$pattern.')~u'];
        }

        return $catalog;
    }

    /** Bloqueos conservadores: no convertir ejecución inexistente o negación en consulta. */
    public function guards(): array
    {
        return [
            'NEGATED-REQUEST' => '~\b(?:no|nunca|tampoco)\b~u',
            'UNSUPPORTED-ATTENDANCE' => '~\b(?:registra|registrar|registro|captura|capturar|capturo|guarda|guardar|marca|marcar|toma|tomar|pasa|pasar)\b.*\basistencia\b~u',
            'UNSUPPORTED-EVALUATION-WRITE' => '~^(?!.*\b(?:como|ayuda|ayudame)\b).*\b(?:registra|registrar|registro|captura|capturar|capturo|pon|poner|guarda|guardar)\b.*\b(?:calificacion|calificaciones|evaluacion|evaluaciones)\b~u',
        ];
    }
}
