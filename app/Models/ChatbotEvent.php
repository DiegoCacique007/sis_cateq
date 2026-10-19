<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** Evento de la bitácora del chatbot. Solo códigos, rol y día; nunca datos personales. */
class ChatbotEvent extends Model
{
    use MassPrunable;

    /** Días que se conservan los eventos. */
    public const RETENTION_DAYS = 90;

    /** Días que se conserva la forma del mensaje no reconocido. */
    public const SHAPE_RETENTION_DAYS = 30;

    protected $table = 'chatbot_events';

    public $timestamps = false;

    protected $fillable = [
        'occurred_on', 'role', 'intent', 'resolution', 'guard', 'status', 'code', 'with_selection', 'message_shape',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'with_selection' => 'boolean',
    ];

    public function prunable(): Builder
    {
        return static::query()->where('occurred_on', '<', today()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Borra los eventos vencidos y vacía las formas de mensaje antiguas.
     *
     * @return array{deleted: int, cleared: int}
     */
    public static function purgeExpired(): array
    {
        $deleted = (new static)->pruneAll();
        $cleared = static::query()->where('occurred_on', '<', today()->subDays(self::SHAPE_RETENTION_DAYS))
            ->whereNotNull('message_shape')->update(['message_shape' => null]);

        return ['deleted' => $deleted, 'cleared' => $cleared];
    }
}
