<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chatbot\SendChatbotMessageRequest;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\Intent\ShadowClassificationTracker;
use App\Services\Chatbot\Presentation\ChatbotResponseFormatter;
use Illuminate\Http\JsonResponse;

final class ChatbotController extends Controller
{
    public function __invoke(SendChatbotMessageRequest $request, ChatbotService $service, ChatbotResponseFormatter $formatter, ShadowClassificationTracker $shadow): JsonResponse
    {
        $data = $request->validated();
        $response = $service->handle($request, $data['message'],
            isset($data['assignment_id']) ? (int) $data['assignment_id'] : null,
            isset($data['inscription_id']) ? (int) $data['inscription_id'] : null,
            isset($data['student_id']) ? (int) $data['student_id'] : null,
            isset($data['evaluation_id']) ? (int) $data['evaluation_id'] : null);

        $json = response()->json($formatter->format($response), $response->status === 'ERROR' ? 500 : ($response->code === 'UNAUTHORIZED_CONTEXT' ? 403 : 200))
            ->header('Cache-Control', 'no-store, private');

        // Clasificación en sombra pendiente (se ejecuta con afterResponse, después de send()). Con PHP como
        // módulo de Apache (mod_php) no hay fastcgi_finish_request: sin Content-Length, el navegador no da
        // la respuesta por completa hasta que termina esa clasificación. Con la longitud en bytes la recibe
        // al instante. mod_deflate la quitaría y volvería a esperar, así que se pide no comprimir esta
        // respuesta; si PHP comprime la salida (zlib.output_compression), la longitud no coincidiría y no se pone.
        if ($shadow->consumePending() && ! filter_var(ini_get('zlib.output_compression'), FILTER_VALIDATE_BOOLEAN)) {
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }
            $json->headers->set('Content-Length', self::contentLength($json->getContent()));
        }

        return $json;
    }

    /** Longitud en bytes (no en caracteres) del cuerpo, como exige Content-Length. */
    public static function contentLength(string $body): string
    {
        return (string) strlen($body);
    }
}
