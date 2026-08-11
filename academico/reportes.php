<?php
require_once __DIR__ . '/config_api.php';
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php");
    exit();
}

$sucursal_sesion = resolver_sucursal($_SESSION['role_id']);

$gestiones_api  = json_decode(@file_get_contents(API_BASE_URL . "/gestion"), true)['data'] ?? [];
$id_gestion_actual     = null;
$nombre_gestion_actual = '';
foreach ($gestiones_api as $g) {
    if ((int)$g['estado_bt'] === 1 && strtoupper(trim($g['sucursal_varchar'])) === $sucursal_sesion) {
        $id_gestion_actual     = (int)$g['id_gestion'];
        $nombre_gestion_actual = $g['gestion_varchar'];
        break;
    }
}

$carreras_todas = json_decode(@file_get_contents(API_BASE_URL . "/carreras"), true)['data'] ?? [];
$cursos_todos    = json_decode(@file_get_contents(API_BASE_URL . "/cursos"),   true)['data'] ?? [];
$carreras = array_values(array_filter($carreras_todas, fn($ca) =>
    strtoupper(trim($ca['sucursal'] ?? $ca['sucursal_varchar'] ?? '')) === $sucursal_sesion
));
$cursos = array_values(array_filter($cursos_todos, function($cu) use ($sucursal_sesion, $id_gestion_actual) {
    $cumpleSucursal = strtoupper(trim($cu['sucursal_varchar'] ?? $cu['sucursal'] ?? '')) === $sucursal_sesion;
    if ($id_gestion_actual !== null) {
        return $cumpleSucursal && (int)($cu['id_gestion'] ?? null) === $id_gestion_actual;
    }
    return $cumpleSucursal;
}));

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Centro de Reportes', $current_page, $_SESSION['role_id']);
?>
<style>
    .reportes-header {
        background: linear-gradient(135deg, #8d191d 0%, #580c0f 100%);
        border-radius: 12px;
    }

    #tabs-reportes .nav-link {
        color: var(--texto-oscuro, #2d3436);
        font-weight: 600;
        font-size: 14px;
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        padding: 0.85rem 1.1rem;
    }
    #tabs-reportes .nav-link .material-icons {
        font-size: 18px;
        vertical-align: middle;
        margin-right: 6px;
    }
    #tabs-reportes .nav-link.active {
        color: var(--rojo-elegante, #8d191d);
        border-bottom-color: var(--rojo-elegante, #8d191d);
        background: transparent;
    }
    #tabs-reportes .nav-link:not(.active):hover {
        color: var(--rojo-elegante, #8d191d);
        border-bottom-color: #e9c6c7;
    }

    #panel-buscador-rep { position: relative; }
    #buscador-rep-spinner {
        position: absolute; right: 14px; top: 50%;
        transform: translateY(-50%); display: none;
    }
    #resultados-busqueda-rep {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050;
        background: #fff; border: 1px solid #dee2e6; border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        max-height: 360px; overflow-y: auto; display: none;
    }
    #resultados-busqueda-rep .item-resultado {
        display: flex; align-items: center; gap: 12px; padding: 10px 14px;
        cursor: pointer; border-bottom: 1px solid #f1f3f5; transition: background 0.12s;
    }
    #resultados-busqueda-rep .item-resultado:last-child { border-bottom: none; }
    #resultados-busqueda-rep .item-resultado:hover { background-color: #f8f9fc; }
    #resultados-busqueda-rep .item-resultado .est-nombre { font-weight: 700; color: #1e272e; font-size: 15px; }
    #resultados-busqueda-rep .item-resultado .est-meta { font-size: 12px; color: #6c757d; }
    #resultados-busqueda-rep .item-resultado .est-badge { margin-left: auto; white-space: nowrap; }
    #resultados-busqueda-rep .sin-resultados { padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
    .thumb-est-rep {
        width: 38px; height: 38px; border-radius: 50%; object-fit: cover;
        border: 1px solid #e9ecef; flex-shrink: 0;
    }

    #tarjeta-est-seleccionado { display: none; }
    #placeholder-sin-seleccion { display: block; }

    .btn-reporte-accion {
        font-weight: 600; font-size: 13px; border-radius: 8px;
    }

    .tab-en-construccion {
        border: 2px dashed #dee2e6; border-radius: 12px;
        padding: 3rem 1.5rem; text-align: center; color: #6c757d;
    }

    #tabs-carrera-rep .nav-link.active {
        background-color: var(--rojo-elegante, #8d191d) !important;
        color: #fff !important;
    }
    .btn-paralelo-rep-active {
        background-color: #ffc107 !important;
        color: #212529 !important;
        border-color: #ffc107 !important;
        font-weight: bold;
    }
    .fila-materia-rep-sindocente { color: #b00020; }
</style>

<div class="reportes-header p-4 mb-4 d-flex align-items-center gap-3">
    <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15);">
        <span class="material-icons fs-1 text-white">summarize</span>
    </div>
    <div>
        <h4 class="fw-bold mb-1 text-white">Centro de Reportes</h4>
        <p class="text-white-50 small mb-0">
            Genera historiales, actas, centralizadores, boletines y listados generales en PDF o Excel desde un solo lugar.
        </p>
    </div>
