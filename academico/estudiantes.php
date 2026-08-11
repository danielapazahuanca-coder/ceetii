<?php
session_start();
// Permite acceso a Administrador (1) y Secretaria (2)
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php"); 
    exit();
}

require_once __DIR__ . '/config_api.php';

$msg = '';

// --- LÓGICA DE CONTROL DE SUCURSAL ---
$sucursal_sesion = resolver_sucursal($_SESSION['role_id']);

// ── Gestión activa desde la API (fuente de verdad) ───────────────────────────
$gestiones_api = json_decode(@file_get_contents(API_BASE_URL . "/gestion"), true)['data'] ?? [];
$id_gestion_activa   = null;
$nombre_gestion_activa = '';
foreach ($gestiones_api as $g) {
    if ((int)$g['estado_bt'] === 1 && strtoupper(trim($g['sucursal_varchar'])) === strtoupper($sucursal_sesion)) {
        $id_gestion_activa     = (int)$g['id_gestion'];
        $nombre_gestion_activa = $g['gestion_varchar'];
        break;
    }
}

// Funciones para estructuración estética de alertas con Bootstrap 5
function ok(string $t): string {
    return '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm p-3 mb-3 rounded-3" role="alert">
                <div class="d-flex align-items-center"><span class="material-icons me-2">check_circle</span><div>' . $t . '</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}
function err(string $t): string {
    return '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm p-3 mb-3 rounded-3" role="alert">
                <div class="d-flex align-items-center"><span class="material-icons me-2">error</span><div>' . $t . '</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

// Pasar id_gestion a la API para que filtre en el JOIN de inscripciones
$api_estudiantes = API_BASE_URL . "/estudiantes?sucursal=" . $sucursal_sesion;
if ($id_gestion_activa !== null) {
    $api_estudiantes .= "&id_gestion=" . $id_gestion_activa;
}

// --- ACCIÓN: Inscribir Estudiante a un Curso ---
if (isset($_POST['inscribir_estudiante'])) {
    $id_est = isset($_POST['id_estudiante']) ? (int)$_POST['id_estudiante'] : null;
    $id_cur = isset($_POST['id_curso']) ? (int)$_POST['id_curso'] : null;

    if (!$id_est || !$id_cur) {
        $msg = err('Datos de inscripción no encontrados o incompletos.');
    } else {
        $payload = [
            'id_estudiante' => $id_est, 
            'id_curso' => $id_cur
        ];
        $opts = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];
        $res = json_decode(@file_get_contents(API_BASE_URL . "/inscripciones", false, stream_context_create($opts)), true);
        
        if ($res && isset($res['status']) && $res['status'] === 'success') {
            $msg = ok('Inscripción e incorporación al aula realizada con éxito.');
        } else {
            $msg = err($res['message'] ?? 'No se pudo procesar la inscripción del estudiante.');
        }
    }
}

// --- ACCIÓN: Registrar o ACTUALIZAR Estudiante ---
if (isset($_POST['create_estudiante'])) {
    $id_estudiante_edit = isset($_POST['id_estudiante_edit']) ? trim($_POST['id_estudiante_edit']) : '';

    // Validar y sanitizar CI (solo números, máximo 12)
    $ci = preg_replace('/[^0-9]/', '', trim($_POST['ci']));
    $ci = substr($ci, 0, 12);
    
    // Validar y sanitizar nombres (solo letras, máximo 25)
    $nombres = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/', '', trim($_POST['nombres']));
    $nombres = substr($nombres, 0, 25);
    $nombres = ucwords(strtolower($nombres));
    
    // Validar y sanitizar apellidos (solo letras, máximo 25)
    $apellidos = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/', '', trim($_POST['apellidos']));
    $apellidos = substr($apellidos, 0, 25);
    $apellidos = ucwords(strtolower($apellidos));
    
    // Validar y sanitizar teléfono (solo números, máximo 8)
    $telefono = preg_replace('/[^0-9]/', '', trim($_POST['telefono']));
    $telefono = substr($telefono, 0, 8);

    $payload = [
        'ci' => $ci,
        'expedido' => strtoupper(trim($_POST['expedido'])),
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'telefono' => $telefono,
        'sucursal_varchar' => $sucursal_sesion,
        'estado' => 1
    ];

    if (!empty($id_estudiante_edit)) {
        // MODO EDICIÓN (PUT)
        $opts = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'PUT',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];
        $res = json_decode(@file_get_contents(API_BASE_URL . "/estudiantes/" . (int)$id_estudiante_edit, false, stream_context_create($opts)), true);
        if ($res && $res['status'] === 'success') {
            $msg = ok('Ficha de información del alumno actualizada correctamente.');
        } else {
            $msg = err($res['message'] ?? 'No se pudieron guardar los cambios en la ficha.');
        }
    } else {
        // MODO REGISTRO NUEVO (POST)
        $opts = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];
        $res = json_decode(@file_get_contents(API_BASE_URL . "/estudiantes", false, stream_context_create($opts)), true);
        if ($res && $res['status'] === 'success') {
            $msg = ok('Nuevo estudiante registrado con éxito en el sistema.');
        } else {
            $msg = err($res['message'] ?? 'Ocurrió un problema al dar de alta al estudiante.');
        }
    }
}

