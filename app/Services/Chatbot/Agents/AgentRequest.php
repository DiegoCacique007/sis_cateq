<?php

namespace App\Services\Chatbot\Agents;

use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleEngine;
use App\Services\Chatbot\Rules\RuleResult;

/** Identidad y selectores están en facts; no se acepta un ALLOWED construido por el cliente. */
final readonly class AgentRequest
{
    private function __construct(public ChatbotFacts $facts, public RuleResult $decision) {}

    public static function evaluate(ChatbotFacts $facts, RuleEngine $engine): self
    {
        return new self($facts, $engine->evaluate($facts));
    }
}
