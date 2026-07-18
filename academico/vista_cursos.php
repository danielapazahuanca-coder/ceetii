<?php
require_once __DIR__ . '/config_api.php';
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php");
    exit();
}

$sucursal_sesion = resolver_sucursal($_SESSION['role_id']);

$gestiones_api  = json_decode(@file_get_contents(API_BASE_URL . "/gestion"), true)['data'] ?? [];
$id_gestion_actual = null;
$nombre_gestion_actual = '';
foreach ($gestiones_api as $g) {
    if ((int)$g['estado_bt'] === 1 && strtoupper(trim($g['sucursal_varchar'])) === $sucursal_sesion) {
        $id_gestion_actual     = (int)$g['id_gestion'];
        $nombre_gestion_actual = $g['gestion_varchar'];
        break;
    }
}
$carreras_todas = json_decode(@file_get_contents(API_BASE_URL . "/carreras"), true)['data'] ?? [];
$cursos_todos   = json_decode(@file_get_contents(API_BASE_URL . "/cursos"),   true)['data'] ?? [];
$carreras = array_values(array_filter($carreras_todas, fn($ca) =>
    strtoupper(trim($ca['sucursal'] ?? $ca['sucursal_varchar'] ?? '')) === $sucursal_sesion
));
$cursos = array_values(array_filter($cursos_todos, function($cu) use ($sucursal_sesion, $id_gestion_actual) {
    $cumpleSucursal = strtoupper(trim($cu['sucursal_varchar'] ?? $cu['sucursal'] ?? '')) === $sucursal_sesion;
    if ($id_gestion_actual !== null) {
        $gestionCurso = $cu['id_gestion'] ?? null;
        return $cumpleSucursal && (int)$gestionCurso === $id_gestion_actual;
    }
    return $cumpleSucursal;
}));
$niveles = [1 => 'Primer Año', 2 => 'Segundo Año', 3 => 'Tercer Año'];
require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Vista Académica Integral', $current_page, $_SESSION['role_id']);
?>
<style>
    .nav-tabs-carreras {
        border-bottom: 2px solid var(--rojo-elegante, #8d191d);
    }
    .nav-tabs-carreras .nav-link {
        color: var(--texto-oscuro, #2d3436);
        font-weight: 600;
        border: 1px solid #e3e6f0;
        border-bottom: none;
        background-color: #fff;
        margin-right: 4px;
        transition: all 0.2s ease;
    }
    .nav-tabs-carreras .nav-link:hover {
        background-color: #f8f9fc;
        border-color: #e3e6f0;
    }
    .nav-tabs-carreras .nav-link.active {
        background-color: var(--rojo-elegante, #8d191d) !important;
        color: #fff !important;
        border-color: var(--rojo-elegante, #8d191d) !important;
    }
    .btn-nivel-active {
        background-color: var(--rojo-elegante, #8d191d) !important;
        color: #fff !important;
        border-color: var(--rojo-elegante, #8d191d) !important;
    }
    .btn-paralelo-active {
        background-color: #ffc107 !important;
        color: #212529 !important;
        border-color: #ffc107 !important;
        font-weight: bold;
    }
    .fila-materia-clic {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .fila-materia-clic:hover {
        background-color: #f1f3f5 !important;
    }
    .fila-materia-clic.seleccionada {
        background-color: #e8f4f8 !important;
        border-left: 4px solid #0dcaf0;
    }
    .loader-container {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
    .tabla-grande {
        font-size: 15px !important;
    }
    .tabla-grande thead th {
        font-size: 13px !important;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    #panel-buscador {
        position: relative;
    }
    #buscador-spinner {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
    #resultados-busqueda {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 1050;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        max-height: 360px;
        overflow-y: auto;
        display: none;
    }
    #resultados-busqueda .item-resultado {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
        transition: background 0.12s;
    }
    #resultados-busqueda .item-resultado:last-child {
        border-bottom: none;
    }
    #resultados-busqueda .item-resultado:hover {
        background-color: #f8f9fc;
    }
    #resultados-busqueda .item-resultado .est-nombre {
        font-weight: 700;
        color: #1e272e;
        font-size: 15px;
    }
    #resultados-busqueda .item-resultado .est-meta {
        font-size: 12px;
        color: #6c757d;
    }
    #resultados-busqueda .item-resultado .est-badge {
        margin-left: auto;
        white-space: nowrap;
    }
    #resultados-busqueda .sin-resultados {
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-size: 14px;
    }
    #toolbar-reportes-curso .btn {
        font-weight: 600;
        font-size: 12px;
        padding: 0.4rem 0.75rem;
    }
