<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;

class SendChatbotMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = ['message' => ['required', 'string', 'min:2', 'max:2000']];
        foreach (['assignment_id', 'inscription_id', 'student_id', 'evaluation_id'] as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX];
        }
        foreach (['role', 'user_id', 'userId', 'status', 'community_id', 'communityId', 'comunidad_id', 'periodo_activo_id', 'intent'] as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }
}
