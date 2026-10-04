<?php

namespace App\Enums;

enum UserRole: string
{
    case Secretaria = 'secretaria';
    case Catequista = 'catequista';
    case Parroco = 'parroco';
    case CoordinadorGeneral = 'coordinador_general';
    case CoordinadorComunidades = 'coordinador_comunidades';
}