</style>
<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">visibility</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Vista Académica Integral</h4>
                <p class="text-muted small mb-0">
                    Entorno de Monitoreo: <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded fw-bold text-uppercase"><?= $sucursal_sesion === 'LP' ? 'La Paz' : 'El Alto'; ?></span>
                    <?php if ($nombre_gestion_actual): ?>
                        &nbsp;·&nbsp; Gestión Centralizada: <span class="text-success fw-bold"><?= htmlspecialchars($nombre_gestion_actual); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div style="min-width: 300px; max-width: 420px; width: 100%;">
            <div id="panel-buscador" class="position-relative">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <span class="material-icons text-muted" style="font-size:20px;">search</span>
                    </span>
                    <input type="text"
                           id="input-buscar-est"
                           class="form-control border-start-0 ps-0"
                           placeholder="Buscar estudiante por nombre o C.I.…"
                           autocomplete="off"
                           style="font-size:14px;">
                    <span class="spinner-border spinner-border-sm text-secondary" id="buscador-spinner" role="status"></span>
                </div>
                <div id="resultados-busqueda"></div>
            </div>
            <div class="text-muted mt-1" style="font-size:11px; padding-left:2px;">
                <span class="material-icons" style="font-size:12px; vertical-align:middle;">info</span>
                Busca en la sucursal <strong><?= $sucursal_sesion === 'LP' ? 'La Paz' : 'El Alto'; ?></strong>
                <?php if ($nombre_gestion_actual): ?> · gestión <strong><?= htmlspecialchars($nombre_gestion_actual); ?></strong><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if (empty($carreras)): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center" role="alert">
        <span class="material-icons me-2">warning</span>
        <div>No se registran carreras activas en la sucursal seleccionada.</div>
    </div>
