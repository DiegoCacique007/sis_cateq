<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;

/**
 * Si el modelo elige una intención de CONSULTA para un mensaje que pide modificar, borrar o registrar
 * datos («quiero cambiar la calificación de Ana»), se descarta y queda UNKNOWN (va a escalamiento).
 * Las preguntas de «cómo» (HOW_TO_*) no se filtran: «cómo registro un tutor» es legítima.
 */
final class WriteRequestFilter
{
    private const READ_INTENTS = [I::ViewMyGroups, I::ViewGroupStudents, I::ViewAttendanceList, I::ViewEvaluations, I::ViewBoleta];

    private const WRITE_VERBS = '/\b(?:cambi\w*|modific\w*|edit\w*|actualiz\w*|corrig\w*|correg\w*|borr\w*|elimin\w*|quit\w*|registr\w*|captur\w*|agreg\w*|pon(?:er|go|le|les|gan)?)\b/u';

    public function __construct(private readonly MessageNormalizer $normalizer = new MessageNormalizer) {}

    public function filter(I $intent, string $message): I
    {
        if (in_array($intent, self::READ_INTENTS, true) && preg_match(self::WRITE_VERBS, $this->normalizer->normalize($message))) {
            return I::Unknown;
        }

        return $intent;
    }
}
