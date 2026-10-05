<?php

namespace App\Services\Chatbot\Rules;

use App\Enums\CatequesisCapability as C;
use App\Enums\ChatbotIntent as I;
use App\Enums\ChatbotRuleResult as S;
use App\Enums\UserRole as R;
use App\Services\Authorization\AccessDecision;
use App\Services\Authorization\CatequesisAccess;

final class RuleEngine
{
    public function __construct(
        private readonly CatequesisAccess $access,
        private readonly RuleCatalog $catalog,
    ) {}

    public function evaluate(ChatbotFacts $facts): RuleResult
    {
        $context = $facts->context;
        // UserRole tipado impide roles desconocidos. No sustituye resolver por petición.
        if ($context->userId < 1 || $context->status !== 'aprobado') {
            return new RuleResult(S::Denied, 'UNAUTHORIZED_CONTEXT', ['AUTH-002']);
        }
        $rules = ['AUTH-001'];
        if ($facts->intent === I::Unknown) {
            return new RuleResult(S::Unsupported, 'UNKNOWN_INTENT', [...$rules, 'INT-001', 'ESC-002'], recommendedResponsible: 'system_human');
        }
        if ($facts->intent === I::ModifyAdministrativeData) {
            return new RuleResult(S::Escalate, 'ADMINISTRATIVE_EXECUTION_UNSUPPORTED', [...$rules, 'EXEC-001', 'ESC-001'], recommendedResponsible: R::Secretaria->value);
        }

        $capability = $this->catalog->capability($facts->intent);
        if ($capability !== null) {
            $decision = $this->access->can($context, $capability);
            if (! $decision->isAllowed()) {
                return $this->rejected($facts, $decision, $rules, $capability);
            }
            $rules[] = 'CAP-001';
        }
        // Ni siquiera Secretaría puede ejecutar modificaciones conversacionales.
        if ($facts->intent === I::ModifyStudent) {
            return new RuleResult(S::Unsupported, 'EXECUTION_UNSUPPORTED', [...$rules, 'EXEC-001'], $capability);
        }

        $resources = $facts->resources();
        foreach ($resources as $id) {
            if ($id < 1) {
                return new RuleResult(S::Denied, 'INVALID_RESOURCE_ID', [...$rules, 'RES-003'], $capability);
            }
        }
        // No ignorar selectores ni autorizar relaciones entre IDs independientes.
        if (array_diff(array_keys($resources), $this->catalog->acceptedResources($facts->intent))
            || ($facts->intent !== I::ViewBoleta && count($resources) > 1)) {
            return new RuleResult(S::Unsupported, 'RESOURCE_SELECTION_UNSUPPORTED', [...$rules, 'CTX-004'], $capability);
        }
        $missing = array_values(array_diff($this->catalog->requiredResources($facts->intent), array_keys($resources)));
        if ($missing) {
            return new RuleResult(S::MissingContext, 'MISSING_RESOURCE', [...$rules, 'CTX-003'], $capability, ['required' => $missing]);
        }

        $ambiguity = null;
        foreach ($resources as $field => $id) {
            $decision = match ($field) {
                'assignmentId' => $facts->intent === I::ViewMyGroups
                    ? $this->access->canViewAsignacion($context, $id)
                    : $this->access->canUseAsignacion($context, $id),
                'inscriptionId' => $facts->intent === I::ViewBoleta
                    ? $this->access->canViewBoleta($context, $id)
                    : $this->access->canViewInscripcion($context, $id),
                'studentId' => $this->access->canViewAlumno($context, $id),
                'evaluationId' => $facts->intent === I::HowToRecordEvaluations
                    ? $this->access->canManageEvaluacion($context, $id)
                    : $this->access->canViewEvaluacion($context, $id),
            };
            // Con varios selectores, un rechazo prevalece sobre una ambigüedad.
            if ($decision->state === 'ambiguous') {
                $ambiguity = $decision;
            } elseif (! $decision->isAllowed()) {
                return $this->rejected($facts, $decision, $rules, $capability);
            }
        }
        if ($ambiguity !== null) {
            return $this->rejected($facts, $ambiguity, $rules, $capability);
        }
        if ($facts->intent === I::ViewBoleta) {
            $decision = $this->access->canUseInscripcionAsignacion($context, $facts->inscriptionId, $facts->assignmentId);
            if (! $decision->isAllowed()) {
                return $this->rejected($facts, $decision, $rules, $capability);
            }
            $rules[] = 'RES-004';
        }
        if ($resources) {
            $rules[] = 'RES-001';
        }
        if ($facts->intent === I::HelpSystem) {
            $rules[] = 'HELP-001';
        } else {
            if ($context->role === R::Catequista) {
                $rules[] = 'CAT-001';
            }
            $rules[] = $this->catalog->allowedRule($context->role, $facts->intent);
        }

        return new RuleResult(S::Allowed, $facts->intent->value.'_ALLOWED', $rules, $capability);
    }

    /** @param list<string> $rules */
    private function rejected(ChatbotFacts $facts, AccessDecision $decision, array $rules, C $capability): RuleResult
    {
        if ($decision->state === 'missing_context') {
            $rules[] = $decision->code === 'MISSING_PERIOD' ? 'CTX-001' : 'CTX-002';

            return new RuleResult(S::MissingContext, $decision->code, $rules, $capability);
        }
        if ($decision->state === 'ambiguous') {
            return new RuleResult(S::Ambiguous, $decision->code, [...$rules, 'AMB-001'], $capability);
        }
        $rules[] = $decision->code === 'ROLE_NOT_ALLOWED' ? 'INT-002' : 'RES-002';
        if ($decision->code === 'OUTSIDE_SCOPE') {
            if ($facts->context->role === R::Catequista) {
                $rules[] = 'CAT-004';
            } elseif ($facts->context->role === R::CoordinadorComunidades) {
                $rules[] = 'CC-002';
            }
        }
        $responsible = null;
        if ($this->catalog->isAdministrative($facts->intent)) {
            $rules[] = match ($facts->context->role) {
                R::Catequista => 'CAT-007',
                R::Parroco => 'PAR-002',
                R::CoordinadorGeneral => 'CG-002',
                R::CoordinadorComunidades => 'CC-003',
                R::Secretaria => 'INT-002',
            };
            $rules[] = 'ESC-001';
            $responsible = R::Secretaria->value;
        }

        return new RuleResult(S::Denied, $decision->code, $rules, $capability, recommendedResponsible: $responsible);
    }
}