<?php else: ?>
    <div class="mb-4">
        <ul class="nav nav-tabs nav-tabs-carreras" id="tabs-carrera" role="tablist">
            <?php foreach ($carreras as $i => $ca): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center gap-2 py-2.5 px-4 <?= $i === 0 ? 'active' : ''; ?>" href="#" data-id-carrera="<?= $ca['id_carrera']; ?>">
                        <span class="material-icons fs-5">school</span>
                        <?= htmlspecialchars($ca['nombre']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="mb-4 p-3 bg-white rounded-3 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" id="grupo-paralelos" style="display:none;">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <span class="text-muted fw-bold small text-uppercase">Aula / Paralelo:</span>
            <div class="btn-group flex-wrap gap-2" id="botones-paralelos" role="group"></div>
        </div>
        <div id="toolbar-reportes-curso" class="d-flex flex-wrap align-items-center gap-1.5" style="display:none;">
            <a href="#" id="btn-centralizador-pdf" target="_blank" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" title="Centralizador PDF">
                <span class="material-icons fs-6">picture_as_pdf</span> Centralizador PDF
            </a>
            <a href="#" id="btn-centralizador-excel" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" title="Centralizador Excel">
                <span class="material-icons fs-6">grid_on</span> Excel
            </a>
            <a href="#" id="btn-boletines-pdf" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Boletines PDF">
                <span class="material-icons fs-6">badge</span> Boletines
            </a>
        </div>
    </div>
    <div id="contenedor-modulos" style="display:none;">
        <ul class="nav nav-pills p-2 bg-light border rounded-3 mb-4 gap-2" id="tabs-internos" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active d-flex align-items-center gap-2 px-4 py-2 fw-semibold" data-bs-toggle="pill" data-bs-target="#subpanel-alumnos" type="button">
                    <span class="material-icons fs-5">groups</span> Lista de Estudiantes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-2 px-4 py-2 fw-semibold" data-bs-toggle="pill" data-bs-target="#subpanel-materias" type="button">
                    <span class="material-icons fs-5">menu_book</span> Materias y Calificaciones
                </button>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="subpanel-alumnos" role="tabpanel">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2 fw-bold text-dark fs-5">
                            <span class="material-icons text-secondary">people_outline</span>
                            <span>Estudiantes — <span class="label-curso-global font-monospace text-primary fw-bold"></span></span>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-1.5 fw-bold" id="badge-total-alumnos">0 alumnos</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0 tabla-grande">
                            <thead class="table-light text-muted small uppercase">
                                <tr>
                                    <th class="text-center" style="width:50px;">#</th>
                                    <th>Apellidos y Nombres</th>
                                    <th style="width:180px;">Documento C.I.</th>
                                    <th style="width:160px;">Contacto / Teléfono</th>
                                    <th class="text-center" style="width:150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-alumnos">
                                <tr><td colspan="5" class="loader-container">Seleccione un curso.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="subpanel-materias" role="tabpanel">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 fw-bold text-dark fs-5">
                            <span class="material-icons text-secondary">collections_bookmark</span>
                            <span>Malla Curricular del Curso — <span class="label-curso-global font-monospace text-primary fw-bold"></span></span>
                        </div>
                        <small class="text-muted d-block mt-1">Haga clic sobre el registro de una materia para auditar su centralizador de calificaciones.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0 tabla-grande">
                            <thead class="table-light text-muted small uppercase">
                                <tr>
                                    <th class="ps-4" style="width:120px;">Sigla</th>
                                    <th>Asignatura Académica</th>
                                    <th style="width:250px;">Docente Asignado</th>
                                    <th class="text-center" style="width:150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-materias">
                                <tr><td colspan="4" class="loader-container">Seleccione un curso para ver sus materias.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="panel-notes" class="mt-4" style="display:none;">
        <div class="card border-0 shadow-sm bg-white border-start border-4 border-info">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <div class="d-flex align-items-center gap-2 fw-bold text-dark fs-5">
                    <span class="material-icons text-info">analytics</span>
                    <span>Registro Centralizador de Notas: <span id="label-materia-notas" class="text-info fw-bold"></span></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="btn-acta-pdf" target="_blank" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                        <span class="material-icons fs-6">picture_as_pdf</span> Acta PDF
                    </a>
                    <a href="#" id="btn-acta-excel" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                        <span class="material-icons fs-6">grid_on</span> Excel
                    </a>
                    <button type="button" class="btn btn-sm btn-light border d-flex align-items-center gap-1 text-secondary" id="btn-cerrar-notas">
                        <span class="material-icons fs-6">close</span> Cerrar
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-bordered mb-0 tabla-grande" style="font-size: 14px !important;">
                    <thead class="table-light text-muted text-center uppercase" style="font-size: 12px !important;">
                        <tr>
                            <th style="width:50px;">#</th>
                            <th class="text-start">Apellidos y Nombres</th>
                            <th style="width:150px;">Cédula Identidad</th>
                            <th style="width:85px;">1° Bim</th>
                            <th style="width:85px;">2° Bim</th>
                            <th style="width:85px;">3° Bim</th>
                            <th style="width:85px;">4° Bim</th>
                            <th style="width:110px;">Promedio Final</th>
                            <th style="width:140px;">Historial</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-notes" class="text-center">
                        <tr><td colspan="9" class="loader-container">Seleccione una materia del listado superior.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<script>
    var CURSOS = <?php echo json_encode($cursos); ?>;
    var GESTION_ACTUAL = <?php echo $id_gestion_actual !== null ? $id_gestion_actual : 'null'; ?>;
    var SUCURSAL_ACTUAL = '<?php echo $sucursal_sesion; ?>';
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var idCarreraActual = null;
var idCursoActual   = null;
var idMateriaActual = null;
$('#tabs-carrera').on('click', 'a', function(e) {
    e.preventDefault();
    $('#tabs-carrera a').removeClass('active');
    $(this).addClass('active');
    idCarreraActual = $(this).data('id-carrera');
    idCursoActual   = null;
    $('#contenedor-modulos').hide();
    $('#toolbar-reportes-curso').hide();
    $('#panel-notes').hide();
    var paralelos = CURSOS.filter(c =>
        parseInt(c.id_carrera) === parseInt(idCarreraActual) &&
        parseInt(c.estado)     === 1 &&
        (c.sucursal_varchar || '').toUpperCase() === SUCURSAL_ACTUAL &&
        (GESTION_ACTUAL === null || parseInt(c.id_gestion) === GESTION_ACTUAL)
    ).sort((a, b) => {
        if (parseInt(a.id_nivel) !== parseInt(b.id_nivel)) return parseInt(a.id_nivel) - parseInt(b.id_nivel);
        return (a.paralelo || '').localeCompare(b.paralelo || '');
    });
    var $cont = $('#botones-paralelos').empty();
    if (paralelos.length === 0) {
        $cont.html('<span class="text-muted small">No se localizan aulas activas creadas para esta gestión.</span>');
    } else {
        paralelos.forEach(p => {
            var label = p.id_nivel + p.paralelo;
            $cont.append('<button type="button" class="btn btn-sm btn-outline-secondary btn-paralelo py-1.5 px-3 font-monospace fw-bold" data-id-curso="'+p.id_curso+'" data-label="'+label+'">'+label+'</button>');
        });
    }
    
    $('#grupo-paralelos').show();
    var $primerParalelo = $cont.find('.btn-paralelo').first();
    if ($primerParalelo.length) $primerParalelo.trigger('click');
});
$(document).on('click', '.btn-paralelo', function() {
    $('.btn-paralelo').removeClass('btn-paralelo-active');
    $(this).addClass('btn-paralelo-active');
    idCursoActual = $(this).data('id-curso');
    var label     = $(this).data('label');
    $('.label-curso-global').text(label);
    $('#contenedor-modulos').show();
    $('#panel-notes').hide();
    // Actualiza y muestra la barra de reportes del curso
    $('#btn-centralizador-pdf').attr('href', 'generar_reporte.php?action=centralizador&id_curso=' + idCursoActual);
    $('#btn-centralizador-excel').attr('href', 'generar_reporte.php?action=centralizador&id_curso=' + idCursoActual + '&format=excel');
    $('#btn-boletines-pdf').attr('href', 'generar_reporte.php?action=boletines&id_curso=' + idCursoActual);
    $('#toolbar-reportes-curso').show();
    cargarEstudiantes(idCursoActual);
    cargarMaterias(idCursoActual);
});
function cargarEstudiantes(id_curso) {
    $('#badge-total-alumnos').text('...');
    $('#cuerpo-alumnos').html('<tr><td colspan="5" class="loader-container"><div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>Sincronizando estudiantes matriculados...</td></tr>');
    var gestionParam = GESTION_ACTUAL !== null ? '&id_gestion=' + GESTION_ACTUAL : '';
    $.get(API_BASE_URL + '/inscripciones?id_curso=' + id_curso + '&id_curso_filtro=' + id_curso + gestionParam, function(res) {
        if (res.data && res.data.length > 0) {
            procesarAlumnosAPI(res.data);
        } else {
            $.get(API_BASE_URL + '/estudiantes?id_curso=' + id_curso + '&id_curso_filtro=' + id_curso + '&curso_filtro=' + id_curso + gestionParam, function(resEst) {
                procesarAlumnosAPI(resEst.data || []);
            }).fail(errorAlumnos);
        }
    }).fail(function() {
        $.get(API_BASE_URL + '/estudiantes?id_curso_filtro=' + id_curso + gestionParam, function(resEst) {
            procesarAlumnosAPI(resEst.data || []);
        }).fail(errorAlumnos);
    });
}
function errorAlumnos() {
    $('#cuerpo-alumnos').html('<tr><td colspan="5" class="text-center text-danger py-3 fw-medium">No se logró establecer comunicación con el listado de alumnos.</td></tr>');
}
function procesarAlumnosAPI(listaOriginal) {
    var html = '';
    var listaNormalizada = [];
    if (listaOriginal && listaOriginal.length > 0) {
        listaOriginal.forEach(function(item) {
            var alumno = item.estudiante || item.alumno || item;
            listaNormalizada.push({
                id_estudiante: alumno.id_estudiante || item.id_estudiante || alumno.id || item.id || '',
                ci:       alumno.ci       || item.ci       || '',
                expedido: alumno.expedido || item.expedido || '',
                apellidos:alumno.apellidos|| item.apellidos|| '',
                nombres:  alumno.nombres  || item.nombres  || '',
                telefono: alumno.telefono || item.telefono || '—'
            });
        });
        listaNormalizada.sort((a, b) => a.apellidos.toUpperCase().localeCompare(b.apellidos.toUpperCase()));
        listaNormalizada.forEach(function(e, idx) {
            var ciCompleto = (e.ci + ' ' + e.expedido).trim();
            html += '<tr>' +
                '<td class="text-center text-muted fw-bold">' + (idx + 1) + '</td>' +
                '<td class="fw-bold text-dark fs-6">' + escHtml(e.apellidos) + ' ' + escHtml(e.nombres) + '</td>' +
                '<td class="font-monospace text-secondary fw-bold fs-6">' + escHtml(ciCompleto) + '</td>' +
                '<td class="text-muted fw-medium">' + escHtml(e.telefono) + '</td>' +
                '<td class="text-center">' +
                    '<a href="ficha_estudiante.php?id=' + e.id_estudiante + '" class="btn btn-sm btn-primary px-2.5 d-inline-flex align-items-center gap-1 shadow-sm" title="Ver Expediente Completo">' +
                        '<span class="material-icons fs-6">folder_shared</span> <span style="font-size:12px; font-weight:600;">Ver Expediente</span>' +
                    '</a>' +
                '</td>' +
                '</tr>';
        });
        $('#badge-total-alumnos').text(listaNormalizada.length + ' alumnos');
    } else {
        html = '<tr><td colspan="5" class="text-center text-muted py-4"><span class="material-icons fs-3 text-black-50 mb-1 d-block">info</span>Sin alumnos inscritos para el aula y gestión configurada.</td></tr>';
        $('#badge-total-alumnos').text('0 alumnos');
    }
    $('#cuerpo-alumnos').html(html);
}
function cargarMaterias(id_curso) {
    $('#cuerpo-materias').html('<tr><td colspan="4" class="loader-container"><div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>Mapeando asignaturas asignadas...</td></tr>');
    var gestionParam = GESTION_ACTUAL !== null ? '?id_gestion=' + GESTION_ACTUAL : '';
    $.getJSON(API_BASE_URL + '/academico/curso/' + id_curso + gestionParam, function(res) {
        var materias = (res.data && res.data.materias) ? res.data.materias : [];
        if (materias.length === 0) {
            $('#cuerpo-materias').html('<tr><td colspan="4" class="text-center text-muted py-4">No se detectaron asignaturas vigentes asociadas a este curso.</td></tr>');
            return;
        }
        var html = '';
        materias.forEach(function(m) {
            var sinDocente  = (m.docente === 'SIN DOCENTE');
            var docenteHtml = sinDocente
                ? '<span class="text-danger fw-medium d-inline-flex align-items-center gap-1"><span class="material-icons fs-6">error_outline</span> SIN DOCENTE ASIGNADO</span>'
                : '<span class="text-secondary d-inline-flex align-items-center gap-1"><span class="material-icons fs-6 text-muted">person_outline</span> ' + escHtml(m.docente) + '</span>';
            html += '<tr class="fila-materia-clic" data-id-materia="'+m.id_materia+'" data-nombre-materia="'+escHtml(m.materia)+'">' +
                '<td class="ps-4 font-monospace fw-bold text-dark">'+escHtml(m.sigla)+'</td>' +
                '<td class="fw-bold text-dark">'+escHtml(m.materia)+'</td>' +
                '<td>'+docenteHtml+'</td>' +
                '<td class="text-center">' +
                    '<div class="d-inline-flex gap-1">' +
                        '<button class="btn btn-sm btn-info text-white btn-ver-notas d-inline-flex align-items-center gap-1 shadow-sm" data-id-materia="'+m.id_materia+'" data-nombre="'+escHtml(m.materia)+'"><span class="material-icons fs-6">format_list_numbered</span> Centralizador</button>' +
                        '<a href="generar_reporte.php?action=acta&id_curso='+idCursoActual+'&id_materia='+m.id_materia+'" target="_blank" onclick="event.stopPropagation();" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center shadow-sm" title="Descargar Acta PDF de esta materia">' +
                            '<span class="material-icons fs-6">picture_as_pdf</span>' +
                        '</a>' +
                    '</div>' +
                '</td>' +
                '</tr>';
        });
        $('#cuerpo-materias').html(html);
    }).fail(function() {
        $('#cuerpo-materias').html('<tr><td colspan="4" class="text-center text-danger py-3">Error al descargar la estructura académica de asignaturas.</td></tr>');
    });
}
$(document).on('click', '.btn-ver-notas', function(e) {
    e.stopPropagation();
    var id_materia = $(this).data('id-materia');
    var nombreMat  = $(this).data('nombre');
    $('.fila-materia-clic').removeClass('seleccionada');
    $(this).closest('tr').addClass('seleccionada');
    idMateriaActual = id_materia;
    $('#label-materia-notas').text(nombreMat);
    $('#btn-acta-pdf').attr('href', 'generar_reporte.php?action=acta&id_curso=' + idCursoActual + '&id_materia=' + id_materia);
    $('#btn-acta-excel').attr('href', 'generar_reporte.php?action=acta&id_curso=' + idCursoActual + '&id_materia=' + id_materia + '&format=excel');
    $('#panel-notes').show();
    $('#cuerpo-notes').html('<tr><td colspan="9" class="loader-container"><div class="spinner-border spinner-border-sm text-info me-2" role="status"></div>Extrayendo calificaciones de estudiantes...</td></tr>');
    var gestionParam = GESTION_ACTUAL !== null ? '&id_gestion=' + GESTION_ACTUAL : '';
    var url = API_BASE_URL + '/notas/estudiantes?id_curso='+idCursoActual+'&id_materia='+id_materia + gestionParam;
    $.getJSON(url, function(res) {
        var estudiantes = (res && res.data) ? res.data : [];
        if (estudiantes.length === 0) {
            $('#cuerpo-notes').html('<tr><td colspan="9" class="text-center text-muted py-4"><span class="material-icons d-block text-black-50 mb-1 fs-4">assignment_late</span> No se encontraron planillas de notas inicializadas para esta materia.</td></tr>');
            return;
        }
        estudiantes.sort((a,b) => (a.apellidos||'').localeCompare(b.apellidos||''));
        var html = '';
        estudiantes.forEach(function(e, idx) {
            var b1   = parseInt(e.primer_bimestre  || 0);
            var b2   = parseInt(e.segundo_bimestre || 0);
            var b3   = parseInt(e.tercer_bimestre  || 0);
            var b4   = parseInt(e.cuarto_bimestre  || 0);
            var prom = e.promedio_final !== undefined ? parseInt(e.promedio_final) : Math.round((b1+b2+b3+b4)/4);
            var badgeClass = prom >= 61 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';
            var ci   = escHtml((e.ci||'') + ' ' + (e.expedido||''));
            html += '<tr>' +
                '<td class="text-muted fw-bold">'+(idx+1)+'</td>' +
                '<td class="text-start fw-bold text-dark fs-6">'+escHtml((e.apellidos||'')+', '+(e.nombres||''))+'</td>' +
                '<td class="font-monospace text-secondary fw-bold fs-6">'+ci+'</td>' +
                '<td class="font-monospace text-muted fw-bold">'+b1+'</td>' +
                '<td class="font-monospace text-muted fw-bold">'+b2+'</td>' +
                '<td class="font-monospace text-muted fw-bold">'+b3+'</td>' +
                '<td class="font-monospace text-muted fw-bold">'+b4+'</td>' +
                '<td><span class="badge px-3 py-1.5 font-monospace rounded-pill '+badgeClass+'" style="font-size:14px; font-weight:700;">'+prom+'</span></td>' +
                '<td>' +
                    '<a href="ficha_estudiante.php?id='+e.id_estudiante+'" class="btn btn-sm btn-primary px-2 d-inline-flex align-items-center gap-1 shadow-sm" title="Ver Expediente">' +
                        '<span class="material-icons fs-6">folder_shared</span> <span style="font-size:11px; font-weight:600;">Expediente</span>' +
                    '</a>' +
                '</td>' +
                '</tr>';
        });
        $('#cuerpo-notes').html(html);
        $('html,body').animate({ scrollTop: $('#panel-notes').offset().top - 80 }, 400);
    }).fail(function() {
        $('#cuerpo-notes').html('<tr><td colspan="9" class="text-center text-danger py-3">Error al compilar las calificaciones de la planilla.</td></tr>');
    });
});
$(document).on('click', '.fila-materia-clic', function() {
    $(this).find('.btn-ver-notas').trigger('click');
});
$('#btn-cerrar-notas').on('click', function() {
    $('#panel-notes').hide();
    $('.fila-materia-clic').removeClass('seleccionada');
});
var timerId = null;
var xhrBusqueda = null;
$('#input-buscar-est').on('input', function() {
    var termino = $(this).val().trim();
    clearTimeout(timerId);
    if (termino.length < 2) {
        $('#resultados-busqueda').hide().empty();
        return;
    }
    timerId = setTimeout(function() {
        if (xhrBusqueda) xhrBusqueda.abort();
        $('#buscador-spinner').show();
        var gestionParam = GESTION_ACTUAL !== null ? '&id_gestion=' + GESTION_ACTUAL : '';
        var url = API_BASE_URL + '/estudiantes'
                + '?buscar=' + encodeURIComponent(termino)
                + '&sucursal=' + SUCURSAL_ACTUAL
                + gestionParam;
        xhrBusqueda = $.getJSON(url, function(res) {
            $('#buscador-spinner').hide();
            var lista = res.data || [];
            renderResultados(lista, termino);
        }).fail(function(xhr) {
            if (xhr.statusText !== 'abort') {
                $('#buscador-spinner').hide();
                $('#resultados-busqueda').html('<div class="sin-resultados text-danger">Error al consultar la API.</div>').show();
            }
        });
    }, 350);
});
function renderResultados(lista, termino) {
    var $r = $('#resultados-busqueda').empty();
    if (lista.length === 0) {
        $r.html('<div class="sin-resultados"><span class="material-icons d-block mb-1 text-black-50">search_off</span>Sin resultados para «' + escHtml(termino) + '»</div>').show();
        return;
    }
    lista.forEach(function(e) {
        var ciText     = (e.ci || '') + (e.expedido ? ' ' + e.expedido : '');
        var cursoText  = '';
        if (e.nombre_nivel && e.paralelo) {
            cursoText = e.nombre_nivel + ' · ' + e.paralelo;
        } else if (e.nombre_carrera) {
            cursoText = e.nombre_carrera;
        }
        var $item = $('<div class="item-resultado">')
            .append(
                $('<div style="flex:1; min-width:0;">').append(
                    $('<div class="est-nombre">').text((e.apellidos || '') + ' ' + (e.nombres || ''))
                ).append(
                    $('<div class="est-meta">').html(
                        '<span class="material-icons" style="font-size:11px;vertical-align:middle;">badge</span> ' + escHtml(ciText) +
                        (e.telefono ? ' &nbsp;·&nbsp; <span class="material-icons" style="font-size:11px;vertical-align:middle;">phone</span> ' + escHtml(e.telefono) : '')
                    )
                )
            );
        if (cursoText) {
            $item.append(
                $('<span class="badge bg-primary-subtle text-primary border border-primary-subtle est-badge" style="font-size:11px;">').text(cursoText)
            );
        }
        $item.on('click', function() {
            window.location.href = 'ficha_estudiante.php?id=' + e.id_estudiante;
        });
        $r.append($item);
    });
    $r.show();
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('#panel-buscador').length) {
        $('#resultados-busqueda').hide();
    }
});

$('#input-buscar-est').on('focus', function() {
    if ($('#resultados-busqueda').children().length) {
        $('#resultados-busqueda').show();
    }
});
$(document).ready(function() {
    var $primera = $('#tabs-carrera li:first-child a');
    if ($primera.length) $primera.trigger('click');
});
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?php layout_foot(); ?>