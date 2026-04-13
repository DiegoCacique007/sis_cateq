@extends('layouts.app_parroquia_coordinador_comunidades')
@section('title', 'Dashboard - Coord. Comunidad')
@section('header_title', 'BIENVENIDO, COORDINADOR')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        .card-parroquia { border-radius: 18px; }
        .table thead th { background: #f8fbff !important; color: var(--blue-dark, #1e3a8a); font-weight: 700; border-bottom: 1px solid rgba(0,0,0,.06); }
        .table tbody td { vertical-align: middle; }

        /* Efecto hover para las tarjetas del menú principal */
        .tarjeta-menu { cursor: pointer; transition: all 0.3s ease; border: 2px solid transparent; }
        .tarjeta-menu:hover { transform: translateY(-5px); border-color: var(--blue-main, #4facfe); box-shadow: 0 10px 20px rgba(79, 172, 254, 0.15) !important; }

        /* Estilos del mapa */
        #mapa-interactivo { height: 480px; border-radius: 16px; border: 2px solid var(--blue-main); box-shadow: 0 10px 30px rgba(79, 172, 254, 0.2); z-index: 1; }

        /* Busqueda de ubicacion */
        .buscador-mapa .input-group-text,
        .buscador-mapa .form-control,
        .buscador-mapa .btn {
            border-radius: 12px;
        }

        .buscador-mapa .input-group {
            gap: 8px;
        }

        .buscador-mapa .input-group > * {
            border-radius: 12px !important;
        }

        /* Estilos para la paginación */
        .page-link { color: var(--blue-dark); border: none; border-radius: 8px; margin: 0 2px; font-weight: 600; padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .page-item.active .page-link { background-color: var(--blue-main); color: white; box-shadow: 0 4px 8px rgba(79, 172, 254, 0.3); }
        .page-link:focus { box-shadow: none; }
    </style>

    <div id="sec-inicio" style="display: block;">
        <div class="card card-parroquia mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, var(--blue-main) 0%, var(--blue-dark) 100%) !important;">
            <div class="card-body p-4 p-md-5 text-white">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-3 text-white">¡Bienvenido al panel de Coordinación!</h3>
                        <p class="mb-0 fs-6" style="color: rgba(255, 255, 255, 0.85);">
                            Desde aquí podrás administrar de forma rápida los territorios y comunidades asignadas. Selecciona el módulo al que deseas ingresar.
                        </p>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                        <i class="bi bi-geo-alt-fill" style="font-size: 6rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3 mt-4" style="color: var(--blue-dark);">Módulos Disponibles</h5>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-parroquia border-0 shadow-sm h-100 p-2 tarjeta-menu" onclick="window.location.hash = 'comunidades'">
                    <div class="card-body text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-pin-map-fill fs-3"></i>
                        </div>
                        <h6 class="fw-bold" style="color: var(--blue-dark);">Gestión de Comunidades</h6>
                        <p class="text-muted small mb-0">Ingresa aquí para registrar, editar o dar de baja las comunidades.</p>
                        <span class="badge bg-primary mt-3 rounded-pill px-3 py-2">Ingresar al Mapa <i class="bi bi-arrow-right ms-1"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="sec-crud-comunidades" style="display: none;">
        <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
            <button class="btn btn-light rounded-circle shadow-sm" onclick="window.location.hash = ''" title="Regresar al inicio" style="width: 40px; height: 40px;">
                <i class="bi bi-arrow-left fs-5"></i>
            </button>
            <h4 class="fw-bold mb-0" style="color: var(--blue-dark);">Mapa de Comunidades</h4>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card card-parroquia border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Explorador Geográfico</h5>
                        <p class="text-muted small mb-3">Navega por el mapa. <strong>Haz clic en cualquier colonia o barrio</strong> para extraer su nombre y registrar la comunidad automáticamente.</p>

                        <div class="buscador-mapa mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white text-primary border">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="searchLugarMapa" class="form-control shadow-sm" placeholder="Buscar lugar, colonia, barrio, calle..." onkeydown="if(event.key === 'Enter'){ event.preventDefault(); buscarLugarEnMapa(); }">
                                <button type="button" class="btn btn-primary shadow-sm px-4" id="btnBuscarLugar" onclick="buscarLugarEnMapa()">
                                    Buscar
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle me-1"></i>Escribe un lugar y el mapa se moverá automáticamente a esa ubicación.
                            </small>
                        </div>

                        <div id="mapa-interactivo"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-flex flex-column gap-4">
                <div class="card card-parroquia border-0 shadow-sm" style="background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-primary" id="form-titulo">Registrar Comunidad</h5>
                        <form id="formComunidad" onsubmit="guardarComunidad(event)">
                            @csrf
                            <input type="hidden" id="comunidad_id">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Nombre de la Comunidad / Barrio</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-primary border-end-0"><i class="bi bi-pin-map-fill"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0" id="inputComunidad" name="comunidad" placeholder="Ej. Centro, San Juan..." required>
                                </div>
                                <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Puedes escribirlo o hacer clic en el mapa.</small>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-light rounded-pill px-3 me-2 d-none" id="btnCancelar" onclick="resetForm()">Cancelar</button>
                                <button type="submit" class="btn btn-parroquia rounded-pill px-4 shadow-sm" id="btnGuardar">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card card-parroquia border-0 shadow-sm flex-grow-1 d-flex flex-column">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column gap-2">
                        <div class="input-group input-group-sm shadow-sm" style="border-radius: 8px; overflow: hidden;">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-0 bg-light" placeholder="Buscar comunidad..." onkeyup="ejecutarBusqueda()">
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-2 mt-1">
                            <span class="text-muted small" style="font-size: 0.75rem;">Mostrar</span>
                            <select id="pageSize" class="form-select form-select-sm text-center border-primary shadow-sm" style="width: 60px; height: 26px; padding: 0 10px; font-size: 0.75rem; border-radius: 6px;" onchange="cambiarTamanioPagina()">
                                <option value="5" selected>5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                    </div>

                    <div class="card-body p-0 flex-grow-1" style="overflow-y: auto; min-height: 200px;">
                        <table class="table table-hover align-middle mb-0" id="tabla-comunidades">
                            <thead style="position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="ps-4">Nombre Registrado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white border-top p-2 d-flex flex-column align-items-center gap-2">
                        <span class="text-muted" style="font-size: 0.7rem;" id="pagination-info">Mostrando 0 a 0 de 0</span>
                        <nav aria-label="Navegación de tabla">
                            <ul class="pagination pagination-sm mb-0" id="pagination-controls"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map;
        let marcadorTemp;

        // Variables de Paginación y Búsqueda
        let currentTableData = [];
        let filteredData = [];
        let currentPage = 1;
        let pageSize = 5; // Iniciamos en 5 porque el espacio derecho es más pequeño

        // 1. LÓGICA DE NAVEGACIÓN SPA
        window.addEventListener('hashchange', gestionarNavegacion);
        document.addEventListener("DOMContentLoaded", () => {
            inicializarMapa();
            gestionarNavegacion();
        });

        function gestionarNavegacion() {
            let hash = window.location.hash.substring(1);

            if (hash === 'comunidades') {
                document.getElementById('sec-inicio').style.display = 'none';
                document.getElementById('sec-crud-comunidades').style.display = 'block';

                if (map) { setTimeout(() => map.invalidateSize(), 300); }

                // Resetear buscador
                document.getElementById('searchInput').value = '';
                currentPage = 1;

                cargarComunidades();
            } else {
                document.getElementById('sec-inicio').style.display = 'block';
                document.getElementById('sec-crud-comunidades').style.display = 'none';
            }
        }

        // 2. LÓGICA DEL MAPA
        function inicializarMapa() {
            // Coordenadas de Valle de Bravo
            map = L.map('mapa-interactivo').setView([19.1903, -100.1332], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            map.on('click', async function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                if (marcadorTemp) map.removeLayer(marcadorTemp);
                marcadorTemp = L.marker([lat, lng]).addTo(map);
                marcadorTemp.bindPopup("Cargando ubicación...").openPopup();

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                    const data = await response.json();

                    if (data && data.address) {
                        const lugar = data.address.neighbourhood || data.address.suburb || data.address.village || data.address.town || data.address.city || 'Desconocido';

                        document.getElementById('inputComunidad').value = lugar;
                        document.getElementById('inputComunidad').style.backgroundColor = '#e6f4ea';
                        setTimeout(() => document.getElementById('inputComunidad').style.backgroundColor = '', 1000);

                        marcadorTemp.bindPopup(`<b>¡Capturado!</b><br>${lugar}`).openPopup();
                    }
                } catch (err) {
                    marcadorTemp.bindPopup("No se pudo obtener el nombre").openPopup();
                }
            });
        }

        async function buscarLugarEnMapa() {
            const input = document.getElementById('searchLugarMapa');
            const btn = document.getElementById('btnBuscarLugar');
            const termino = input.value.trim();

            if (!termino) {
                alert('Escribe un lugar para buscar.');
                input.focus();
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Buscando...';

            try {
                const params = new URLSearchParams({
                    q: termino,
                    format: 'json',
                    limit: 1,
                    addressdetails: 1
                });

                const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`);
                const data = await response.json();

                if (!Array.isArray(data) || data.length === 0) {
                    alert('No se encontró ninguna ubicación con ese nombre.');
                    return;
                }

                const lugarEncontrado = data[0];
                const lat = parseFloat(lugarEncontrado.lat);
                const lon = parseFloat(lugarEncontrado.lon);

                if (marcadorTemp) map.removeLayer(marcadorTemp);

                map.flyTo([lat, lon], 16, { duration: 1.5 });

                marcadorTemp = L.marker([lat, lon]).addTo(map);

                const nombreCorto =
                    (lugarEncontrado.address && (
                        lugarEncontrado.address.neighbourhood ||
                        lugarEncontrado.address.suburb ||
                        lugarEncontrado.address.village ||
                        lugarEncontrado.address.town ||
                        lugarEncontrado.address.city
                    )) || termino;

                document.getElementById('inputComunidad').value = nombreCorto;
                document.getElementById('inputComunidad').style.backgroundColor = '#e6f4ea';
                setTimeout(() => document.getElementById('inputComunidad').style.backgroundColor = '', 1000);

                marcadorTemp.bindPopup(`<b>Ubicación encontrada</b><br>${lugarEncontrado.display_name}`).openPopup();
            } catch (error) {
                alert('No se pudo realizar la búsqueda del lugar.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Buscar';
            }
        }

        // 3. LÓGICA DE DATOS (FETCH)
        async function cargarComunidades() {
            const tbody = document.querySelector('#tabla-comunidades tbody');
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando...</td></tr>';

            try {
                const res = await fetch('/coordinador-comunidad/comunidades');
                currentTableData = await res.json();

                // Ejecutamos búsqueda vacía para renderizar con paginación
                ejecutarBusqueda();

            } catch(e) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-danger">Error al cargar comunidades.</td></tr>';
            }
        }

        // 4. LÓGICA DE BÚSQUEDA Y PAGINACIÓN
        function cambiarTamanioPagina() {
            pageSize = parseInt(document.getElementById('pageSize').value);
            currentPage = 1;
            renderizarTabla();
        }

        function ejecutarBusqueda() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();

            filteredData = currentTableData.filter(c => {
                if(!query) return true;

                if(String(c.comunidad).toLowerCase().includes(query)) return true;
                if(String(c.id).includes(query)) return true;

                return false;
            });

            currentPage = 1;
            renderizarTabla();
        }

        function cambiarPagina(page) {
            currentPage = page;
            renderizarTabla();
        }

        function renderizarTabla() {
            const tbody = document.querySelector('#tabla-comunidades tbody');

            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="2" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-1"></i>Vacio.</td></tr>`;
                document.getElementById('pagination-info').innerText = '0 registros';
                document.getElementById('pagination-controls').innerHTML = '';
                return;
            }

            const totalPages = Math.ceil(filteredData.length / pageSize);
            if(currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, filteredData.length);
            const dataToRender = filteredData.slice(startIndex, endIndex);

            document.getElementById('pagination-info').innerText = `${startIndex + 1} - ${endIndex} de ${filteredData.length}`;

            let html = '';
            dataToRender.forEach(c => {
                html += `<tr>
                    <td><span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-geo-alt text-primary me-1"></i> ${c.comunidad}</span></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm me-1" onclick="editarComunidad(${c.id}, '${c.comunidad}')" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm" onclick="borrarComunidad(${c.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;

            renderizarPaginacion(totalPages);
        }

        function renderizarPaginacion(totalPages) {
            const ul = document.getElementById('pagination-controls');
            let paginationHtml = '';

            paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPagina(${currentPage - 1})"><i class="bi bi-chevron-left"></i></button>
            </li>`;

            let startPage = Math.max(1, currentPage - 1);
            let endPage = Math.min(totalPages, currentPage + 1);

            if (startPage > 1) paginationHtml += `<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>`;

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
                </li>`;
            }

            if (endPage < totalPages) paginationHtml += `<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>`;

            paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPagina(${currentPage + 1})"><i class="bi bi-chevron-right"></i></button>
            </li>`;

            ul.innerHTML = paginationHtml;
        }

        // 5. LÓGICA DE FORMULARIO (CRUD POST/PUT/DELETE)
        async function guardarComunidad(e) {
            e.preventDefault();
            const id = document.getElementById('comunidad_id').value;
            const nombre = document.getElementById('inputComunidad').value;
            const btnGuardar = document.getElementById('btnGuardar');

            btnGuardar.disabled = true;
            btnGuardar.innerText = 'Procesando...';

            let url = '/coordinador-comunidad/comunidades';
            let method = 'POST';

            if(id) {
                url = `/coordinador-comunidad/comunidades/${id}`;
                method = 'PUT';
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        comunidad: nombre,
                        _method: method === 'PUT' ? 'PUT' : 'POST'
                    })
                });

                if(response.ok) {
                    resetForm();
                    cargarComunidades();
                    if(marcadorTemp) map.removeLayer(marcadorTemp);
                } else {
                    alert('Error al guardar. Verifica la conexión.');
                }
            } catch(error) {
                alert('Error de conexión con el servidor.');
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.innerText = id ? 'Actualizar' : 'Guardar';
            }
        }

        function editarComunidad(id, nombre) {
            document.getElementById('comunidad_id').value = id;
            document.getElementById('inputComunidad').value = nombre;
            document.getElementById('form-titulo').innerText = 'Editar Comunidad';
            document.getElementById('btnGuardar').innerText = 'Actualizar';
            document.getElementById('btnCancelar').classList.remove('d-none');
            document.getElementById('inputComunidad').focus();
        }

        function resetForm() {
            document.getElementById('formComunidad').reset();
            document.getElementById('comunidad_id').value = '';
            document.getElementById('form-titulo').innerText = 'Registrar Comunidad';
            document.getElementById('btnGuardar').innerText = 'Guardar';
            document.getElementById('btnCancelar').classList.add('d-none');
        }

        async function borrarComunidad(id) {
            if(!confirm('¿Eliminar esta comunidad?')) return;
            try {
                const res = await fetch(`/coordinador-comunidad/comunidades/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                });
                if(res.ok) cargarComunidades();
            } catch(e) { alert('Error al eliminar'); }
        }
    </script>
@endsection
