<?php

namespace App\Services\Chatbot\Intent;

final class MessageNormalizer
{
    public function normalize(string $message): string
    {
        $text = mb_strtolower($message, 'UTF-8');
        $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
        // Acentos descompuestos; se conserva la ñ y no se borran negaciones ni IDs.
        $text = preg_replace('/(?<=[aeiou])\x{0301}|(?<=u)\x{0308}/u', '', $text);
        $text = str_replace(['¿', '?', '¡', '!'], ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
