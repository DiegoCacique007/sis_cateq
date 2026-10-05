<?php

namespace App\Services\Chatbot\Presentation;

use App\Services\Chatbot\Agents\AgentResponse;
use Illuminate\Support\Facades\Route;

final class ChatbotResponseFormatter
{
    public function format(AgentResponse $response): array
    {
        $status = match ($response->status) {
            'OK' => 'success', 'DENIED' => 'denied', 'ESCALATE' => 'denied',
            'UNSUPPORTED' => 'unsupported', 'RESOURCE_SELECTION_REQUIRED' => 'selection_required',
            'MISSING_CONTEXT' => 'missing_context', 'AMBIGUOUS' => 'ambiguous', default => 'error',
        };
        $message = match ($response->messageKey) {
            'chatbot.groups_ready' => 'Estas son tus asignaciones disponibles.',
            'chatbot.students_ready' => 'Estos son los alumnos de la asignación seleccionada.',
            'chatbot.evaluations_ready' => 'Estas son las evaluaciones disponibles para tu selección.',
            'chatbot.help_ready' => 'Puedo ayudarte con estas consultas y procedimientos:',
            'chatbot.guidance_ready' => 'Sigue estos pasos en el módulo del sistema. No realizaré cambios desde el chat.',
            'chatbot.select_assignment' => 'Selecciona una asignación para continuar.',
            'chatbot.attendance_in_group_module' => 'Abre Mi grupo para consultar y descargar la lista de asistencia. No hay registro digital de asistencia.',
            'chatbot.boleta_in_existing_module' => 'La boleta se consulta en el módulo existente.',
            'chatbot.access_denied' => 'No tienes acceso a esta solicitud o al recurso seleccionado.',
            'chatbot.contact_responsible' => 'Esta operación requiere atención en el módulo correspondiente.',
            'chatbot.function_unavailable' => 'Esta solicitud no está disponible. Prueba con «¿Qué puedes hacer?».',
            'chatbot.clarify_intent' => 'Identifiqué varias solicitudes. Escribe una sola consulta para continuar.',
            'chatbot.inconsistent_assignment' => 'La asignación tiene datos inconsistentes. Solicita su revisión antes de continuar.',
            'chatbot.context_required' => match ($response->code) {
                'MISSING_PERIOD' => 'Selecciona un periodo de trabajo en el sistema y vuelve a consultar.',
                'MISSING_COMMUNITY' => 'Tu cuenta necesita una comunidad asignada. Acude a Secretaría.',
                default => 'Falta seleccionar un recurso. Realiza la consulta en el módulo correspondiente.',
            },
            default => 'No pude procesar la solicitud en este momento.',
        };
        $items = $response->data['steps'] ?? [];
        foreach ($response->data['groups'] ?? [] as $group) {
            $items[] = $this->groupLabel($group);
        }
        foreach ($response->data['students'] ?? [] as $student) {
            $items[] = $student['fullName'];
        }
        foreach ($response->data['evaluations'] ?? [] as $evaluation) {
            $items[] = $evaluation['student']['fullName'].' — '.$evaluation['unit'].' — '.$evaluation['rubric'].': '.$evaluation['grade'];
        }
        foreach ($response->data['availableIntents'] ?? [] as $intent) {
            $items[] = $this->intentLabel($intent);
        }
        $options = array_map(fn ($group) => ['id' => $group['assignmentId'], 'label' => $this->groupLabel($group)], $response->data['options'] ?? []);
        if ($status === 'selection_required' && ! $options) {
            $message = 'No hay asignaciones disponibles para seleccionar en este contexto. Solicita una revisión a Secretaría.';
        } elseif ($status === 'success' && ! $items) {
            $message .= ' No se encontraron registros.';
        }
        $actions = [];
        foreach ($response->suggestedActions as $action) {
            if ($action['type'] === 'navigate' && Route::has($action['route'])) {
                $actions[] = ['type' => 'navigate', 'label' => 'Abrir módulo', 'url' => route($action['route'], $action['parameters'] ?? [], false)];
            } elseif ($action['type'] === 'contact') {
                $message .= $action['responsible'] === 'secretaria' ? ' Acude a Secretaría.' : ' Consulta al encargado del sistema.';
            }
        }

        return ['status' => $status, 'code' => $response->code, 'message' => $message,
            'data' => ['items' => array_values($items), 'options' => $options], 'actions' => $actions,
            'meta' => ['hasMore' => (bool) ($response->metadata['hasMore'] ?? false)]];
    }

    private function groupLabel(array $group): string
    {
        return $group['name'].' — '.$group['level'].' — '.$group['community'].' — '.$group['period'];
    }

    private function intentLabel(string $intent): string
    {
        return match ($intent) {
            'VIEW_MY_GROUPS' => 'Consultar mis grupos', 'VIEW_GROUP_STUDENTS' => 'Consultar alumnos de un grupo',
            'VIEW_ATTENDANCE_LIST' => 'Consultar la lista de asistencia', 'VIEW_EVALUATIONS' => 'Consultar evaluaciones',
            'VIEW_BOLETA' => 'Orientación para consultar boletas en su módulo',
            'HOW_TO_RECORD_EVALUATIONS' => 'Cómo registrar evaluaciones', 'HOW_TO_REGISTER_STUDENT' => 'Cómo registrar un alumno',
            'HOW_TO_REGISTER_TUTOR' => 'Cómo registrar un tutor', 'HOW_TO_REGISTER_INSCRIPTION' => 'Cómo hacer una inscripción',
            'HOW_TO_ASSIGN_GROUP' => 'Cómo asignar un grupo', 'HOW_TO_MANAGE_COMMUNITIES' => 'Cómo administrar comunidades',
            'HOW_TO_MANAGE_PERIODS' => 'Cómo administrar periodos', 'HOW_TO_MANAGE_LEVELS' => 'Cómo administrar niveles',
            'HOW_TO_MANAGE_UNITS' => 'Cómo administrar unidades', 'HOW_TO_MANAGE_RUBRICS' => 'Cómo administrar rubros',
            'HOW_TO_MANAGE_USERS' => 'Cómo administrar usuarios pendientes', default => 'Consulta del sistema',
        };
    }
}
