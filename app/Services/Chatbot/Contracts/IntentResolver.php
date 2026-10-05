<?php

namespace App\Services\Chatbot\Contracts;

use App\Services\Chatbot\Intent\IntentResolution;

interface IntentResolver
{
    public function resolve(string $message): IntentResolution;
}
