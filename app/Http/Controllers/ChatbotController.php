<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chatbot\SendChatbotMessageRequest;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\Presentation\ChatbotResponseFormatter;
use Illuminate\Http\JsonResponse;

final class ChatbotController extends Controller
{
    public function __invoke(SendChatbotMessageRequest $request, ChatbotService $service, ChatbotResponseFormatter $formatter): JsonResponse
    {
        $data = $request->validated();
        $response = $service->handle($request, $data['message'],
            isset($data['assignment_id']) ? (int) $data['assignment_id'] : null,
            isset($data['inscription_id']) ? (int) $data['inscription_id'] : null,
            isset($data['student_id']) ? (int) $data['student_id'] : null,
            isset($data['evaluation_id']) ? (int) $data['evaluation_id'] : null);

        return response()->json($formatter->format($response), $response->status === 'ERROR' ? 500 : ($response->code === 'UNAUTHORIZED_CONTEXT' ? 403 : 200))
            ->header('Cache-Control', 'no-store, private');
    }
}
