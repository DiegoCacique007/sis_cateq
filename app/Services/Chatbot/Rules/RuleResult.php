<?php

namespace App\Services\Chatbot\Rules;

use App\Enums\CatequesisCapability;
use App\Enums\ChatbotRuleResult;

final readonly class RuleResult
{
    /**
     * @param  list<string>  $matchedRules
     * @param  array<string, mixed>  $context  Solo metadatos, nunca registros ni IDs rechazados.
     * @param  'secretaria'|'system_human'|null  $recommendedResponsible
     */
    public function __construct(
        public ChatbotRuleResult $result,
        public string $code,
        public array $matchedRules,
        public ?CatequesisCapability $capability = null,
        public array $context = [],
        public ?string $recommendedResponsible = null,
    ) {}
}
