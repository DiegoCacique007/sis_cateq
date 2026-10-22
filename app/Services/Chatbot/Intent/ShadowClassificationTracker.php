<?php

namespace App\Services\Chatbot\Intent;

/**
 * Indica a ChatbotController si esta petición dejó una clasificación en sombra para después de la
 * respuesta. Se registra como scoped (una instancia por petición).
 */
final class ShadowClassificationTracker
{
    private bool $pending = false;

    private ?int $eventId = null;

    public function markPending(): void
    {
        $this->pending = true;
    }

    /** Id del evento de la bitácora de esta petición; la sombra lo completa después de la respuesta. */
    public function recordEvent(int $id): void
    {
        $this->eventId = $id;
    }

    public function eventId(): ?int
    {
        return $this->eventId;
    }

    /** Devuelve si había una clasificación pendiente y la da por atendida. */
    public function consumePending(): bool
    {
        $pending = $this->pending;
        $this->pending = false;

        return $pending;
    }
}
