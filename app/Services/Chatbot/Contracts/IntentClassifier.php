<?php

namespace App\Services\Chatbot\Contracts;

use App\Enums\ChatbotIntent;
use App\Services\Chatbot\Intent\IntentClassification;

/**
 * Clasificador de intenciones independiente del proveedor (modelo local, remoto o ninguno).
 * Solo elige una intención de la lista; nunca responde al usuario ni decide permisos.
 * Nunca lanza excepciones: cualquier fallo se devuelve como outcome y se vuelve a las reglas.
 */
interface IntentClassifier
{
    /** @param  list<ChatbotIntent>  $allowed  Lista cerrada de intenciones que puede devolver. */
    public function classify(string $message, array $allowed): IntentClassification;
}