</div>

<div class="card border-0 shadow-sm bg-white">
    <div class="border-bottom px-2">
        <ul class="nav" id="tabs-reportes" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-historial-btn" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button" role="tab">
                    <span class="material-icons">badge</span>Historial Individual
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-curso-btn" data-bs-toggle="tab" data-bs-target="#tab-curso" type="button" role="tab">
                    <span class="material-icons">groups</span>Actas / Centralizador / Boletines
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-listados-btn" data-bs-toggle="tab" data-bs-target="#tab-listados" type="button" role="tab">
                    <span class="material-icons">list_alt</span>Listados Generales
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content p-4">

        <div class="tab-pane fade show active" id="tab-historial" role="tabpanel">
            <p class="text-muted small mb-3">
                Busca un estudiante por nombre o C.I. para descargar su historial académico completo (todos los bimestres y gestiones).
            </p>

            <div style="max-width: 520px;">
                <div id="panel-buscador-rep" class="position-relative">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <span class="material-icons text-muted" style="font-size:20px;">search</span>
                        </span>
                        <input type="text"
                               id="input-buscar-est-rep"
                               class="form-control border-start-0 ps-0"
                               placeholder="Buscar estudiante por nombre o C.I.…"
                               autocomplete="off"
                               style="font-size:14px;">
                        <span class="spinner-border spinner-border-sm text-secondary" id="buscador-rep-spinner" role="status"></span>
                    </div>
                    <div id="resultados-busqueda-rep"></div>
                </div>
            </div>

            <div class="mt-4">
                <div id="placeholder-sin-seleccion" class="text-center text-muted py-5" style="border: 2px dashed #e9ecef; border-radius: 12px;">
                    <span class="material-icons d-block mb-2" style="font-size: 40px; opacity: 0.4;">person_search</span>
                    <span class="small">Selecciona un estudiante de la búsqueda para generar su historial académico.</span>
                </div>

                <div id="tarjeta-est-seleccionado" class="card border-0 shadow-sm" style="border-left: 5px solid var(--rojo-elegante) !important;">
                    <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
                        <img id="est-sel-foto" src="" class="rounded-circle" style="width:64px; height:64px; object-fit:cover; border:2px solid #f1f3f5;" onerror="this.src='../uploads/fotos/default.png';">
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1 text-dark" id="est-sel-nombre" style="font-size:17px;"></h5>
                            <div class="text-muted small">
                                <span class="material-icons" style="font-size:13px; vertical-align:middle;">badge</span>
                                <span id="est-sel-ci"></span>
                                <span id="est-sel-curso-wrap" class="ms-2">
                                    &nbsp;·&nbsp;<span class="material-icons" style="font-size:13px; vertical-align:middle;">school</span>
                                    <span id="est-sel-curso"></span>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a id="btn-hist-pdf" href="#" target="_blank" class="btn btn-danger btn-reporte-accion d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:16px;">picture_as_pdf</span> PDF
                            </a>
                            <a id="btn-hist-excel" href="#" target="_blank" class="btn btn-success btn-reporte-accion d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:16px;">grid_on</span> Excel
                            </a>
                            <a id="btn-hist-ficha" href="#" class="btn btn-outline-secondary btn-reporte-accion d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:16px;">visibility</span> Ver Ficha
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-curso" role="tabpanel">
            <p class="text-muted small mb-3">
                Elige una carrera y un paralelo para descargar el Centralizador y los Boletines del curso completo, o el Acta de una materia específica.
            </p>

            <?php if (empty($carreras)): ?>
                <div class="alert alert-warning small mb-0">No hay carreras registradas para esta sucursal.</div>
            <?php else: ?>
            <ul class="nav nav-pills mb-3" id="tabs-carrera-rep">
                <?php foreach ($carreras as $i => $ca): ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link <?= $i === 0 ? 'active' : ''; ?>" data-id-carrera="<?= $ca['id_carrera']; ?>">
                            <?= htmlspecialchars($ca['nombre'] ?? $ca['carrera'] ?? ('Carrera ' . $ca['id_carrera'])); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div id="grupo-paralelos-rep" class="d-flex flex-wrap align-items-center gap-2 mb-4">
                <span class="text-muted small me-1">Paralelo:</span>
                <div id="botones-paralelos-rep" class="d-flex flex-wrap gap-2"></div>
            </div>

            <div id="curso-sin-seleccion-rep" class="text-center text-muted py-5" style="border: 2px dashed #e9ecef; border-radius: 12px;">
                <span class="material-icons d-block mb-2" style="font-size: 40px; opacity: 0.4;">groups</span>
                <span class="small">Selecciona un paralelo para ver sus opciones de reporte.</span>
            </div>

            <div id="curso-contenido-rep" style="display:none;">

                <div class="card border-0 shadow-sm mb-4" style="border-left: 5px solid var(--rojo-elegante) !important;">
                    <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1 text-dark" id="curso-sel-label" style="font-size:17px;"></h5>
                            <p class="text-muted small mb-0">Reportes de todo el curso (todas las materias).</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a id="btn-central-pdf" href="#" target="_blank" class="btn btn-danger btn-reporte-accion d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:16px;">picture_as_pdf</span> Centralizador PDF
                            </a>
                            <a id="btn-central-excel" href="#" target="_blank" class="btn btn-success btn-reporte-accion d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:16px;">grid_on</span> Centralizador Excel
                            </a>
                            <a id="btn-boletines-pdf-rep" href="#" target="_blank" class="btn btn-outline-danger btn-reporte-accion d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:16px;">picture_as_pdf</span> Boletines (todos)
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 px-4">
                        <h6 class="fw-bold text-dark mb-0" style="font-size:14px;">
                            <span class="material-icons" style="font-size:16px; vertical-align:middle;">menu_book</span>
                            Acta por Materia
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Sigla</th>
                                    <th>Asignatura</th>
                                    <th>Docente</th>
                                    <th class="text-center pe-4">Acta</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-materias-rep">
                                <tr><td colspan="4" class="text-center text-muted py-4">Selecciona un paralelo para ver sus materias.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="tab-listados" role="tabpanel">
            <p class="text-muted small mb-3">
                Descarga listados generales de la sucursal <strong><?= htmlspecialchars($sucursal_sesion); ?></strong> en PDF o Excel.
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid var(--rojo-elegante) !important;">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="material-icons text-danger" style="font-size:32px;">groups</span>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">Estudiantes</h6>
                                <p class="text-muted small mb-2">Listado general de estudiantes registrados.</p>
                                <div class="d-flex gap-2">
                                    <a href="generar_reporte.php?action=general&tipo=estudiantes&sucursal=<?= urlencode($sucursal_sesion); ?>" target="_blank" class="btn btn-sm btn-danger btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">picture_as_pdf</span> PDF</a>
                                    <a href="generar_reporte.php?action=general&tipo=estudiantes&sucursal=<?= urlencode($sucursal_sesion); ?>&format=excel" target="_blank" class="btn btn-sm btn-success btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">grid_on</span> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid var(--rojo-elegante) !important;">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="material-icons text-danger" style="font-size:32px;">manage_accounts</span>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">Usuarios</h6>
                                <p class="text-muted small mb-2">Listado general de usuarios del sistema.</p>
                                <div class="d-flex gap-2">
                                    <a href="generar_reporte.php?action=general&tipo=usuarios&sucursal=<?= urlencode($sucursal_sesion); ?>" target="_blank" class="btn btn-sm btn-danger btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">picture_as_pdf</span> PDF</a>
                                    <a href="generar_reporte.php?action=general&tipo=usuarios&sucursal=<?= urlencode($sucursal_sesion); ?>&format=excel" target="_blank" class="btn btn-sm btn-success btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">grid_on</span> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid var(--rojo-elegante) !important;">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="material-icons text-danger" style="font-size:32px;">menu_book</span>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">Materias</h6>
                                <p class="text-muted small mb-2">Listado general de materias/asignaturas.</p>
                                <div class="d-flex gap-2">
                                    <a href="generar_reporte.php?action=general&tipo=materias&sucursal=<?= urlencode($sucursal_sesion); ?>" target="_blank" class="btn btn-sm btn-danger btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">picture_as_pdf</span> PDF</a>
                                    <a href="generar_reporte.php?action=general&tipo=materias&sucursal=<?= urlencode($sucursal_sesion); ?>&format=excel" target="_blank" class="btn btn-sm btn-success btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">grid_on</span> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid var(--rojo-elegante) !important;">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="material-icons text-danger" style="font-size:32px;">school</span>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">Cursos</h6>
                                <p class="text-muted small mb-2">Listado general de cursos/paralelos.</p>
                                <div class="d-flex gap-2">
                                    <a href="generar_reporte.php?action=general&tipo=cursos&sucursal=<?= urlencode($sucursal_sesion); ?>" target="_blank" class="btn btn-sm btn-danger btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">picture_as_pdf</span> PDF</a>
                                    <a href="generar_reporte.php?action=general&tipo=cursos&sucursal=<?= urlencode($sucursal_sesion); ?>&format=excel" target="_blank" class="btn btn-sm btn-success btn-reporte-accion d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:14px;">grid_on</span> Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const SUCURSAL_ACTUAL = "<?= $sucursal_sesion; ?>";
