@extends('layouts.app_parroquia_coordinador_comunidades')

@section('title', ($modulo ?? 'Módulo') . ' - Coordinador de Comunidades')
@section('header_title', $modulo ?? 'Módulo')
@section('header_subtitle', 'Supervisión por comunidad')

@section('content')
    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi {{ $icono ?? 'bi-gear' }} display-1 text-muted opacity-50 mb-3 d-block"></i>
            <h4 class="fw-bold text-secondary mb-2">{{ $modulo ?? 'Módulo' }}</h4>
            <p class="text-muted mb-0" style="max-width: 460px; margin: 0 auto;">
                Este módulo de supervisión por comunidad se encuentra en desarrollo.
                Próximamente podrá consultar información detallada desde este panel.
            </p>
        </div>
    </div>
@endsection
