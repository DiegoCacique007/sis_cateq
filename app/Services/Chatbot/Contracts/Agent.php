<?php

namespace App\Services\Chatbot\Contracts;

use App\Enums\ChatbotIntent;
use App\Services\Chatbot\Agents\AgentRequest;
use App\Services\Chatbot\Agents\AgentResponse;

interface Agent
{
    public function supports(ChatbotIntent $intent): bool;

    public function handle(AgentRequest $request): AgentResponse;
}
