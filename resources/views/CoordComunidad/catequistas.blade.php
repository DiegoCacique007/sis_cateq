@extends('layouts.app_parroquia_coordinador_comunidades')

@section('title', 'Catequistas de mi Comunidad')
@section('header_title', 'Catequistas de mi Comunidad')

@section('content')
    <style>
        .catequistas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .catequista-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 145px;
        }

        .catequista-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .catequista-card .card-header-content {
            margin-bottom: 1rem;
        }

        .catequista-card .catequista-name {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .catequista-card .catequista-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .catequista-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .catequista-card .info-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .catequista-card .email-badge {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .catequista-card .status-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: #64748b;
            border: 1px dashed #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
        }

        .empty-state i {
            font-size: 2.4rem;
            color: #94a3b8;
            display: block;
            margin-bottom: 0.75rem;
        }
    </style>

    <div class="card card-parroquia module-card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 module-title">Listado de Catequistas</h5>
                <small class="text-muted">Consulta de catequistas registrados.</small>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                <i class="bi bi-person-workspace"></i>
                {{ $registros->total() }} catequista(s)
            </span>
        </div>

        <div class="catequistas-grid">
            @forelse($registros as $catequista)
                <div class="catequista-card">
                    <div class="card-header-content">
                        <h4 class="catequista-name">
                            {{ $catequista->name }}
                        </h4>
                        <span class="catequista-subtitle">Catequista activo</span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge email-badge">
                            <i class="bi bi-envelope"></i>
                            {{ $catequista->email }}
                        </span>
                        
                        <span class="info-badge status-badge">
                            <i class="bi bi-check-circle"></i>
                            Activo
                        </span>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-inbox text-muted mb-3"></i>
                    <h6 class="fw-bold text-dark">No hay catequistas registrados</h6>
                    <p class="text-muted mb-0">No se encontraron catequistas registrados en el sistema.</p>
                </div>
            @endforelse
        </div>

        @if($registros->hasPages())
            <div class="card-footer bg-white border-top-0 d-flex justify-content-end p-3">
                {{ $registros->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
