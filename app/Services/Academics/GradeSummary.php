<?php

namespace App\Services\Academics;

use Illuminate\Support\Collection;

final class GradeSummary
{
    /** Fórmula de catequista: puntos capturados / valor total de rubros × 10. */
    public function calculate(Collection $unidades, Collection $evaluaciones, float $totalRubros): array
    {
        $promedios = [];
        $raw = [];
        foreach ($unidades as $unidad) {
            $grades = $evaluaciones->where('unidad_id', $unidad->id)->whereNotNull('calificacion');
            $value = $grades->isNotEmpty() && $totalRubros > 0 ? $grades->sum('calificacion') / $totalRubros * 10 : null;
            $promedios[$unidad->id] = $value === null ? null : round($value, 2);
            if ($value !== null) {
                $raw[] = $value;
            }
        }

        return ['unidades' => $promedios, 'final' => $raw ? round(array_sum($raw) / count($raw), 2) : null];
    }
}
