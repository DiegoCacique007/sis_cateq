<?php

namespace App\Services\Chatbot\Intent;

use App\Services\Chatbot\Contracts\IntentClassifier;

/** Sin proveedor configurado: nunca clasifica. */
final class NullIntentClassifier implements IntentClassifier
{
    public function classify(string $message, array $allowed): IntentClassification
    {
        return IntentClassification::failed('DISABLED');
    }
}