const GESTION_ACTUAL   = <?= $id_gestion_actual !== null ? $id_gestion_actual : 'null'; ?>;
const CURSOS_REP        = <?= json_encode($cursos); ?>;

function escHtmlRep(s) {
    return $('<div>').text(s == null ? '' : s).html();
}

var timerIdRep = null;
var xhrBusquedaRep = null;

function buscarEstudiantesRep(termino) {
    if (xhrBusquedaRep) xhrBusquedaRep.abort();
    $('#buscador-rep-spinner').show();

    var gestionParam = GESTION_ACTUAL !== null ? '&id_gestion=' + GESTION_ACTUAL : '';
    var buscarParam  = termino.length > 0 ? '&buscar=' + encodeURIComponent(termino) : '';
    var url = API_BASE_URL + '/estudiantes'
            + '?sucursal=' + SUCURSAL_ACTUAL
            + buscarParam
            + gestionParam;

    xhrBusquedaRep = $.getJSON(url, function(res) {
        $('#buscador-rep-spinner').hide();
        renderResultadosRep(res.data || [], termino);
    }).fail(function(xhr) {
        if (xhr.statusText !== 'abort') {
            $('#buscador-rep-spinner').hide();
            $('#resultados-busqueda-rep').html('<div class="sin-resultados text-danger">Error al consultar la API.</div>').show();
        }
    });
}

