<?php

namespace App\Services\Chatbot\Rules;

use App\Enums\CatequesisCapability as C;
use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;

final class RuleCatalog
{
    public function capability(I $intent): ?C
    {
        return match ($intent) {
            I::ViewMyGroups => C::ViewGroups,
            I::ViewGroupStudents => C::ViewGroupStudents,
            I::ViewAttendanceList => C::ViewAttendanceList,
            I::ViewEvaluations => C::ViewEvaluations,
            I::ViewBoleta => C::ViewBoletas,
            I::HowToRecordEvaluations => C::ManageEvaluations,
            I::HowToRegisterStudent, I::ModifyStudent => C::ManageStudents,
            I::HowToRegisterTutor => C::ManageTutors,
            I::HowToRegisterInscription => C::ManageInscriptions,
            I::HowToAssignGroup => C::ManageAssignments,
            I::HowToManageCommunities => C::ManageCommunities,
            I::HowToManagePeriods => C::ManagePeriods,
            I::HowToManageLevels => C::ManageLevels,
            I::HowToManageUnits => C::ManageUnits,
            I::HowToManageRubrics => C::ManageRubrics,
            I::HowToManageUsers => C::ManageUsers,
            I::HelpSystem, I::Unknown, I::ModifyAdministrativeData => null,
        };
    }

    /** Contrato de selección; no contiene permisos de roles. @return list<string> */
    public function acceptedResources(I $intent): array
    {
        return match ($intent) {
            I::ViewMyGroups, I::ViewGroupStudents, I::ViewAttendanceList => ['assignmentId'],
            I::ViewBoleta => ['assignmentId', 'inscriptionId'],
            I::ViewEvaluations, I::HowToRecordEvaluations => ['assignmentId', 'inscriptionId', 'studentId', 'evaluationId'],
            default => [],
        };
    }

    /** @return list<string> */
    public function requiredResources(I $intent): array
    {
        return match ($intent) {
            I::ViewGroupStudents, I::ViewAttendanceList => ['assignmentId'],
            I::ViewBoleta => ['assignmentId', 'inscriptionId'],
            default => [],
        };
    }

    public function isAdministrative(I $intent): bool
    {
        return in_array($intent, [
            I::HowToRegisterStudent, I::HowToRegisterTutor, I::HowToRegisterInscription,
            I::HowToAssignGroup, I::HowToManageCommunities, I::HowToManagePeriods,
            I::HowToManageLevels, I::HowToManageUnits, I::HowToManageRubrics,
            I::HowToManageUsers, I::ModifyStudent, I::ModifyAdministrativeData,
        ], true);
    }

    /** Etiquetas explicativas DESPUÉS de autorizar; nunca conceden capacidades. */
    public function allowedRule(R $role, I $intent): string
    {
        return match ($role) {
            R::Catequista => match ($intent) {
                I::ViewMyGroups => 'CAT-002',
                I::ViewGroupStudents => 'CAT-003',
                I::ViewAttendanceList => 'CAT-005',
                I::HowToRecordEvaluations => 'CAT-006',
                default => 'CAT-008',
            },
            R::Secretaria => match ($intent) {
                I::HowToRegisterStudent => 'SEC-001',
                I::HowToRegisterInscription => 'SEC-002',
                I::HowToAssignGroup => 'SEC-003',
                I::HowToManageUsers => 'SEC-004',
                I::HowToRegisterTutor => 'SEC-005',
                I::HowToManageCommunities => 'SEC-006',
                I::HowToManagePeriods => 'SEC-007',
                I::HowToManageLevels => 'SEC-008',
                I::HowToManageUnits => 'SEC-009',
                I::HowToManageRubrics => 'SEC-010',
                I::HowToRecordEvaluations => 'SEC-011',
                default => 'SEC-012',
            },
            R::Parroco => 'PAR-001',
            R::CoordinadorGeneral => 'CG-001',
            R::CoordinadorComunidades => 'CC-001',
        };
    }
}
