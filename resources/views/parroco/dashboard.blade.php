@extends('layouts.app_parroquia_parroco')
@section('title', 'Dashboard - Párroco')
@section('header_title', 'CONTROL TOTAL')
@section('header_subtitle', 'Panel de Supervisión General')

@section('content')
    <style>
        .card-parroquia, #contenedor-tablas { border-radius: 18px; }
        #cabecera-crud { gap: 12px; }
        .table thead th { background: #f8fbff !important; color: var(--blue-dark, #1e3a8a); font-weight: 700; border-bottom: 1px solid rgba(0,0,0,.06); }
        .table tbody td { vertical-align: middle; }
        .modal-content { border-radius: 20px; overflow: hidden; border: none; }
        .modal-header { background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%); }
        .modal .form-control, .modal .form-select { min-height: 46px; border-radius: 12px; border: 1px solid rgba(79, 172, 254, 0.28); }
        .modal .form-control:focus, .modal .form-select:focus { border-color: var(--blue-main, #4facfe); box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.14); }
        .modal .form-label { color: var(--blue-dark, #1e3a8a); font-weight: 700; margin-bottom: 8px; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .tarjeta-hover { cursor: pointer; transition: transform 0.2s; }
        .tarjeta-hover:hover { transform: translateY(-5px); }

        /* Estilos para la paginación */
        .page-link { color: var(--blue-dark); border: none; border-radius: 8px; margin: 0 2px; font-weight: 600; }
        .page-item.active .page-link { background-color: var(--blue-main); color: white; box-shadow: 0 4px 8px rgba(79, 172, 254, 0.3); }
        .page-link:focus { box-shadow: none; }
    </style>

    <div id="sec-inicio" style="display: block;">
        <div class="card card-parroquia mb-4 shadow-sm border-0" style="background: linear-gradient(135deg, var(--blue-dark) 0%, #3b82f6 100%) !important;">
            <div class="card-body p-4 p-md-5 text-white">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-3 text-white" style="font-family: 'Cinzel', serif;">¡Bendiciones, Párroco!</h3>
                        <p class="mb-0 fs-6" style="color: rgba(255, 255, 255, 0.85);">
                            Panel de acceso total para auditar secretaría, configuraciones globales y monitorear evaluaciones.
                        </p>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                        <i class="bi bi-bank" style="font-size: 6rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-parroquia border-0 shadow-sm h-100 p-2 tarjeta-hover" onclick="switchSection('alumnos')">
                    <div class="card-body text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-people fs-3"></i></div>
                        <h6 class="fw-bold" style="color: var(--blue-dark);">Alumnado Global</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-parroquia border-0 shadow-sm h-100 p-2 tarjeta-hover" onclick="switchSection('asigna_grupo')">
                    <div class="card-body text-center">
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-diagram-3 fs-3"></i></div>
                        <h6 class="fw-bold" style="color: var(--blue-dark);">Asignaciones Totales</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-parroquia border-0 shadow-sm h-100 p-2 tarjeta-hover" onclick="switchSection('evaluaciones')">
                    <div class="card-body text-center">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-clipboard-data fs-3"></i></div>
                        <h6 class="fw-bold" style="color: var(--blue-dark);">Evaluaciones Totales</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="cabecera-crud" class="d-none mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <h4 class="fw-bold mb-0" id="section-title" style="color: var(--blue-dark);">Gestión</h4>
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
                    <input type="text" id="searchInput" class="form-control border-0 bg-light" placeholder="Buscar..." onkeyup="ejecutarBusqueda()">
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
                        <div class="mt-4 text-end pt-3">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-parroquia rounded-pill px-4 ms-2" id="btnGuardar">
                                <span id="btnGuardarTexto">Guardar Datos</span>
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

        // Variables para la Paginación y Búsqueda
        let currentTableData = [];
        let filteredData = [];
        let currentPage = 1;
        let pageSize = 10;
        let editandoId = null;

        const dbSchema = {
            comunidades: [ { field: 'comunidad', type: 'text', label: 'Comunidad' } ],
            niveles: [ { field: 'nivel', type: 'text', label: 'Nivel' } ],
            periodos: [ { field: 'fecha_inicio', type: 'date', label: 'Inicio' }, { field: 'fecha_fin', type: 'date', label: 'Fin' }, { field: 'estado', type: 'select', label: 'Estado', options: [{val: 1, text: 'Activo'}, {val: 0, text: 'Inactivo'}] } ],
            rubros: [ { field: 'nombre', type: 'text', label: 'Rubro' }, { field: 'valor', type: 'number', step: '0.01', label: '%' } ],
            unidades: [ { field: 'nombre', type: 'text', label: 'Unidad' }, { field: 'numero', type: 'number', label: 'Número' }, { field: 'nivel_id', type: 'fk', label: 'Nivel', fkSource: 'niveles' } ],
            grupos: [ { field: 'nombre', type: 'text', label: 'Grupo' } ],

            alumnos: [
                { field: 'nombre', type: 'text', label: 'Nombre' },
                { field: 'apellido_paterno', type: 'text', label: 'Ap. Paterno' },
                { field: 'apellido_materno', type: 'text', label: 'Ap. Materno' },
                { field: 'comunidad_id', type: 'fk', label: 'Comunidad', fkSource: 'comunidades' },
                { field: 'estado', type: 'select', label: 'Estado', options: [{val: 1, text: 'Activo'}, {val: 0, text: 'Baja'}] }
            ],

            tutores: [
                { field: 'nombre', type: 'text', label: 'Nombre' },
                { field: 'ap', type: 'text', label: 'Ap. Paterno' },
                { field: 'am', type: 'text', label: 'Ap. Materno' },
                { field: 'alumno_id', type: 'fk', label: 'Alumno', fkSource: 'alumnos' }
            ],

            inscripciones: [ { field: 'alumno_id', type: 'fk', label: 'Alumno', fkSource: 'alumnos' }, { field: 'periodo_id', type: 'fk', label: 'Periodo', fkSource: 'periodos' }, { field: 'grupo_id', type: 'fk', label: 'Grupo', fkSource: 'grupos' } ],
            asigna_grupo: [ { field: 'comunidad_id', type: 'fk', label: 'Comunidad', fkSource: 'comunidades' }, { field: 'grupo_id', type: 'fk', label: 'Grupo', fkSource: 'grupos' }, { field: 'nivel_id', type: 'fk', label: 'Nivel', fkSource: 'niveles' }, { field: 'periodo_id', type: 'fk', label: 'Periodo', fkSource: 'periodos' }, { field: 'catequista_id', type: 'fk', label: 'Catequista', fkSource: 'users' } ],

            evaluaciones: [
                { field: 'inscripcion_id', type: 'fk', label: 'Alumno', fkSource: 'inscripciones' },
                { field: 'unidad_id', type: 'fk', label: 'Unidad', fkSource: 'unidades' },
                { field: 'rubro_id', type: 'fk', label: 'Rubro', fkSource: 'rubros' },
                { field: 'calificacion', type: 'number', step: '0.01', label: 'Nota' }
            ]
        };

        const nombresBonitos = { alumnos: 'Alumnos', tutores: 'Tutores', inscripciones: 'Inscripciones', asigna_grupo: 'Asignaciones', niveles: 'Niveles', periodos: 'Periodos', rubros: 'Rubros', unidades: 'Unidades', grupos: 'Grupos', comunidades: 'Comunidades', evaluaciones: 'Evaluaciones Totales' };

        document.addEventListener("DOMContentLoaded", async function() {
            try {
                const res = await fetch('/parroco/catalogos');
                fkData = await res.json();
            } catch(e) {}
            let hash = window.location.hash.substring(1);
            if (hash && dbSchema[hash]) switchSection(hash);
        });

        function switchSection(tableName) {
            if(!tableName) {
                document.getElementById('sec-inicio').style.display = 'block';
                document.getElementById('cabecera-crud').classList.add('d-none');
                document.getElementById('contenedor-tablas').style.display = 'none';
                activeTableGlobal = ''; return;
            }
            document.getElementById('sec-inicio').style.display = 'none';
            document.getElementById('cabecera-crud').classList.remove('d-none');
            document.getElementById('contenedor-tablas').style.display = 'block';
            document.getElementById('section-title').innerText = nombresBonitos[tableName];

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

            let headersHtml = `<th class="ps-4">ID</th>`;
            schema.forEach(col => headersHtml += `<th>${col.label}</th>`);
            headersHtml += `<th class="text-end pe-4">Acciones</th>`;
            thead.innerHTML = headersHtml;

            tbody.innerHTML = `<tr><td colspan="${schema.length + 2}" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando...</td></tr>`;

            try {
                const response = await fetch(`/parroco/${tabla}`);
                currentTableData = await response.json();
                ejecutarBusqueda();
            } catch (error) {
                tbody.innerHTML = `<tr><td colspan="${schema.length + 2}" class="text-center py-4 text-danger">Error de conexión.</td></tr>`;
            }
        }

        // ==========================================
        // FUNCIONES DE BÚSQUEDA Y PAGINACIÓN
        // ==========================================

        function cambiarTamanioPagina() {
            pageSize = parseInt(document.getElementById('pageSize').value);
            currentPage = 1;
            renderizarVista();
        }

        function ejecutarBusqueda() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const schema = dbSchema[activeTableGlobal];

            filteredData = currentTableData.filter(row => {
                if(!query) return true;
                let isMatch = false;

                // Buscamos en TODAS las columnas ya traducidas
                for(let col of schema) {
                    let val = row[col.field];

                    if(col.type === 'fk' && val) {
                        let campoNombre = col.field.replace('_id', '_nombre');
                        val = row[campoNombre] ? row[campoNombre] : getFkText(col.fkSource, val);
                    } else if(col.type === 'select') {
                        val = val == 1 ? 'Activo' : 'Inactivo';
                    }

                    if(val && String(val).toLowerCase().includes(query)) {
                        isMatch = true;
                    }
                }

                if(String(row.id).includes(query)) isMatch = true;
                return isMatch;
            });

            currentPage = 1;
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
                tbody.innerHTML = `<tr><td colspan="${schema.length + 2}" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No se encontraron registros.</td></tr>`;
                document.getElementById('pagination-info').innerText = 'Mostrando 0 a 0 de 0 registros';
                document.getElementById('pagination-controls').innerHTML = '';
                return;
            }

            const totalPages = Math.ceil(filteredData.length / pageSize);
            if(currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, filteredData.length);
            const dataToRender = filteredData.slice(startIndex, endIndex);

            document.getElementById('pagination-info').innerText = `Mostrando ${startIndex + 1} a ${endIndex} de ${filteredData.length} registros`;

            let rowsHtml = '';
            dataToRender.forEach(row => {
                rowsHtml += `<tr><td class="ps-4 fw-bold text-secondary">#${row.id}</td>`;
                schema.forEach(col => {
                    let valor = row[col.field];

                    if(col.type === 'fk' && valor) {
                        let campoNombre = col.field.replace('_id', '_nombre');
                        let textoMostrar = row[campoNombre] ? row[campoNombre] : getFkText(col.fkSource, valor);

                        valor = `<span class="badge bg-light text-dark border px-2 py-1">${textoMostrar}</span>`;
                    } else if(col.type === 'select') {
                        valor = valor == 1 ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Activo</span>' : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Inactivo</span>';
                    }

                    rowsHtml += `<td>${valor ?? '<span class="text-muted">-</span>'}</td>`;
                });
                rowsHtml += `<td class="text-end pe-4">
                    <button class="btn btn-outline-primary btn-action rounded-circle shadow-sm me-1" onclick="prepararEdicion(${row.id})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn btn-outline-danger btn-action rounded-circle shadow-sm" onclick="eliminarRegistro(${row.id})" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                </td></tr>`;
            });
            tbody.innerHTML = rowsHtml;

            renderizarPaginacion(totalPages);
        }

        function renderizarPaginacion(totalPages) {
            const ul = document.getElementById('pagination-controls');
            let paginationHtml = '';

            paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><button class="page-link" onclick="cambiarPagina(${currentPage - 1})"><i class="bi bi-chevron-left"></i></button></li>`;

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) paginationHtml += `<li class="page-item"><button class="page-link" onclick="cambiarPagina(1)">1</button></li><li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>`;

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"><button class="page-link" onclick="cambiarPagina(${i})">${i}</button></li>`;
            }

            if (endPage < totalPages) paginationHtml += `<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li><li class="page-item"><button class="page-link" onclick="cambiarPagina(${totalPages})">${totalPages}</button></li>`;

            paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><button class="page-link" onclick="cambiarPagina(${currentPage + 1})"><i class="bi bi-chevron-right"></i></button></li>`;

            ul.innerHTML = paginationHtml;
        }

        function construirFormulario() {
            const formInputs = document.getElementById('form-inputs');
            formInputs.innerHTML = '';
            dbSchema[activeTableGlobal].forEach(col => {
                let html = `<div class="mb-3"><label class="form-label">${col.label}</label>`;
                if (col.type === 'text' || col.type === 'date') html += `<input type="${col.type}" class="form-control bg-light" name="${col.field}" required>`;
                else if (col.type === 'number') html += `<input type="number" class="form-control bg-light" name="${col.field}" ${col.step ? `step="${col.step}"` : ''} required>`;
                else if (col.type === 'select') {
                    html += `<select class="form-select bg-light" name="${col.field}" required><option value="">-- Seleccione --</option>`;
                    col.options.forEach(opt => html += `<option value="${opt.val}">${opt.text}</option>`);
                    html += `</select>`;
                } else if (col.type === 'fk') {
                    let catalogoOpciones = fkData[col.fkSource] || [];
                    html += `<select class="form-select bg-light border-primary" name="${col.field}" required><option value="">-- Seleccione --</option>`;
                    catalogoOpciones.forEach(opt => html += `<option value="${opt.id}">${opt.text}</option>`);
                    html += `</select>`;
                }
                html += `</div>`;
                formInputs.innerHTML += html;
            });
        }

        function abrirModalNuevo() { editandoId = null; construirFormulario(); document.getElementById('modalTitle').innerText = 'Nuevo Registro'; document.getElementById('btnGuardarTexto').innerText = 'Guardar'; document.getElementById('dynamicForm').reset(); crudModalInstance = new bootstrap.Modal(document.getElementById('modalCRUD')); crudModalInstance.show(); }

        function prepararEdicion(id) {
            editandoId = id; const rowData = currentTableData.find(r => r.id == id);
            if(!rowData) return; construirFormulario(); document.getElementById('modalTitle').innerText = 'Editar'; document.getElementById('btnGuardarTexto').innerText = 'Actualizar';
            const form = document.getElementById('dynamicForm');
            dbSchema[activeTableGlobal].forEach(col => {
                if(form.elements[col.field]) {
                    form.elements[col.field].value = (col.type === 'date' && rowData[col.field]) ? rowData[col.field].split(' ')[0] : rowData[col.field];
                }
            });
            crudModalInstance = new bootstrap.Modal(document.getElementById('modalCRUD')); crudModalInstance.show();
        }

        async function guardarRegistro(event) {
            event.preventDefault(); const form = document.getElementById('dynamicForm'); const formData = new FormData(form);
            document.getElementById('btnGuardar').disabled = true; document.getElementById('btnGuardarTexto').textContent = 'Procesando...';
            let url = `/parroco/${activeTableGlobal}`; if (editandoId) { url += `/${editandoId}`; formData.append('_method', 'PUT'); }
            try {
                const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' }, body: formData });
                if (response.ok) {
                    crudModalInstance.hide();
                    cargarTabla(activeTableGlobal);
                } else alert('Error en los datos.');
            } catch (error) { alert('Error crítico.'); } finally { document.getElementById('btnGuardar').disabled = false; document.getElementById('btnGuardarTexto').textContent = 'Guardar'; }
        }

        async function eliminarRegistro(id) {
            if (!confirm('¿Desea eliminar este registro?')) return;
            try {
                const res = await fetch(`/parroco/${activeTableGlobal}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' } });
                if (res.ok) cargarTabla(activeTableGlobal);
            } catch (error) { alert('Error crítico.'); }
        }
    </script>
@endsection
