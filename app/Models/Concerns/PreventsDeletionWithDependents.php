<?php

namespace App\Models\Concerns;

use App\Exceptions\DeletionBlockedException;

/**
 * Impide eliminar (delete o forceDelete) un modelo mientras tenga registros dependientes no eliminados.
 *
 * El modelo declara sus dependencias en dependentRelations(): nombre de la relación hasMany => [singular, plural].
 * Las relaciones hacia modelos con SoftDeletes ya excluyen los registros eliminados.
 *
 * LÍMITE: la barrera usa el evento «deleting» de Eloquent, así que no se aplica a borrados masivos con el
 * constructor de consultas (Modelo::where(...)->delete(), DB::table(...)->delete()), que no disparan eventos.
 * El código de la aplicación debe eliminar siempre instancia por instancia.
 */
trait PreventsDeletionWithDependents
{
    /** @return array<string, array{0: string, 1: string}> */
    abstract protected function dependentRelations(): array;

    public static function bootPreventsDeletionWithDependents(): void
    {
        static::deleting(function (self $model): void {
            if ($model->hasDependents()) {
                throw new DeletionBlockedException($model, $model->dependentsSummary());
            }
        });
    }

    /** @return array<string, int> Conteos por relación, solo los que tienen registros. */
    public function dependentCounts(): array
    {
        $counts = [];
        foreach (array_keys($this->dependentRelations()) as $relation) {
            $count = $this->{$relation}()->count();
            if ($count > 0) {
                $counts[$relation] = $count;
            }
        }

        return $counts;
    }

    public function hasDependents(): bool
    {
        return $this->dependentCounts() !== [];
    }

    /** Resumen legible: «3 grupos, 25 inscripciones y 1 evaluación». */
    public function dependentsSummary(): string
    {
        $labels = $this->dependentRelations();
        $parts = [];
        foreach ($this->dependentCounts() as $relation => $count) {
            $parts[] = $count.' '.$labels[$relation][$count === 1 ? 0 : 1];
        }
        $last = array_pop($parts);

        return $parts ? implode(', ', $parts).' y '.$last : (string) $last;
    }
}
