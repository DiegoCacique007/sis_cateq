@extends('layouts.app_parroquia_admin')
@section('title', 'Dashboard - Secretaría')
@section('header_title', 'BIENVENIDA, SECRETARÍA')

@section('content')
    <style>
        .card-parroquia, #contenedor-tablas { border-radius: 18px; }
        #cabecera-crud { gap: 12px; }
        #section-title { color: var(--blue-dark, #1e3a8a); letter-spacing: .4px; }
        .table thead th { background: #f8fbff !important; color: var(--blue-dark, #1e3a8a); font-weight: 700; border-bottom: 1px solid rgba(0,0,0,.06); }
        .table tbody td { vertical-align: middle; }
        .modal-content { border-radius: 20px; overflow: hidden; }
        .modal-header { background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%); }
        .modal .form-control, .modal .form-select { min-height: 46px; border-radius: 12px; border: 1px solid rgba(79, 172, 254, 0.28); }
        .modal .form-control:focus, .modal .form-select:focus { border-color: var(--blue-main, #4facfe); box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.14); }
        .modal .form-label { color: var(--blue-dark, #1e3a8a); font-weight: 700; margin-bottom: 8px; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }

        /* Estilos para la paginación */
        .page-link { color: var(--blue-dark); border: none; border-radius: 8px; margin: 0 2px; font-weight: 600; }
        .page-item.active .page-link { background-color: var(--blue-main); color: white; box-shadow: 0 4px 8px rgba(79, 172, 254, 0.3); }
        .page-link:focus { box-shadow: none; }
    </style>

    <div id="sec-inicio" class="crud-section" style="display: block;">
        <div class="card card-parroquia mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, var(--blue-main) 0%, var(--blue-dark) 100%) !important;">
            <div class="card-body p-4 p-md-5 text-white">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-3 text-white">¡Bienvenida al Sistema Escolar!</h3>
                        <p class="mb-0 fs-6" style="color: rgba(255, 255, 255, 0.85);">
                            Desde este panel central puedes administrar de forma rápida y segura toda la información de la catequesis. Selecciona un módulo en el menú lateral o usa los accesos rápidos a continuación.
                        </p>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                        <i class="bi bi-shield-lock" style="font-size: 6rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3 mt-4" style="color: var(--blue-dark);">Accesos Rápidos</h5>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-parroquia border-0 shadow-sm h-100 p-2" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'" onclick="document.querySelector('a[href$=\'#alumnos\']').click()">
                    <div class="card-body text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-people fs-3"></i>
                        </div>
                        <h6 class="fw-bold" style="color: var(--blue-dark);">Gestión de Alumnos</h6>
                        <p class="text-muted small mb-0">Registra nuevos alumnos o actualiza su estado en el sistema.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-parroquia border-0 shadow-sm h-100 p-2" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'" onclick="document.querySelector('a[href$=\'#inscripciones\']').click()">
                    <div class="card-body text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-card-checklist fs-3"></i>
                        </div>
                        <h6 class="fw-bold" style="color: var(--blue-dark);">Inscripciones</h6>
                        <p class="text-muted small mb-0">Inscribe rápidamente a los alumnos en sus respectivos grupos.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <a href="{{ route('secretaria.usuarios.pendientes') }}" class="text-decoration-none">
                    <div class="card card-parroquia border-0 shadow-sm h-100 p-2" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card-body text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-person-lines-fill fs-3"></i>
                            </div>
                            <h6 class="fw-bold" style="color: var(--blue-dark);">Aprobar Usuarios</h6>
                            <p class="text-muted small mb-0">Revisa y autoriza el acceso a nuevos catequistas.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div id="cabecera-crud" class="d-none mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <h4 class="fw-bold mb-0" id="section-title">Gestión</h4>
        <button class="btn btn-parroquia shadow-sm rounded-pill px-4" type="button" onclick="abrirModalNuevo()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Registro
        </button>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden" id="contenedor-tablas" style="display: none;">

        <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold">Mostrar</span>
                <select id="pageSize" class="form-select form-select-sm text-center border-primary shadow-sm" style="width: 70px; border-radius: 8px;" onchange="cambiarTamanioPagina()">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <span class="text-muted small fw-bold">registros</span>
            </div>
            <div class="w-100" style="max-width: 300px;">
                <div class="input-group input-group-sm shadow-sm" style="border-radius: 12px; overflow: hidden;">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-0 bg-light" placeholder="Buscar en esta tabla..." onkeyup="ejecutarBusqueda()">
                </div>
            </div>
        </div>

        <div class="card-body p-0" style="min-height: 400px; overflow-x: auto;">
            <table class="table table-hover align-middle mb-0" id="tabla-dinamica">
                <thead><tr></tr></thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <span class="text-muted small mb-2 mb-md-0" id="pagination-info">Mostrando 0 a 0 de 0 registros</span>
            <nav aria-label="Navegación de tabla">
                <ul class="pagination pagination-sm mb-0" id="pagination-controls">
                </ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="modalCRUD" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom border-3" style="border-color: var(--blue-main) !important;">
                    <h5 class="modal-title fw-bold" style="color: var(--blue-dark);" id="modalTitle">Formulario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="dynamicForm" onsubmit="guardarRegistro(event)">
                        @csrf
                        <div id="form-inputs"></div>
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-parroquia rounded-pill px-4 ms-2" id="btnGuardar">
                                <span id="btnGuardarTexto">Guardar Registro</span>
                                <span class="spinner-border spinner-border-sm d-none" id="btnGuardarSpinner"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let activeTableGlobal = '';
        let crudModalInstance = null;
        let fkData = {};

        // Variables para Buscador y Paginación
        let currentTableData = []; // Todos los datos de la base de datos
        let filteredData = [];     // Datos después de aplicar el buscador
        let currentPage = 1;
        let pageSize = 10;

        let editandoId = null;

        const dbSchema = {
            alumnos: [
                { field: 'nombre', type: 'text', label: 'Nombre(s)' },
                { field: 'apellido_paterno', type: 'text', label: 'Apellido paterno' },
                { field: 'apellido_materno', type: 'text', label: 'Apellido materno' },
                { field: 'comunidad_id', type: 'fk', label: 'Comunidad', fkSource: 'comunidades' },
                { field: 'estado', type: 'select', label: 'Estado', options: [{val: 1, text: 'Activo'}, {val: 0, text: 'Baja'}] }
            ],
            tutores: [
                { field: 'nombre', type: 'text', label: 'Nombre(s)' },
                { field: 'ap', type: 'text', label: 'Apellido paterno' },
                { field: 'am', type: 'text', label: 'Apellido materno' },
                { field: 'alumno_id', type: 'fk', label: 'Alumno asignado', fkSource: 'alumnos' }
            ],
            inscripciones: [
                { field: 'alumno_id', type: 'fk', label: 'Alumno', fkSource: 'alumnos' },
                { field: 'periodo_id', type: 'fk', label: 'Periodo', fkSource: 'periodos' },
                { field: 'grupo_id', type: 'fk', label: 'Grupo', fkSource: 'grupos' }
            ],
            asigna_grupo: [
                { field: 'comunidad_id', type: 'fk', label: 'Comunidad', fkSource: 'comunidades' },
                { field: 'grupo_id', type: 'fk', label: 'Grupo', fkSource: 'grupos' },
                { field: 'nivel_id', type: 'fk', label: 'Nivel', fkSource: 'niveles' },
                { field: 'periodo_id', type: 'fk', label: 'Periodo', fkSource: 'periodos' },
                { field: 'catequista_id', type: 'fk', label: 'Catequista Responsable', fkSource: 'users' }
            ]
        };

        const nombresBonitos = {
            alumnos: 'Alumnos', tutores: 'Tutores',
            inscripciones: 'Inscripciones', asigna_grupo: 'Asignación de grupos'
        };

        document.addEventListener("DOMContentLoaded", async function() {
            try {
                const res = await fetch('/secretaria/catalogos');
                fkData = await res.json();
            } catch(e) {}

            let hash = window.location.hash.substring(1);
            if (hash && dbSchema[hash]) {
                let link = document.querySelector(`a[href$="#${hash}"]`);
                if (link) switchSection(hash, link);
            }
        });

        function switchSection(tableName, element) {
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active', 'active-menu'));
            if (element) element.classList.add('active');
            document.getElementById('sec-inicio').style.display = 'none';
            document.getElementById('cabecera-crud').classList.remove('d-none');
            document.getElementById('contenedor-tablas').style.display = 'block';
            document.getElementById('section-title').innerText = nombresBonitos[tableName] || 'Gestión';

            // Resetear buscador y paginación al cambiar de tabla
            document.getElementById('searchInput').value = '';
            currentPage = 1;

            activeTableGlobal = tableName;
            cargarTabla(tableName);
        }

        function getFkText(sourceName, id) {
            if(!fkData[sourceName]) return id;
            const item = fkData[sourceName].find(x => x.id == id);
            return item ? item.text : id;
        }

        async function cargarTabla(tabla) {
            const thead = document.querySelector('#tabla-dinamica thead tr');
            const tbody = document.querySelector('#tabla-dinamica tbody');
            const schema = dbSchema[tabla];

            // Renderizar Cabeceras
            let headersHtml = `<th class="ps-4">ID</th>`;
            schema.forEach(col => headersHtml += `<th>${col.label}</th>`);
            headersHtml += `<th class="text-end pe-4">Acciones</th>`;
            thead.innerHTML = headersHtml;

            tbody.innerHTML = `<tr><td colspan="${schema.length + 2}" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando...</td></tr>`;

            try {
                const response = await fetch(`/secretaria/${tabla}`);
                currentTableData = await response.json();

                // Aplicar filtros iniciales y renderizar
                ejecutarBusqueda();

            } catch (error) {
                tbody.innerHTML = `<tr><td colspan="${schema.length + 2}" class="text-center py-4 text-danger">Error al cargar la información.</td></tr>`;
            }
        }

        // ==========================================
        // LÓGICA DE BÚSQUEDA Y PAGINACIÓN
        // ==========================================

        function cambiarTamanioPagina() {
            pageSize = parseInt(document.getElementById('pageSize').value);
            currentPage = 1; // Regresar a la página 1 al cambiar el tamaño
            renderizarVista();
        }

        function ejecutarBusqueda() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const schema = dbSchema[activeTableGlobal];

            // Filtrar los datos originales
            filteredData = currentTableData.filter(row => {
                if(!query) return true; // Si está vacío, mostrar todo

                // Buscar en todas las columnas configuradas en el schema
                for(let col of schema) {
                    let val = row[col.field];

                    // Si es Llave foránea o Select, buscar por el texto visible, no por el ID numérico
                    if(col.type === 'fk' && val) val = getFkText(col.fkSource, val);
                    else if(col.type === 'select') {
                        const opt = col.options.find(o => o.val == val);
                        val = opt ? opt.text : val;
                    }

                    // Si coincide con la búsqueda
                    if(val && String(val).toLowerCase().includes(query)) {
                        return true;
                    }
                }
                // También permitir buscar por ID
                if(String(row.id).includes(query)) return true;

                return false;
            });

            currentPage = 1; // Regresar a la página 1 tras buscar
            renderizarVista();
        }

        function cambiarPagina(page) {
            currentPage = page;
            renderizarVista();
        }

        function renderizarVista() {
            const tbody = document.querySelector('#tabla-dinamica tbody');
            const schema = dbSchema[activeTableGlobal];

            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${schema.length + 2}" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No se encontraron resultados.</td></tr>`;
                document.getElementById('pagination-info').innerText = 'Mostrando 0 a 0 de 0 registros';
                document.getElementById('pagination-controls').innerHTML = '';
                return;
            }

            // Calcular paginación
            const totalPages = Math.ceil(filteredData.length / pageSize);
            if(currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, filteredData.length);
            const dataToRender = filteredData.slice(startIndex, endIndex);

            // Actualizar info de registros (Ej. Mostrando 1 a 10 de 50)
            document.getElementById('pagination-info').innerText = `Mostrando ${startIndex + 1} a ${endIndex} de ${filteredData.length} registros`;

            // Imprimir HTML de la tabla
            let rowsHtml = '';
            dataToRender.forEach(row => {
                rowsHtml += `<tr><td class="ps-4 fw-bold text-secondary">#${row.id}</td>`;
                schema.forEach(col => {
                    let valor = row[col.field];
                    if(col.type === 'fk' && valor) {
                        valor = `<span class="badge bg-light text-dark border px-2 py-1">${getFkText(col.fkSource, valor)}</span>`;
                    } else if(col.type === 'select') {
                        const opt = col.options.find(o => o.val == valor);
                        valor = opt ? opt.text : valor;
                        if(valor === 'Activo') valor = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Activo</span>';
                        if(valor === 'Inactivo' || valor === 'Baja') valor = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">'+valor+'</span>';
                    }
                    rowsHtml += `<td>${valor ?? '<span class="text-muted">-</span>'}</td>`;
                });

                rowsHtml += `<td class="text-end pe-4">
                    <button class="btn btn-outline-primary btn-action rounded-circle shadow-sm me-1" onclick="prepararEdicion(${row.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn btn-outline-danger btn-action rounded-circle shadow-sm" onclick="eliminarRegistro(${row.id})" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                </td></tr>`;
            });
            tbody.innerHTML = rowsHtml;

            // Renderizar Botones de Paginación
            renderizarPaginacion(totalPages);
        }

        function renderizarPaginacion(totalPages) {
            const ul = document.getElementById('pagination-controls');
            let paginationHtml = '';

            // Botón Anterior
            paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPagina(${currentPage - 1})"><i class="bi bi-chevron-left"></i></button>
            </li>`;

            // Lógica para mostrar siempre unas cuantas páginas y evitar que se desborde si hay 100 páginas
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) paginationHtml += `<li class="page-item"><button class="page-link" onclick="cambiarPagina(1)">1</button></li><li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>`;

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
                </li>`;
            }

            if (endPage < totalPages) paginationHtml += `<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li><li class="page-item"><button class="page-link" onclick="cambiarPagina(${totalPages})">${totalPages}</button></li>`;

            // Botón Siguiente
            paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPagina(${currentPage + 1})"><i class="bi bi-chevron-right"></i></button>
            </li>`;

            ul.innerHTML = paginationHtml;
        }

        // ==========================================
        // LÓGICA DE MODALES Y CRUD
        // ==========================================

        function construirFormulario() {
            const formInputs = document.getElementById('form-inputs');
            const schema = dbSchema[activeTableGlobal];
            formInputs.innerHTML = '';

            schema.forEach(col => {
                let html = `<div class="mb-3"><label class="form-label">${col.label}</label>`;
                if (col.type === 'text' || col.type === 'date') html += `<input type="${col.type}" class="form-control bg-light" name="${col.field}" required>`;
                else if (col.type === 'number') {
                    const step = col.step ? `step="${col.step}"` : '';
                    html += `<input type="number" class="form-control bg-light" name="${col.field}" ${step} required>`;
                } else if (col.type === 'select') {
                    html += `<select class="form-select bg-light" name="${col.field}" required><option value="">-- Seleccione --</option>`;
                    col.options.forEach(opt => html += `<option value="${opt.val}">${opt.text}</option>`);
                    html += `</select>`;
                } else if (col.type === 'fk') {
                    html += `<select class="form-select bg-light border-primary" name="${col.field}" required><option value="">-- Seleccione una opción --</option>`;
                    const optionsData = fkData[col.fkSource] || [];
                    optionsData.forEach(opt => html += `<option value="${opt.id}">${opt.text}</option>`);
                    html += `</select>`;
                }
                html += `</div>`;
                formInputs.innerHTML += html;
            });
        }

        function abrirModalNuevo() {
            if (!activeTableGlobal) return;
            editandoId = null;
            construirFormulario();
            document.getElementById('modalTitle').innerText = 'Registrar: ' + nombresBonitos[activeTableGlobal];
            document.getElementById('btnGuardarTexto').innerText = 'Guardar Registro';
            document.getElementById('dynamicForm').reset();
            crudModalInstance = new bootstrap.Modal(document.getElementById('modalCRUD'));
            crudModalInstance.show();
        }

        function prepararEdicion(id) {
            editandoId = id;
            const rowData = currentTableData.find(r => r.id == id);
            if(!rowData) return;
            construirFormulario();
            document.getElementById('modalTitle').innerText = 'Editar: ' + nombresBonitos[activeTableGlobal];
            document.getElementById('btnGuardarTexto').innerText = 'Actualizar Cambios';

            const form = document.getElementById('dynamicForm');
            dbSchema[activeTableGlobal].forEach(col => {
                if(form.elements[col.field]) {
                    let val = rowData[col.field];
                    if(col.type === 'date' && val) val = val.split(' ')[0];
                    form.elements[col.field].value = val;
                }
            });
            crudModalInstance = new bootstrap.Modal(document.getElementById('modalCRUD'));
            crudModalInstance.show();
        }

        async function guardarRegistro(event) {
            event.preventDefault();
            const form = document.getElementById('dynamicForm');
            const formData = new FormData(form);

            const btnTexto = document.getElementById('btnGuardarTexto');
            const btnSpinner = document.getElementById('btnGuardarSpinner');
            const btnGuardar = document.getElementById('btnGuardar');

            btnTexto.textContent = 'Procesando...';
            btnSpinner.classList.remove('d-none');
            btnGuardar.disabled = true;

            let url = `/secretaria/${activeTableGlobal}`;
            if (editandoId) {
                url = `/secretaria/${activeTableGlobal}/${editandoId}`;
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' },
                    body: formData
                });

                if (response.ok) {
                    const resFk = await fetch('/secretaria/catalogos');
                    fkData = await resFk.json();
                    crudModalInstance.hide();
                    cargarTabla(activeTableGlobal); // Recarga todo para actualizar la tabla
                } else {
                    alert('Error: Verifique los datos ingresados.');
                }
            } catch (error) {
                alert('Error crítico de conexión.');
            } finally {
                btnTexto.textContent = 'Guardar Registro';
                btnSpinner.classList.add('d-none');
                btnGuardar.disabled = false;
            }
        }

        async function eliminarRegistro(id) {
            if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) return;
            try {
                const response = await fetch(`/secretaria/${activeTableGlobal}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' }
                });
                if (response.ok) cargarTabla(activeTableGlobal);
                else alert('Error al intentar eliminar.');
            } catch (error) { alert('Error crítico.'); }
        }
    </script>
@endsection