$('#input-buscar-est-rep').on('input', function() {
    var termino = $(this).val().trim();
    clearTimeout(timerIdRep);
    timerIdRep = setTimeout(function() {
        buscarEstudiantesRep(termino);
    }, 350);
});

$('#input-buscar-est-rep').on('focus', function() {
    var termino = $(this).val().trim();
    if ($('#resultados-busqueda-rep').children().length) {
        $('#resultados-busqueda-rep').show();
    } else {
        buscarEstudiantesRep(termino);
    }
});

function renderResultadosRep(lista, termino) {
    var $r = $('#resultados-busqueda-rep').empty();

    if (lista.length === 0) {
        var msg = termino.length > 0
            ? 'Sin resultados para «' + escHtmlRep(termino) + '»'
            : 'No hay estudiantes registrados para esta sucursal.';
        $r.html('<div class="sin-resultados"><span class="material-icons d-block mb-1 text-black-50">search_off</span>' + msg + '</div>').show();
        return;
    }

    lista.forEach(function(e) {
        var foto      = e.foto_ruta ? '../' + e.foto_ruta : '../uploads/fotos/default.png';
        var ciText    = (e.ci || '') + (e.expedido ? ' ' + e.expedido : '');
        var cursoText = '';
        if (e.nombre_nivel && e.paralelo) {
            cursoText = e.nombre_nivel + ' · ' + e.paralelo;
        } else if (e.nombre_carrera) {
            cursoText = e.nombre_carrera;
        }

        var $item = $('<div class="item-resultado">')
            .append('<img src="' + foto + '" class="thumb-est-rep" onerror="this.src=\'../uploads/fotos/default.png\'">')
            .append(
                $('<div style="flex:1; min-width:0;">').append(
                    $('<div class="est-nombre">').text((e.apellidos || '') + ' ' + (e.nombres || ''))
                ).append(
                    $('<div class="est-meta">').html(
                        '<span class="material-icons" style="font-size:11px;vertical-align:middle;">badge</span> ' + escHtmlRep(ciText)
                    )
                )
            );

        if (cursoText) {
            $item.append(
                $('<span class="badge bg-primary-subtle text-primary border border-primary-subtle est-badge" style="font-size:11px;">').text(cursoText)
            );
        }

        $item.on('click', function() {
            seleccionarEstudianteRep(e);
            $('#resultados-busqueda-rep').hide();
            $('#input-buscar-est-rep').val((e.apellidos || '') + ' ' + (e.nombres || ''));
        });

        $r.append($item);
    });

    $r.show();
}

