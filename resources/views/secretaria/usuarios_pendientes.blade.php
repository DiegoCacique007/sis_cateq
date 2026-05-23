@extends('layouts.app_parroquia_admin')

@section('title', 'Registros Pendientes | Sis_Cateq')
@section('subtitle', 'Aprobación de accesos')
@section('header_title', 'Registros Pendientes')
@section('header_subtitle', 'Usuarios registrados que aún no tienen acceso.')

@section('content')
    <style>
        .pendientes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .pendiente-card {
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
            min-height: 235px;
        }

        .pendiente-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .pendiente-card .card-header-content {
            margin-bottom: 1rem;
        }

        .pendiente-card .user-name {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
            padding-right: 0.5rem;
        }

        .pendiente-card .user-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .pendiente-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
            margin-bottom: 1rem;
        }

        .pendiente-card .info-badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .pendiente-card .email-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
            max-width: 100%;
            word-break: break-word;
        }

        .pendiente-card .requested-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .pendiente-card .role-badge {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .pendiente-card .date-badge {
            background-color: #fff7ed;
            color: #d97706;
            border: 1px solid #f59e0b;
        }

        .pendiente-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: auto;
            padding-top: 0.85rem;
            border-top: 1px solid #e2e8f0;
        }

        .pendiente-actions form {
            margin: 0;
            flex: 1;
        }

        .btn-approve-card,
        .btn-block-card {
            width: 100%;
            border-radius: 9999px;
            padding: 0.48rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
        }

        .btn-approve-card {
            background: #16a34a;
            color: #ffffff;
            border: 1px solid #16a34a;
        }

        .btn-approve-card:hover {
            background: #15803d;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.22);
        }

        .btn-block-card {
            background: #ffffff;
            color: #dc2626;
            border: 1px solid #dc2626;
        }

        .btn-block-card:hover {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(220, 38, 38, 0.18);
        }

        .top-actions-card {
            background: #ffffff;
            border: 0;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1.1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .module-search-box {
            max-width: 360px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            border: 1px dashed #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            margin: 1.5rem;
        }

        .empty-state.full-grid {
            grid-column: 1 / -1;
            margin: 0;
        }

        .empty-state i {
            font-size: 2.4rem;
            color: #94a3b8;
            display: block;
            margin-bottom: 0.75rem;
        }

        .tip-box {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .pendientes-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .module-search-box {
                max-width: 100%;
                width: 100%;
            }

            .pendiente-actions {
                flex-direction: column;
            }
        }
    </style>

    <div class="top-actions-card d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-primary rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.9rem;">
                <i class="bi bi-person-lines-fill me-1"></i>
                {{ $pendientes->count() }} Solicitudes Pendientes
            </span>
        </div>

        <div>
            <a class="btn btn-outline-parroquia btn-sm px-4 rounded-pill fw-bold" href="{{ route('secretaria.dashboard') }}">
                <i class="bi bi-arrow-left me-1"></i>
                Volver al Inicio
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success fw-bold shadow-sm rounded-3 py-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('status') }}
        </div>
    @endif

    <div class="card card-parroquia border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="mb-0 fw-bold" style="color: var(--blue-dark, #1e3a8a);">
                    Lista de solicitudes
                </h5>
                <small class="text-muted">Revisa, aprueba o bloquea usuarios registrados.</small>
            </div>

            <div class="input-group input-group-sm shadow-sm module-search-box">
                <span class="input-group-text bg-white text-muted border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input
                    id="searchInput"
                    type="text"
                    class="form-control border-start-0 ps-0"
                    placeholder="Nombre o correo..."
                >
            </div>
        </div>

        <div class="card-body p-0">
            @if($pendientes->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-info-circle"></i>
                    No hay usuarios pendientes por aprobar en este momento.
                </div>
            @else
                <div class="pendientes-grid" id="pendientesGrid">
                    @foreach($pendientes as $u)
                        <div
                            class="pendiente-card"
                            data-search="{{ strtolower($u->name . ' ' . $u->email . ' ' . ($u->requested_role ?? 'catequista') . ' ' . $u->role) }}"
                        >
                            <div class="card-header-content">
                                <h4 class="user-name">
                                    {{ $u->name }}
                                </h4>

                                <span class="user-subtitle">
                                    Usuario pendiente de aprobación
                                </span>
                            </div>

                            <div class="card-body-content">
                                <span class="info-badge email-badge">
                                    <i class="bi bi-envelope"></i>
                                    {{ $u->email }}
                                </span>

                                <span class="info-badge requested-badge">
                                    <i class="bi bi-person-plus"></i>
                                    Solicita: {{ strtoupper($u->requested_role ?? 'catequista') }}
                                </span>

                                <span class="info-badge role-badge">
                                    <i class="bi bi-person-badge"></i>
                                    Rol actual: {{ strtoupper($u->role) }}
                                </span>

                                <span class="info-badge date-badge">
                                    <i class="bi bi-calendar-event"></i>
                                    {{ $u->created_at?->format('Y-m-d H:i') ?? 'Sin fecha' }}
                                </span>
                            </div>

                            <div class="pendiente-actions">
                                <form method="POST" action="{{ route('secretaria.usuarios.aprobar', $u->id) }}">
                                    @csrf
                                    <input type="hidden" name="role" value="{{ $u->requested_role ?? 'catequista' }}">

                                    <button
                                        type="submit"
                                        class="btn-approve-card"
                                        onclick="return confirm('¿Aprobar a {{ $u->name }} con el rol solicitado?');"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                        Aprobar
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('secretaria.usuarios.bloquear', $u->id) }}">
                                    @csrf

                                    <button
                                        type="submit"
                                        class="btn-block-card"
                                        onclick="return confirm('¿Bloquear a {{ $u->name }}? Esta acción impedirá su acceso.');"
                                    >
                                        <i class="bi bi-slash-circle"></i>
                                        Bloquear
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    <div class="empty-state full-grid d-none" id="noResultsMessage">
                        <i class="bi bi-search"></i>
                        No se encontraron solicitudes con ese criterio.
                    </div>
                </div>

                <div class="tip-box">
                    <i class="bi bi-lightbulb text-warning me-1"></i>
                    <strong>Tip:</strong> revisa la solicitud antes de aprobar. Si no reconoces el correo o nombre, puedes bloquear la cuenta.
                </div>
            @endif
        </div>
    </div>

    <script>
        const input = document.getElementById('searchInput');
        const grid = document.getElementById('pendientesGrid');
        const noResultsMessage = document.getElementById('noResultsMessage');

        if (input && grid) {
            input.addEventListener('keyup', function () {
                const q = this.value.toLowerCase().trim();
                const cards = grid.querySelectorAll('.pendiente-card');
                let visibleCount = 0;

                cards.forEach(card => {
                    const text = card.dataset.search || card.innerText.toLowerCase();
                    const match = text.includes(q);

                    card.style.display = match ? '' : 'none';

                    if (match) {
                        visibleCount++;
                    }
                });

                if (noResultsMessage) {
                    noResultsMessage.classList.toggle('d-none', visibleCount !== 0);
                }
            });
        }
    </script>
@endsection
