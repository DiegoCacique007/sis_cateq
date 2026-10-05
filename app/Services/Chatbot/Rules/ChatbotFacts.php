<?php

namespace App\Services\Chatbot\Rules;

use App\Enums\ChatbotIntent;
use App\Services\Authorization\AccessContext;

/** El contexto se resuelve en el servidor por operación, nunca desde el mensaje. */
final readonly class ChatbotFacts
{
    public function __construct(
        public AccessContext $context,
        public ChatbotIntent $intent,
        public ?int $assignmentId = null,
        public ?int $inscriptionId = null,
        public ?int $studentId = null,
        public ?int $evaluationId = null,
    ) {}

    /** @return array<string, int> */
    public function resources(): array
    {
        return array_filter([
            'assignmentId' => $this->assignmentId,
            'inscriptionId' => $this->inscriptionId,
            'studentId' => $this->studentId,
            'evaluationId' => $this->evaluationId,
        ], static fn (?int $id) => $id !== null);
    }
}