// --- ACCIÓN: Eliminar Estudiante ---

if (isset($_GET['delete_id'])) {
    $id_est_eliminar = (int)$_GET['delete_id'];

    // 1) Eliminar todas las notas del estudiante (nuevo)
    $opts_delete_notas = ['http' => ['method' => 'DELETE', 'ignore_errors' => true]];
    @file_get_contents(API_BASE_URL . "/notas/estudiante/" . $id_est_eliminar, false, stream_context_create($opts_delete_notas));

    // 2) Eliminar todas las inscripciones del estudiante
    $opts_delete_inscripciones = ['http' => ['method' => 'DELETE', 'ignore_errors' => true]];
    @file_get_contents(API_BASE_URL . "/inscripciones/estudiante/" . $id_est_eliminar, false, stream_context_create($opts_delete_inscripciones));

    // 3) Eliminar el estudiante
    $opts = ['http' => ['method' => 'DELETE', 'ignore_errors' => true]];
    $res = json_decode(@file_get_contents(API_BASE_URL . "/estudiantes/" . $id_est_eliminar, false, stream_context_create($opts)), true);

    if ($res && $res['status'] === 'success') {
        $msg = ok('El registro del estudiante ha sido removido de forma definitiva junto con sus inscripciones.');
    } else {
        $msg = err($res['message'] ?? 'No se pudo eliminar la ficha del alumno.');
    }
}

// Cargar catálogos e información base de las APIs
$estudiantes  = json_decode(@file_get_contents($api_estudiantes), true)['data'] ?? [];
$carreras_api = json_decode(@file_get_contents(API_BASE_URL . "/carreras"), true)['data'] ?? [];

if ($id_gestion_activa !== null) {
    $estudiantes = array_map(function($e) use ($id_gestion_activa) {
        $gestion_est = $e['id_gestion'] ?? $e['gestion_id'] ?? null;
        if (!empty($e['paralelo']) && $gestion_est !== null && (int)$gestion_est !== $id_gestion_activa) {
            $e['paralelo']       = null;
            $e['nombre_carrera'] = null;
            $e['nombre_nivel']   = null;
        }
        return $e;
    }, $estudiantes);
}

$carreras_tab2 = array_values(array_filter($carreras_api, function($ca) use ($sucursal_sesion) {
    return isset($ca['sucursal']) && strtoupper(trim($ca['sucursal'])) === strtoupper(trim($sucursal_sesion));
}));

// --- ESTRUCTURACIÓN DE LOS DATOS PARA LA VISTA ---
$estudiantes_sin_curso = [];
$estructura_carreras = [];

foreach ($estudiantes as $e) {
    if (empty($e['paralelo'])) {
        $estudiantes_sin_curso[] = $e;
    } else {
        $carrera_nombre = $e['nombre_carrera'] ?? 'CARRERA NO ESPECIFICADA';
        $curso_nombre = ($e['id_nivel'] ?? '') . ($e['paralelo'] ?? '');
        $estructura_carreras[$carrera_nombre][$curso_nombre][] = $e;
    }
}

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Registro de Estudiantes', $current_page, $_SESSION['role_id']);
?>

