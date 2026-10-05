<?php

namespace App\Services\Chatbot\Agents;

final readonly class AgentResponse
{
    /** Arrays de escalares proyectados explícitamente, nunca modelos. */
    public function __construct(
        public string $status,
        public string $code,
        public string $messageKey,
        public array $data = [],
        public array $suggestedActions = [],
        public array $metadata = [],
    ) {}
}