function seleccionarEstudianteRep(e) {
    var foto      = e.foto_ruta ? '../' + e.foto_ruta : '../uploads/fotos/default.png';
    var ciText    = (e.ci || '') + (e.expedido ? ' ' + e.expedido : '');
    var cursoText = '';
    if (e.nombre_nivel && e.paralelo) {
        cursoText = e.nombre_nivel + ' · ' + e.paralelo;
    } else if (e.nombre_carrera) {
        cursoText = e.nombre_carrera;
    }

    $('#est-sel-foto').attr('src', foto);
    $('#est-sel-nombre').text((e.apellidos || '') + ' ' + (e.nombres || ''));
    $('#est-sel-ci').text(ciText || '—');

    if (cursoText) {
        $('#est-sel-curso').text(cursoText);
        $('#est-sel-curso-wrap').show();
    } else {
        $('#est-sel-curso-wrap').hide();
    }

    $('#btn-hist-pdf').attr('href', 'generar_reporte.php?action=historial&id_estudiante=' + e.id_estudiante);
    $('#btn-hist-excel').attr('href', 'generar_reporte.php?action=historial&id_estudiante=' + e.id_estudiante + '&format=excel');
    $('#btn-hist-ficha').attr('href', 'ficha_estudiante.php?id=' + e.id_estudiante);

    $('#placeholder-sin-seleccion').hide();
    $('#tarjeta-est-seleccionado').fadeIn(150);
}

var idCarreraActualRep = null;
var idCursoActualRep   = null;

$('#tabs-carrera-rep').on('click', 'a', function(e) {
    e.preventDefault();
    $('#tabs-carrera-rep a').removeClass('active');
    $(this).addClass('active');
    idCarreraActualRep = $(this).data('id-carrera');
    idCursoActualRep   = null;

    $('#curso-contenido-rep').hide();
    $('#curso-sin-seleccion-rep').show();

    var paralelos = CURSOS_REP.filter(c =>
        parseInt(c.id_carrera) === parseInt(idCarreraActualRep) &&
        parseInt(c.estado)     === 1
    ).sort((a, b) => {
        if (parseInt(a.id_nivel) !== parseInt(b.id_nivel)) return parseInt(a.id_nivel) - parseInt(b.id_nivel);
        return (a.paralelo || '').localeCompare(b.paralelo || '');
    });

    var $cont = $('#botones-paralelos-rep').empty();
    if (paralelos.length === 0) {
        $cont.html('<span class="text-muted small">No hay paralelos activos para esta carrera.</span>');
        return;
    }
    paralelos.forEach(function(p) {
        var label = p.id_nivel + p.paralelo;
        $cont.append('<button type="button" class="btn btn-sm btn-outline-secondary btn-paralelo-rep py-1 px-3 font-monospace fw-bold" data-id-curso="' + p.id_curso + '" data-label="' + label + '">' + label + '</button>');
    });

    var $primerParalelo = $cont.find('.btn-paralelo-rep').first();
    if ($primerParalelo.length) $primerParalelo.trigger('click');
});