<style>
    body {
        background-color: #f4f6f9 !important;
    }
    .tabla-minimalista {
        font-size: 14px !important;
    }
    .tabla-minimalista thead th {
        font-size: 12px !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fa;
        color: #6c757d;
        border-bottom: 1px solid #dee2e6;
    }
    .tabla-minimalista tbody tr td {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
        border-bottom: 1px solid #efefef;
    }
    .select-minimalista {
        font-size: 13px !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 4px !important;
        border: 1px solid #dcdde1;
        background-color: #fafafa;
        color: #2c3e50;
    }
    .select-minimalista:focus {
        border-color: #3498db;
        box-shadow: none;
    }
    /* Estilos para los botones/tabs de Cursos */
    .nav-botones-cursos .nav-link {
        color: #495057;
        background-color: #fff;
        border: 1px solid #dee2e6;
        padding: 0.5rem 1rem;
        font-size: 13px;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .nav-botones-cursos .nav-link:hover {
        background-color: #f1f3f5;
        border-color: #ced4da;
    }
    .nav-botones-cursos .nav-link.active {
        color: #fff !important;
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
    }
    .contenedor-carrera-bloque {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }
</style>

<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">portrait</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Admisión y Matrícula Académica</h4>
                <p class="text-muted small mb-0">
                    Entorno actual: <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded fw-bold text-uppercase"><?= $sucursal_sesion === 'LP' ? 'La Paz' : 'El Alto'; ?></span>
                    <?php if ($nombre_gestion_activa): ?>
                        &nbsp;·&nbsp; Gestión Sincronizada: <span class="text-success fw-bold"><?= htmlspecialchars($nombre_gestion_activa); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?= $msg; ?>

<div class="row g-4">
    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm position-sticky" style="top: 80px;" id="panel-formulario-estudiante">
            <div class="card-header border-bottom py-3 d-flex align-items-center gap-2 text-white fw-bold transition-all" id="titulo-formulario" style="background-color: #198754; border-top-left-radius:8px; border-top-right-radius:8px;">
                <span class="material-icons">person_add_alt</span>
                <span>Nueva Ficha de Estudiante</span>
            </div>
            <div class="card-body p-4 bg-white">
                <form method="POST" action="estudiantes.php?sucursal=<?= $sucursal_sesion; ?>" id="formEstudiante">
                    <input type="hidden" name="id_estudiante_edit" id="id_estudiante_edit" value="" />

                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold text-muted small">Cédula de Identidad (CI)</label>
                            <input type="text" name="ci" id="form_ci" class="form-control bg-light" placeholder="Ej: 8473621" required maxlength="12" style="border-radius:6px;"/>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold text-muted small">Expedido</label>
                            <select name="expedido" id="form_expedido" class="form-select bg-light" required style="border-radius:6px;">
                                <option value="LP">LP</option><option value="OR">OR</option><option value="CB">CB</option>
                                <option value="SC">SC</option><option value="PT">PT</option><option value="CH">CH</option>
                                <option value="TJ">TJ</option><option value="BE">BE</option><option value="PD">PD</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombres</label>
                        <input type="text" name="nombres" id="form_nombres" class="form-control bg-light" placeholder="Ej: Juan Carlos" required maxlength="25" style="border-radius:6px;"/>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Apellidos</label>
                        <input type="text" name="apellidos" id="form_apellidos" class="form-control bg-light" placeholder="Ej: Perez Mamani" required maxlength="25" style="border-radius:6px;"/>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted small">Teléfono / Celular</label>
                        <input type="text" name="telefono" id="form_telefono" class="form-control bg-light" placeholder="Ej: 71234567" maxlength="8" style="border-radius:6px;"/>
                    </div>

                    <button type="submit" name="create_estudiante" id="btn-submit-formulario" class="btn btn-success w-100 py-2.5 d-flex align-items-center justify-content-center gap-1 fw-semibold shadow-sm" style="border-radius:6px;">
                        <span class="material-icons">save</span> Guardar Ficha en Sede
                    </button>
                    
                    <button type="button" id="btn-cancelar-edicion" class="btn btn-light border w-100 py-2 mt-2 d-flex align-items-center justify-content-center gap-1 small fw-medium text-secondary" style="display:none; border-radius:6px;">
                        <span class="material-icons fs-6">close</span> Cancelar Edición
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        
        <div class="card border-0 shadow-sm border-start border-4 border-danger mb-4 bg-white">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-0">
                <div class="d-flex align-items-center gap-2 text-danger fs-5">
                    <span class="material-icons fs-4">assignment_late</span>
                    <span>Estudiantes Sin Curso Asignado</span>
                </div>
                <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-normal font-monospace fs-6"><?= count($estudiantes_sin_curso); ?></span>
            </div>
            
            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                <table class="table align-middle table-hover mb-0 tabla-minimalista">
                    <thead>
                        <tr>
                            <th>Identificación y Nombre</th>
                            <th class="text-center" style="width:280px;">Asignación de Curso</th>
                            <th class="text-end pe-4" style="width:110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($estudiantes_sin_curso)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted fs-6">
                                    <span class="material-icons fs-2 mb-1 text-opacity-25 text-dark">task_alt</span><br>
                                    Todos los estudiantes cuentan con un aula asignada en esta gestión.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($estudiantes_sin_curso as $e): ?>
                                <tr>
                                    <td>
                                        <div class="text-dark fs-6"><?= htmlspecialchars($e['apellidos'] . " " . $e['nombres']); ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($e['ci'] . ' ' . $e['expedido']); ?></div>
                                    </td>
                                    <td>
                                        <form method="POST" action="estudiantes.php?sucursal=<?= $sucursal_sesion; ?>" class="d-flex gap-1.5 align-items-center m-0">
                                            <input type="hidden" name="id_estudiante" value="<?= $e['id_estudiante']; ?>">
                                            
                                            <select name="id_carrera" class="form-select select-minimalista" onchange="cargarCursosAmigables(<?= $e['id_estudiante']; ?>, this.value)">
                                                <option value="">Carrera</option>
                                                <?php foreach($carreras_tab2 as $ca): ?>
                                                    <option value="<?= $ca['id_carrera']; ?>"><?= htmlspecialchars($ca['nombre']); ?></option>
                                                <?php endforeach; ?>
                                            </select>

                                            <select name="id_curso" id="curso_<?= $e['id_estudiante']; ?>" class="form-select select-minimalista text-primary" style="width:110px;" required>
                                                <option value="">Curso</option>
                                            </select>
                                            
                                            <button type="submit" name="inscribir_estudiante" class="btn btn-light border btn-sm px-2 py-1.5 d-flex align-items-center text-primary" title="Confirmar Inscripción">
                                                <span class="material-icons fs-5">done</span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-3">
                                            <a class="text-secondary btn-editar-link" style="cursor:pointer;" title="Editar Ficha"
                                               data-id="<?= $e['id_estudiante']; ?>"
                                               data-ci="<?= htmlspecialchars($e['ci']); ?>"
                                               data-expedido="<?= htmlspecialchars($e['expedido']); ?>"
                                               data-nombres="<?= htmlspecialchars($e['nombres']); ?>"
                                               data-apellidos="<?= htmlspecialchars($e['apellidos']); ?>"
                                               data-telefono="<?= htmlspecialchars($e['telefono'] ?? ''); ?>">
                                                <span class="material-icons fs-5">edit</span>
                                            </a>
                                            <a class="text-danger opacity-75" style="cursor:pointer;" title="Eliminar Alumno" onclick="eliminarEstudiante(<?= (int)$e['id_estudiante']; ?>, '<?= htmlspecialchars($e['apellidos'] . ' ' . $e['nombres'], ENT_QUOTES); ?>')">
                                                <span class="material-icons fs-5">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-white">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center gap-2 text-dark fs-5">
                    <span class="material-icons text-secondary fs-4">folder_shared</span>
                    <span>Distribución Orgánica por Aulas</span>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if(empty($estructura_carreras)): ?>
                    <div class="text-center py-5 text-muted fs-6">
                        <span class="material-icons fs-1 text-opacity-25 text-dark mb-2">grid_view</span>
                        <p class="mb-0">No se detectaron estudiantes cursando asignaturas para la gestión actual.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($estructura_carreras as $carrera_nombre => $cursos_de_carrera): ?>
                        
                        <div class="contenedor-carrera-bloque p-3 mb-4">
                            <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom text-uppercase text-primary fs-6 fw-semibold">
                                <span class="material-icons fs-5">school</span>
                                <span><?= htmlspecialchars($carrera_nombre); ?></span>
                            </div>

                            <!-- BOTONES / TABS MINIMALISTAS (1A, 1B, 2A...) -->
                            <ul class="nav nav-pills nav-botones-cursos gap-2 mb-3" id="tab-<?= md5($carrera_nombre); ?>" role="tablist">
                                <?php 
                                $es_primero = true;
                                foreach ($cursos_de_carrera as $curso_nombre => $lista_alumnos): 
                                    $id_tab_boton = md5($carrera_nombre . $curso_nombre);
                                ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $es_primero ? 'active' : ''; ?>" 
                                                id="btn-<?= $id_tab_boton; ?>" 
                                                data-bs-toggle="pill" 
                                                data-bs-target="#panel-<?= $id_tab_boton; ?>" 
                                                type="button" role="tab">
                                            <?= htmlspecialchars($curso_nombre); ?>
                                            <span class="badge rounded-pill bg-dark bg-opacity-10 text-dark small ms-1"><?= count($lista_alumnos); ?></span>
                                        </button>
                                    </li>
                                <?php 
                                $es_primero = false;
                                endforeach; 
                                ?>
                            </ul>

                            <div class="tab-content" id="content-<?= md5($carrera_nombre); ?>">
                                <?php 
                                $es_primero_panel = true;
                                foreach ($cursos_de_carrera as $curso_nombre => $lista_alumnos): 
                                    $id_tab_boton = md5($carrera_nombre . $curso_nombre);
                                ?>
                                    <div class="tab-pane fade <?= $es_primero_panel ? 'show active' : ''; ?>" 
                                         id="panel-<?= $id_tab_boton; ?>" 
                                         role="tabpanel">
                                        
                                        <div class="border rounded bg-white overflow-hidden shadow-sm">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0 tabla-minimalista">
                                                    <thead>
                                                        <tr>
                                                            <th class="ps-3" style="width: 150px;">Documento (C.I.)</th>
                                                            <th>Apellidos y Nombres</th>
                                                            <th style="width: 140px;">Teléfono</th>
                                                            <th class="text-end pe-3" style="width: 100px;">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($lista_alumnos as $al): ?>
                                                            <tr>
                                                                <td class="ps-3 font-monospace text-muted small"><?= htmlspecialchars($al['ci'] . " " . $al['expedido']); ?></td>
                                                                <td class="text-dark"><?= htmlspecialchars($al['apellidos'] . " " . $al['nombres']); ?></td>
                                                                <td class="text-muted small"><?= htmlspecialchars($al['telefono'] ?? '—'); ?></td>
                                                                <td class="text-end pe-3">
                                                                    <div class="d-inline-flex gap-3">
                                                                        <a class="text-secondary btn-editar-link" style="cursor:pointer;" title="Editar Ficha"
                                                                           data-id="<?= $al['id_estudiante']; ?>"
                                                                           data-ci="<?= htmlspecialchars($al['ci']); ?>"
                                                                           data-expedido="<?= htmlspecialchars($al['expedido']); ?>"
                                                                           data-nombres="<?= htmlspecialchars($al['nombres']); ?>"
                                                                           data-apellidos="<?= htmlspecialchars($al['apellidos']); ?>"
                                                                           data-telefono="<?= htmlspecialchars($al['telefono'] ?? ''); ?>">
                                                                            <span class="material-icons fs-5">edit</span>
                                                                        </a>
                                                                        <a class="text-danger opacity-75" style="cursor:pointer;" title="Eliminar Permanente" onclick="eliminarEstudiante(<?= (int)$al['id_estudiante']; ?>, '<?= htmlspecialchars($al['apellidos'] . ' ' . $al['nombres'], ENT_QUOTES); ?>')">
                                                                            <span class="material-icons fs-5">delete</span>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                <?php 
                                $es_primero_panel = false;
                                endforeach; 
                                ?>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function cargarCursosAmigables(id_estudiante, id_carrera) {
    let select = $('#curso_' + id_estudiante);
    select.empty().append('<option value="">Curso</option>');
    if(id_carrera == "") return;

    $.ajax({
        url: API_BASE_URL + '/cursos',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            var sucursalActual = '<?= $sucursal_sesion; ?>';
            var idGestionActiva = <?= $id_gestion_activa ?? 'null'; ?>;
        
            if(res && res.data) {
                res.data.forEach(function(c) {
                    var gestionOk = !idGestionActiva || parseInt(c.id_gestion) === idGestionActiva;
                    var sucCur = (c.sucursal_varchar || c.sucursal || '').toUpperCase();
                    
                    if(c.id_carrera == id_carrera && gestionOk && sucCur == sucursalActual.toUpperCase()) {
                        // Obtener número de nivel (1, 2, 3) de id_nivel o del nombre
                        let nivelNum = c.id_nivel || '';
                        if (!nivelNum && c.nivel_nombre) {
                            nivelNum = c.nivel_nombre.match(/\d+/)?.[0] || '';
                        }
                        let label = nivelNum + (c.paralelo || '');
                        select.append('<option value="'+c.id_curso+'">'+label+'</option>');
                    }
                });
            }
        }
    });
}

