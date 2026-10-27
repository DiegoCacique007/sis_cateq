@extends($layout_role ?? 'layouts.app_parroquia_admin')
@section('title', 'Seleccionar boleta')
@section('header_title', 'Boletas de Evaluación')
@section('content')
    <section class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width:540px">
        <div class="card-body p-4">
            <h2 class="h5 text-primary">Selecciona la asignación</h2>
            <p>{{ $inscripcion->alumno->nombre_completo }} · {{ $inscripcion->grupo->nombre }}</p>
            <p class="small text-muted">Hay varias asignaciones válidas para este alumno en el periodo seleccionado. Elige el nivel y catequista de la boleta.</p>
            <form method="GET" action="{{ route(($route_prefix ?? 'secretaria.').'boletas.generar', $inscripcion->id) }}">
                <label for="asignacion-boleta" class="form-label">Nivel y catequista</label>
                <select id="asignacion-boleta" name="asignacion_id" class="form-select mb-3" required>
                    <option value="">Selecciona una asignación</option>
                    @foreach($assignments as $option)
                        <option value="{{ $option->id }}">{{ $option->nivel->nivel }} · {{ $option->catequista->name }} · {{ $option->comunidad->comunidad }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary rounded-pill" type="submit">Abrir boleta</button>
            </form>
        </div>
    </section>
@endsection