$(document).on('click', '.btn-paralelo-rep', function() {
    $('.btn-paralelo-rep').removeClass('btn-paralelo-rep-active');
    $(this).addClass('btn-paralelo-rep-active');
    idCursoActualRep = $(this).data('id-curso');
    var label        = $(this).data('label');

    $('#curso-sel-label').text(label);
    $('#curso-sin-seleccion-rep').hide();
    $('#curso-contenido-rep').show();

    $('#btn-central-pdf').attr('href', 'generar_reporte.php?action=centralizador&id_curso=' + idCursoActualRep);
    $('#btn-central-excel').attr('href', 'generar_reporte.php?action=centralizador&id_curso=' + idCursoActualRep + '&format=excel');
    $('#btn-boletines-pdf-rep').attr('href', 'generar_reporte.php?action=boletines&id_curso=' + idCursoActualRep);

    cargarMateriasRep(idCursoActualRep);
});

function cargarMateriasRep(id_curso) {
    $('#cuerpo-materias-rep').html('<tr><td colspan="4" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>Cargando materias…</td></tr>');
    var gestionParam = GESTION_ACTUAL !== null ? '?id_gestion=' + GESTION_ACTUAL : '';
    $.getJSON(API_BASE_URL + '/academico/curso/' + id_curso + gestionParam, function(res) {
        var materias = (res.data && res.data.materias) ? res.data.materias : [];
        if (materias.length === 0) {
            $('#cuerpo-materias-rep').html('<tr><td colspan="4" class="text-center text-muted py-4">No hay materias asignadas a este curso.</td></tr>');
            return;
        }
        var html = '';
        materias.forEach(function(m) {
            var sinDocente  = (m.docente === 'SIN DOCENTE');
            var docenteHtml = sinDocente
                ? '<span class="fila-materia-rep-sindocente small"><span class="material-icons" style="font-size:14px;vertical-align:middle;">error_outline</span> Sin asignar</span>'
                : '<span class="text-secondary small">' + escHtmlRep(m.docente) + '</span>';
            html += '<tr>' +
                '<td class="ps-4 font-monospace fw-bold text-dark">' + escHtmlRep(m.sigla) + '</td>' +
                '<td class="fw-bold text-dark">' + escHtmlRep(m.materia) + '</td>' +
                '<td>' + docenteHtml + '</td>' +
                '<td class="text-center pe-4">' +
                    '<a href="generar_reporte.php?action=acta&id_curso=' + idCursoActualRep + '&id_materia=' + m.id_materia + '" target="_blank" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" title="Descargar Acta PDF de esta materia">' +
                        '<span class="material-icons" style="font-size:14px;">picture_as_pdf</span> Acta' +
                    '</a>' +
                '</td>' +
                '</tr>';
        });
        $('#cuerpo-materias-rep').html(html);
    }).fail(function() {
        $('#cuerpo-materias-rep').html('<tr><td colspan="4" class="text-center text-danger py-3">Error al cargar las materias del curso.</td></tr>');
    });
}

$('#tab-curso-btn').on('shown.bs.tab', function() {
    if (idCarreraActualRep === null) {
        $('#tabs-carrera-rep li:first-child a').trigger('click');
    }
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('#panel-buscador-rep').length) {
        $('#resultados-busqueda-rep').hide();
    }
});

$('#input-buscar-est-rep').on('focus', function() {
    if ($('#resultados-busqueda-rep').children().length) {
        $('#resultados-busqueda-rep').show();
    }
});
</script>

<?php layout_foot(); ?>