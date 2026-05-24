@extends('layouts.app_parroquia_coordinador_general')

@section('title', ($modulo ?? 'Módulo') . ' - Coordinador General')
@section('header_title', $modulo ?? 'Módulo')
@section('header_subtitle', 'Coordinación general de catequesis')

@section('content')
    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi {{ $icono ?? 'bi-gear' }} display-1 text-muted opacity-50 mb-3 d-block"></i>
            <h4 class="fw-bold text-secondary mb-2">{{ $modulo ?? 'Módulo' }}</h4>
            <p class="text-muted mb-0" style="max-width: 460px; margin: 0 auto;">
                Este módulo de coordinación se encuentra en desarrollo.
                Próximamente podrá consultar información detallada desde este panel.
            </p>
        </div>
    </div>
@endsection
