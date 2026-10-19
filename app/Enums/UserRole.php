<?php

namespace App\Enums;

enum UserRole: string
{
    case Secretaria = 'secretaria';
    case Catequista = 'catequista';
    case Parroco = 'parroco';
    case CoordinadorGeneral = 'coordinador_general';
    case CoordinadorComunidades = 'coordinador_comunidades';

    /** Nombre legible para pantallas y mensajes. */
    public function label(): string
    {
        return match ($this) {
            self::Secretaria => 'Secretaría',
            self::Catequista => 'Catequista',
            self::Parroco => 'Párroco',
            self::CoordinadorGeneral => 'Coordinador General',
            self::CoordinadorComunidades => 'Coordinador de Comunidades',
        };
    }
}
