<?php

namespace App\Services\Chatbot\Intent;

/**
 * «Forma» de un mensaje no reconocido para la bitácora: conserva solo las palabras de un
 * vocabulario cerrado y sustituye cualquier otra palabra o número por «…».
 *
 * Así se ven las maneras de pedir que el resolvedor no entiende sin guardar nombres de
 * alumnos, tutores ni otros datos personales. Las palabras están normalizadas como en
 * MessageNormalizer (minúsculas y sin acentos; se conserva la ñ).
 *
 * REGLA AL AMPLIAR EL VOCABULARIO: no añadir palabras que también sean nombres de persona
 * (rosa, luz, paz, angel, dolores, pilar, mercedes, consuelo, guadalupe, jesus, maria, jose,
 * cruz, esperanza, victoria, amparo, socorro, concepcion, asuncion, rocio, soledad, cielo,
 * alba, reyes, santos, trinidad, natividad, rosario, lupe, chuy…). Una prueba lo comprueba.
 */
final class MessageShaper
{
    private const MASK = '…';

    private const MAX_LENGTH = 200;

    private const VOCABULARY = [
        // Artículos, preposiciones, pronombres y conjunciones.
        'a', 'al', 'de', 'del', 'el', 'la', 'los', 'las', 'lo', 'le', 'les', 'un', 'una', 'unos', 'unas',
        'mi', 'mis', 'tu', 'tus', 'su', 'sus', 'este', 'esta', 'estos', 'estas', 'ese', 'esa', 'esos', 'esas',
        'y', 'o', 'u', 'ni', 'que', 'en', 'con', 'sin', 'por', 'para', 'sobre', 'entre', 'hasta', 'desde',
        'me', 'te', 'se', 'nos', 'yo', 'mas', 'muy', 'ya', 'si', 'no', 'pero', 'porque', 'tambien', 'ademas',
        'todo', 'todos', 'todas', 'nada', 'algo', 'otro', 'otra', 'hoy', 'ahora',
        // Interrogativos y fórmulas de cortesía.
        'como', 'donde', 'cuando', 'cual', 'cuales', 'cuanto', 'cuantos', 'quien', 'quienes',
        'hola', 'gracias', 'favor', 'porfavor', 'ayuda', 'ayudame', 'buenas', 'buenos', 'dias', 'tardes',
        // Verbos auxiliares y de intención.
        'quiero', 'quisiera', 'necesito', 'puedo', 'puedes', 'puede', 'podemos', 'pueden', 'podrias', 'debo',
        'tengo', 'tiene', 'hay', 'es', 'son', 'estan', 'deja', 'dejan', 'permite', 'logro', 'sabe',
        'saber', 'entiendo', 'recuerdo', 'funciona', 'sirve', 'falta', 'faltan',
        // Verbos de consulta y de reporte.
        'ver', 'veo', 'consultar', 'consulto', 'muestrame', 'mostrar', 'muestra', 'muestran', 'ensename',
        'aparece', 'aparecen', 'sale', 'salen', 'carga', 'cargan', 'cargar', 'encuentro', 'encontrar',
        'localizo', 'buscar', 'busco', 'descargar', 'descargo', 'generar', 'genera', 'imprimir', 'abrir',
        // Verbos de escritura y de gestión.
        'registrar', 'registro', 'registra', 'registran', 'registre', 'capturar', 'captura', 'capturo',
        'guardar', 'guarda', 'guardan', 'poner', 'pon', 'pongo', 'pone', 'subir', 'sube', 'meter', 'mete',
        'ingresar', 'ingresa', 'anotar', 'anota', 'calificar', 'califica', 'califico', 'evaluar', 'evalua',
        'evaluo', 'modificar', 'modifica', 'cambiar', 'cambia', 'editar', 'edita', 'actualizar', 'corregir',
        'borrar', 'eliminar', 'inscribir', 'inscribo', 'asignar', 'asigno', 'administrar', 'gestionar',
        'crear', 'agregar', 'dar', 'doy', 'alta', 'hacer', 'hago', 'haz', 'pasar', 'pasa', 'marcar', 'tomar',
        'aprobar', 'bloquear',
        // Sustantivos del sistema y sus módulos.
        'alumno', 'alumnos', 'alumna', 'alumnas', 'grupo', 'grupos', 'calificacion', 'calificaciones',
        'evaluacion', 'evaluaciones', 'nota', 'notas', 'promedio', 'boleta', 'boletas', 'asistencia', 'lista',
        'pdf', 'tutor', 'tutores', 'inscripcion', 'inscripciones', 'asignacion', 'asignaciones', 'comunidad',
        'comunidades', 'periodo', 'periodos', 'nivel', 'niveles', 'unidad', 'unidades', 'rubro', 'rubros',
        'usuario', 'usuarios', 'catequista', 'catequistas', 'secretaria', 'parroco', 'coordinador',
        'coordinadora', 'sistema', 'modulo', 'pantalla', 'boton', 'menu', 'chatbot', 'asistente', 'cuenta',
        'contraseña', 'sesion', 'datos', 'informacion', 'actual', 'correcto',
    ];

    private readonly array $words;

    public function __construct(private readonly MessageNormalizer $normalizer = new MessageNormalizer)
    {
        $this->words = array_fill_keys(self::VOCABULARY, true);
    }

    public function shape(string $message): string
    {
        $tokens = preg_split('/\s+/u', $this->normalizer->normalize($message), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        $shape = array_map(function (string $token): string {
            $word = trim($token, ".,;:()\"'«»-");

            return isset($this->words[$word]) ? $word : self::MASK;
        }, $tokens);

        return mb_substr(implode(' ', $shape), 0, self::MAX_LENGTH);
    }

    /** @return list<string> */
    public static function vocabulary(): array
    {
        return self::VOCABULARY;
    }
}