$(document).on('click', '.btn-editar-link', function() {
    var id        = $(this).data('id');
    var ci        = $(this).data('ci');
    var expedido  = $(this).data('expedido');
    var nombres   = $(this).data('nombres');
    var apellidos = $(this).data('apellidos');
    var telefono  = $(this).data('telefono');

    $('#id_estudiante_edit').val(id);
    $('#form_ci').val(ci);
    $('#form_expedido').val(expedido).trigger('change');
    $('#form_nombres').val(nombres);
    $('#form_apellidos').val(apellidos);
    $('#form_telefono').val(telefono);

    $('#titulo-formulario').html('<span class="material-icons">edit_note</span><span>Editar Ficha de Estudiante</span>').css('background-color', '#0d6efd');
    $('#btn-submit-formulario').html('<span class="material-icons">refresh</span> Actualizar Ficha').removeClass('btn-success').addClass('btn-primary');
    $('#btn-cancelar-edicion').show();

    $('html, body').animate({ scrollTop: $('#panel-formulario-estudiante').offset().top - 80 }, 300);
});

$('#btn-cancelar-edicion').on('click', function() {
    $('#id_estudiante_edit').val('');
    $('#form_ci').val('');
    $('#form_nombres').val('');
    $('#form_apellidos').val('');
    $('#form_telefono').val('');
    $('#form_expedido').val('LP');

    $('#titulo-formulario').html('<span class="material-icons">person_add_alt</span><span>Nueva Ficha de Estudiante</span>').css('background-color', '#198754');
    $('#btn-submit-formulario').html('<span class="material-icons">save</span> Guardar Ficha en Sede').removeClass('btn-primary').addClass('btn-success');
    $(this).hide();
});

function eliminarEstudiante(id, nombreCompleto) {
    Swal.fire({
        title: '¿Eliminar Estudiante?',
        text: `¿Estás completamente seguro de eliminar permanentemente a "${nombreCompleto}"? Se perderá su historial de notas e inscripciones asociadas.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `estudiantes.php?sucursal=<?= $sucursal_sesion; ?>&delete_id=${id}`;
        }
    });
}

$(document).ready(function() {
    $('#form_ci').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 12) {
            this.value = this.value.slice(0, 12);
        }
    });

    $('#form_nombres').on('input', function() {
        this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (this.value.length > 25) {
            this.value = this.value.slice(0, 25);
        }
        // Capitalizar cada palabra
        this.value = this.value.toLowerCase().replace(/\b\w/g, function(letra) {
            return letra.toUpperCase();
        });
    });

    $('#form_apellidos').on('input', function() {
        this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (this.value.length > 25) {
            this.value = this.value.slice(0, 25);
        }
        // Capitalizar cada palabra
        this.value = this.value.toLowerCase().replace(/\b\w/g, function(letra) {
            return letra.toUpperCase();
        });
    });

    $('#form_telefono').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 8) {
            this.value = this.value.slice(0, 8);
        }
    });

    // Validación antes de enviar el formulario
    $('#formEstudiante').on('submit', function(e) {
        let valid = true;
        let mensajes = [];

        // Validar CI
        const ci = $('#form_ci').val();
        if (ci.length < 6) {
            valid = false;
            mensajes.push('El CI debe tener al menos 6 dígitos.');
        }
        if (!/^[0-9]+$/.test(ci)) {
            valid = false;
            mensajes.push('El CI solo puede contener números.');
        }

        // Validar Nombres
        const nombres = $('#form_nombres').val();
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(nombres)) {
            valid = false;
            mensajes.push('Los nombres solo pueden contener letras y espacios.');
        }
        if (nombres.length < 2) {
            valid = false;
            mensajes.push('Los nombres deben tener al menos 2 caracteres.');
        }

        const apellidos = $('#form_apellidos').val();
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(apellidos)) {
            valid = false;
            mensajes.push('Los apellidos solo pueden contener letras y espacios.');
        }
        if (apellidos.length < 2) {
            valid = false;
            mensajes.push('Los apellidos deben tener al menos 2 caracteres.');
        }

        const telefono = $('#form_telefono').val();
        if (telefono && telefono.length > 0 && !/^[0-9]+$/.test(telefono)) {
            valid = false;
            mensajes.push('El teléfono solo puede contener números.');
        }
        if (telefono && telefono.length > 0 && telefono.length < 7) {
            valid = false;
            mensajes.push('El teléfono debe tener al menos 7 dígitos.');
        }

        if (!valid) {
            e.preventDefault();
            Swal.fire('Error de Validación', mensajes.join('\n'), 'error');
            return false;
        }
    });
});
</script>

<?php layout_foot(); ?>