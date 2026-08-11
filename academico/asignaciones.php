<?php
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php"); 
    exit();
}
require_once __DIR__ . '/config_api.php';
$sucursal_admin = resolver_sucursal($_SESSION['role_id']);
$msg = '';
$api_asignaciones = API_BASE_URL . "/asignaciones";
$api_cursos       = API_BASE_URL . "/cursos";
$api_usuarios     = API_BASE_URL . "/auth/users";

function ok(string $t): string {
    return '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm p-3 mb-4 rounded-3" role="alert">
                <div class="d-flex align-items-center"><span class="material-icons me-2">check_circle</span><div>' . $t . '</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}
function err(string $t): string {
    return '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm p-3 mb-4 rounded-3" role="alert">
                <div class="d-flex align-items-center"><span class="material-icons me-2">error</span><div>' . $t . '</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

if (isset($_POST['create_asignacion'])) {
    $payload = [
        'id_curso'   => (int)$_POST['id_curso'],
        'id_materia' => (int)$_POST['id_materia'],
        'id_docente' => (int)$_POST['id_docente']
    ];
    $opts = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'POST', 'content' => json_encode($payload), 'ignore_errors' => true]];
    $res = json_decode(@file_get_contents($api_asignaciones, false, stream_context_create($opts)), true);
    if ($res && $res['status'] === 'success') {
        $msg = ok('Docente asignado correctamente a la materia especificada.');
    } else {
        $msg = err($res['message'] ?? 'No se pudo procesar la asignación en el servidor.');
    }
}

// ── Crear docente rápido desde esta pantalla ────────────────────────────────
if (isset($_POST['create_docente'])) {
    $ci = trim($_POST['carnet_ci']);
    $email_prefix = strtolower(trim($_POST['email_prefix']));
    $email = $email_prefix . '@gmail.com';

    $payload = [
        'username' => $ci,
        'password' => $ci,
        'name'     => trim($_POST['name']),
        'emailid'  => $email,
        'sucursal' => $sucursal_admin,
        'role_id'  => 3
    ];
    $opts = ['http' => [
        'header'        => "Content-Type: application/json\r\n",
        'method'        => 'POST',
        'content'       => json_encode($payload),
        'ignore_errors' => true
    ]];
    $res = json_decode(@file_get_contents($api_usuarios, false, stream_context_create($opts)), true);
    if ($res && $res['status'] === 'success') {
        $msg = ok('Docente registrado con éxito. Su contraseña inicial es su C.I.: <b>' . htmlspecialchars($ci) . '</b>');
    } else {
        $msg = err($res['message'] ?? 'No se pudo registrar al docente.');
    }
}

if (isset($_GET['delete_id'])) {
    $opts = ['http' => ['method' => 'DELETE', 'ignore_errors' => true]];
    $res = json_decode(@file_get_contents($api_asignaciones . "/" . (int)$_GET['delete_id'], false, stream_context_create($opts)), true);
    if ($res && $res['status'] === 'success') {
        $msg = ok('Asignación docente removida con éxito de esta materia.');
    } else {
        $msg = err('No se pudo quitar la asignación docente especificada.');
    }
}

// CARGA DE DATOS
$cursos_todos = json_decode(@file_get_contents($api_cursos), true)['data'] ?? [];
$usuarios     = json_decode(@file_get_contents($api_usuarios), true)['data'] ?? [];
$carreras_api = json_decode(@file_get_contents(API_BASE_URL . "/carreras"), true)['data'] ?? [];
$gestiones_api         = json_decode(@file_get_contents(API_BASE_URL . "/gestion"), true)['data'] ?? [];
$ids_gestion_activa    = [];
$nombre_gestion_activa = '';
foreach ($gestiones_api as $g) {
    if ((int)$g['estado_bt'] === 1) {
        $ids_gestion_activa[] = (int)$g['id_gestion'];
        if (strtoupper(trim($g['sucursal_varchar'])) === $sucursal_admin) {
            $nombre_gestion_activa = $g['gestion_varchar'];
        }
    }
}

// Carreras de la sucursal (solo activas)
$carreras_sucursal = array_values(array_filter($carreras_api, function($ca) use ($sucursal_admin) {
    $activa = (int)($ca['estado'] ?? 1) === 1;
    $suc = strtoupper(trim($ca['sucursal_varchar'] ?? $ca['sucursal'] ?? ''));
    return $activa && $suc === $sucursal_admin;
}));

// IDs de carreras activas, para excluir cursos de carreras deshabilitadas
$ids_carreras_activas = array_column($carreras_sucursal, 'id_carrera');

// Cursos filtrados: activos + gestión activa + sucursal + carrera activa
$cursos = array_values(array_filter($cursos_todos, function($c) use ($ids_gestion_activa, $sucursal_admin, $ids_carreras_activas) {
    if ((int)$c['estado'] !== 1) return false;
    $suc = strtoupper(trim($c['sucursal_varchar'] ?? $c['sucursal'] ?? ''));
    if ($suc !== $sucursal_admin) return false;
    if (!in_array((int)($c['id_carrera'] ?? 0), $ids_carreras_activas)) return false;
    if (empty($ids_gestion_activa)) return true;
    return in_array((int)($c['id_gestion'] ?? 0), $ids_gestion_activa);
}));

// Cursos agrupados por carrera para JS (formulario)
$cursos_por_carrera = [];
foreach ($cursos as $c) {
    $cursos_por_carrera[(int)$c['id_carrera']][] = [
        'id_curso'    => $c['id_curso'],
        'id_nivel'    => (int)$c['id_nivel'],
        'nivel_nombre'=> $c['nivel_nombre'] ?? '',
        'paralelo'    => $c['paralelo'],
    ];
}

// Docentes
$docentes = array_filter($usuarios, fn($u) => (int)$u['role_id'] === 3);

// Todas las asignaciones de la gestión activa
$asignaciones_todas = json_decode(@file_get_contents($api_asignaciones), true)['data'] ?? [];
$asignaciones = array_values(array_filter($asignaciones_todas, function($a) use ($ids_gestion_activa, $sucursal_admin) {
    if (!empty($ids_gestion_activa) && isset($a['id_gestion'])) {
        if (!in_array((int)$a['id_gestion'], $ids_gestion_activa)) return false;
    }
    if (isset($a['sucursal_varchar'])) {
        return strtoupper(trim($a['sucursal_varchar'])) === $sucursal_admin;
    }
    return true;
}));

// ── Construir estructura: carrera → curso → [materias con docente] ─────────────
$asig_idx = []; // [id_curso][id_materia] => asignacion
foreach ($asignaciones as $a) {
    $asig_idx[(int)$a['id_curso']][(int)$a['id_materia']] = $a;
}

// Agrupar cursos por carrera (para pestañas)
$cursos_por_carrera_tab = []; // [id_carrera] => ['nombre'=>..., 'cursos'=>[...]]
foreach ($cursos as $c) {
    $id_ca = (int)$c['id_carrera'];
    if (!isset($cursos_por_carrera_tab[$id_ca])) {
        $nombre_ca = $c['carrera_nombre'] ?? '';
        if (!$nombre_ca) {
            foreach ($carreras_sucursal as $ca) {
                if ((int)$ca['id_carrera'] === $id_ca) { $nombre_ca = $ca['nombre']; break; }
            }
        }
        $cursos_por_carrera_tab[$id_ca] = ['nombre' => $nombre_ca, 'cursos' => []];
    }
    $cursos_por_carrera_tab[$id_ca]['cursos'][] = $c;
}

// Ordenar cursos dentro de cada carrera por nivel y paralelo
foreach ($cursos_por_carrera_tab as &$caData) {
    usort($caData['cursos'], fn($a,$b) =>
        $a['id_nivel'] !== $b['id_nivel']
            ? $a['id_nivel'] <=> $b['id_nivel']
            : strcmp($a['paralelo'], $b['paralelo'])
    );
}
unset($caData);

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Asignación Docente', $current_page, $_SESSION['role_id']);
?>
<style>
/* Pestañas de carrera — mismo estilo que vista_cursos.php */
.nav-tabs-carreras { border-bottom: 2px solid var(--rojo-elegante, #8d191d); }
.nav-tabs-carreras .nav-link {
    color: var(--texto-oscuro, #2d3436); font-weight: 600;
    border: 1px solid #e3e6f0; border-bottom: none;
    background-color: #fff; margin-right: 4px; transition: all 0.2s ease;
    border-radius: 6px 6px 0 0;
}
.nav-tabs-carreras .nav-link:hover { background-color: #f8f9fc; }
.nav-tabs-carreras .nav-link.active {
    background-color: var(--rojo-elegante, #8d191d) !important;
    color: #fff !important; border-color: var(--rojo-elegante, #8d191d) !important;
}
/* Pestañas de curso (paralelos) */
.nav-tabs-cursos { border-bottom: 2px solid #ffc107; margin-bottom: 16px; flex-wrap: wrap; }
.nav-tabs-cursos .nav-link {
    color: #495057; font-weight: 700; font-family: monospace; font-size: 15px;
    border: 1px solid #dee2e6; border-bottom: none;
    background: #fff; margin-right: 3px; min-width: 52px; text-align: center;
    border-radius: 6px 6px 0 0; transition: all 0.15s;
}
.nav-tabs-cursos .nav-link:hover { background: #fff9e6; border-color: #ffc107; color: #856404; }
.nav-tabs-cursos .nav-link.active {
    background: #ffc107 !important; color: #212529 !important;
    border-color: #ffc107 !important; font-weight: 900;
}
/* Filas sin docente destacadas */
.fila-sin-docente { background-color: #fff8f8 !important; }
.fila-sin-docente td:first-child { border-left: 4px solid #dc3545; }

/* Estilo destacado y llamativo para el botón de nuevo docente */
.btn-crear-docente-destacado {
    background-color: #198754;
    color: #fff !important;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    box-shadow: 0 4px 6px rgba(25, 135, 84, 0.2);
    transition: all 0.2s ease-in-out;
}
.btn-crear-docente-destacado:hover {
    background-color: #146c43;
    transform: translateY(-1px);
    box-shadow: 0 6px 10px rgba(25, 135, 84, 0.3);
}

/* ── NUEVO DISEÑO: El contenedor simula ser el input de Bootstrap ── */
.fake-input-container {
    display: flex;
    align-items: center;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 0.375rem 0.75rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    cursor: text;
}
/* Efecto Focus de Bootstrap replicado al contenedor */
.fake-input-container:focus-within {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
/* El input real no tiene bordes ni sombras y se autoajusta */
.fake-input-container input {
    border: none !important;
    outline: none !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    width: 60px; /* Tamaño mínimo inicial */
    min-width: 10px;
    color: #212529;
}
/* El sufijo pegado, bien visible y del color del texto estándar */
.fake-input-sufijo {
    color: #212529; /* Mismo color de letra que el input */
    font-weight: 500;
    margin-left: 2px;
    user-select: none;
    white-space: nowrap;
}
</style>

<!-- HEADER -->
<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color:var(--gris-suave);color:var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">assignment_ind</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--texto-oscuro);">Asignación de Docentes a Materias</h4>
                <p class="text-muted small mb-0">
                    Sede: <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded fw-bold text-uppercase"><?= $sucursal_admin==='LP' ? 'La Paz' : 'El Alto'; ?></span>
                    <?php if ($nombre_gestion_activa): ?>
                        &nbsp;·&nbsp; Gestión Sincronizada: <span class="text-success fw-bold"><?= htmlspecialchars($nombre_gestion_activa); ?></span>
                    <?php else: ?>
                        &nbsp;·&nbsp; <span class="text-danger fw-bold">Sin gestión activa en esta sede</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?= $msg; ?>

<div class="row g-4">
    <!-- ══ FORMULARIO NUEVA ASIGNACIÓN ══ -->
    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm position-sticky" style="top:80px;">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-2 text-success fw-bold">
                <span class="material-icons">add_task</span> Nueva Vinculación Docente
            </div>
            <div class="card-body p-4">
                <?php if (empty($cursos)): ?>
                    <div class="text-center py-4 text-muted small">
                        <span class="material-icons fs-2 text-warning mb-2 d-block">error_outline</span>
                        No hay cursos activos en la gestión actual para esta sede.
                    </div>
                <?php else: ?>
                <form method="POST" action="asignaciones.php?sucursal=<?= $sucursal_admin; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">1. Carrera Profesional</label>
                        <select class="form-select" id="select_carrera" name="id_carrera_ui" style="border-radius:8px;">
                            <option value="">-- Seleccionar Carrera --</option>
                            <?php foreach ($carreras_sucursal as $ca): ?>
                                <option value="<?= $ca['id_carrera']; ?>"><?= htmlspecialchars($ca['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">2. Curso / Aula</label>
                        <select class="form-select font-monospace fw-bold text-primary" name="id_curso" id="select_curso" style="border-radius:8px;" required disabled>
                            <option value="">-- Elige una carrera primero --</option>
                        </select>
                        <div class="form-text small text-muted">Solo cursos de la gestión en vigencia.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">3. Materia del Plan de Estudios</label>
                        <select class="form-select" name="id_materia" id="select_materia" style="border-radius:8px;" required disabled>
                            <option value="">-- Elige un curso primero --</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold text-muted small mb-0">4. Docente Responsable</label>
                            <button type="button" class="btn btn-crear-docente-destacado border-0 d-flex align-items-center gap-1"
                                    data-bs-toggle="modal" data-bs-target="#modalCrearDocente">
                                <span class="material-icons" style="font-size:15px;">person_add</span> Registrar Nuevo
                            </button>
                        </div>
                        <select class="form-select" name="id_docente" id="select_docente" style="border-radius:8px;" required>
                            <option value="">-- Seleccionar Docente --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="create_asignacion" class="btn btn-success w-100 py-2 d-flex align-items-center justify-content-center gap-1 fw-semibold" style="border-radius:8px;">
                        <span class="material-icons">save</span> Guardar Asignación
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ══ DISTRIBUCIÓN DE MATERIAS Y DOCENTES (PESTAÑAS) ══ -->
    <div class="col-xl-8 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 fw-bold text-dark">
                    <span class="material-icons text-secondary">view_list</span>
                    <span>Distribución de Materias y Docentes</span>
                </div>
                <?php if ($nombre_gestion_activa): ?>
                    <span class="badge bg-warning text-dark px-3 py-1 rounded fw-bold font-monospace">
                        <span class="material-icons fs-6 me-1" style="vertical-align:middle;">sync</span>
                        Gestión Sincronizada: <?= htmlspecialchars($nombre_gestion_activa); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-3">
            <?php if (empty($cursos_por_carrera_tab)): ?>
                <div class="text-center py-5 text-muted small">
                    <span class="material-icons fs-2 d-block mb-2 text-black-50">rule_folder</span>
                    No hay cursos activos en la gestión actual para esta sede.
                </div>
            <?php else: ?>
                <!-- PESTAÑAS DE CARRERA -->
                <ul class="nav nav-tabs-carreras mb-0" id="tabs-carrera-asig" style="border-bottom:2px solid var(--rojo-elegante,#8d191d);">
                    <?php $firstCa = true; foreach ($cursos_por_carrera_tab as $id_ca => $caData): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $firstCa ? 'active' : ''; ?>"
                           href="#"
                           data-id-carrera="<?= $id_ca; ?>"
                           data-bs-tab="carrera">
                            <span class="material-icons fs-6 me-1" style="vertical-align:middle;">school</span>
                            <?= htmlspecialchars($caData['nombre']); ?>
                        </a>
                    </li>
                    <?php $firstCa = false; endforeach; ?>
                </ul>
                <!-- CONTENIDO POR CARRERA -->
                <?php $firstCa = true; foreach ($cursos_por_carrera_tab as $id_ca => $caData): ?>
                <div class="tab-carrera-content <?= $firstCa ? '' : 'd-none'; ?>" data-carrera="<?= $id_ca; ?>">
                    <!-- PESTAÑAS DE CURSO (paralelos) -->
                    <ul class="nav nav-tabs-cursos mt-3" id="tabs-curso-<?= $id_ca; ?>">
                        <?php $firstCu = true; foreach ($caData['cursos'] as $cu): 
                            $label = $cu['id_nivel'] . $cu['paralelo']; 
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $firstCu ? 'active' : ''; ?>"
                               href="#"
                               data-id-curso="<?= $cu['id_curso']; ?>"
                               data-carrera="<?= $id_ca; ?>"
                               data-bs-tab="curso">
                                <?= htmlspecialchars($label); ?>
                            </a>
                        </li>
                        <?php $firstCu = false; endforeach; ?>
                    </ul>
                    <!-- PANELES DE MATERIAS POR CURSO -->
                    <?php $firstCu = true; foreach ($caData['cursos'] as $cu): 
                        $id_curso_tab = (int)$cu['id_curso'];
                        $label = $cu['id_nivel'] . $cu['paralelo'];
                        $materias_plan_raw = json_decode(@file_get_contents(
                            API_BASE_URL . "/asignaciones/curso/{$id_curso_tab}"
                        ), true)['data'] ?? [];
                        
                        $sin_docente = [];
                        $con_docente = [];
                        foreach ($materias_plan_raw as $mp) {
                            $id_mat = (int)$mp['id_materia'];
                            if (isset($asig_idx[$id_curso_tab][$id_mat])) {
                                $a = $asig_idx[$id_curso_tab][$id_mat];
                                $con_docente[] = array_merge($mp, ['docente_nombre' => $a['docente_nombre'], 'id_asignacion' => $a['id_asignacion']]);
                            } else {
                                $sin_docente[] = $mp;
                            }
                        }
                        $materias_ordenadas = array_merge($sin_docente, $con_docente);
                    ?>
                    <div class="tab-curso-content <?= $firstCu ? '' : 'd-none'; ?>"
                         data-curso="<?= $id_curso_tab; ?>"
                         data-carrera="<?= $id_ca; ?>">
                        <?php if (empty($materias_ordenadas)): ?>
                            <div class="text-center py-4 text-muted small">
                                <span class="material-icons fs-2 d-block mb-1 text-black-50">auto_stories</span>
                                No hay materias en el plan de estudios para este curso.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0" style="font-size:14px;">
                                <thead class="table-light text-muted" style="font-size:12px;">
                                    <tr>
                                        <th class="ps-3" style="width:100px;">Sigla</th>
                                        <th>Materia / Asignatura</th>
                                        <th>Docente Asignado</th>
                                        <th class="text-end pe-3" style="width:110px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($materias_ordenadas as $mp): 
                                    $id_mat = (int)$mp['id_materia'];
                                    $tiene_docente = isset($mp['docente_nombre']);
                                    $id_asig = $mp['id_asignacion'] ?? null;
                                ?>
                                <tr class="<?= !$tiene_docente ? 'fila-sin-docente' : ''; ?>">
                                    <td class="ps-3">
                                        <span class="badge bg-light text-dark border px-2 py-1 fw-bold font-monospace">
                                            <?= htmlspecialchars($mp['sigla'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($mp['materia_nombre'] ?? $mp['nombre'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($tiene_docente): ?>
                                            <div class="d-flex align-items-center gap-1 text-secondary small">
                                                <span class="material-icons fs-6 text-success">person</span>
                                                <span class="fw-medium"><?= htmlspecialchars($mp['docente_nombre']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="material-icons fs-6 text-danger">person_off</span>
                                                <span class="text-danger fw-semibold small">Sin docente asignado</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($tiene_docente && $id_asig): ?>
                                            <button class="btn btn-outline-danger btn-sm px-2 d-inline-flex align-items-center gap-1"
                                                    style="border-radius:6px; font-size:12px;"
                                                    onclick="quitarAsignacion(<?= (int)$id_asig; ?>, '<?= htmlspecialchars($mp['docente_nombre'], ENT_QUOTES); ?>', '<?= htmlspecialchars($mp['materia_nombre'] ?? $mp['nombre'] ?? '', ENT_QUOTES); ?>')">
                                                <span class="material-icons fs-6">person_remove</span> Quitar
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Resumen rápido del curso -->
                        <?php 
                        $total = count($materias_ordenadas);
                        $sin   = count($sin_docente);
                        $con   = count($con_docente);
                        ?>
                        <div class="d-flex gap-3 px-3 py-2 border-top bg-light" style="font-size:12px;">
                            <span class="text-muted">Total: <b><?= $total; ?></b></span>
                            <span class="text-success">Con docente: <b><?= $con; ?></b></span>
                            <?php if ($sin > 0): ?>
                                <span class="text-danger fw-bold">
                                    <span class="material-icons fs-6" style="vertical-align:middle;">warning</span>
                                    Sin docente: <b><?= $sin; ?></b>
                                </span>
                            <?php else: ?>
                                <span class="text-success">
                                    <span class="material-icons fs-6" style="vertical-align:middle;">check_circle</span>
                                    Completado
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php $firstCu = false; endforeach; ?>
                </div>
                <?php $firstCa = false; endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: REGISTRAR NUEVO DOCENTE ══ -->
<div class="modal fade" id="modalCrearDocente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="asignaciones.php?sucursal=<?= $sucursal_admin; ?>" id="formCrearDocente">
                <div class="modal-header text-white border-0" style="background-color:var(--rojo-elegante);">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <span class="material-icons me-2">person_add</span> Registrar Nuevo Docente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control p-2.5" style="border-radius:8px;"
                               placeholder="Ej: Juan Pérez Mamani" required maxlength="45">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Número de C.I. <span class="text-danger">*</span></label>
                        <input type="text" name="carnet_ci" class="form-control p-2.5" style="border-radius:8px;"
                               placeholder="Ej: 8439201" required maxlength="12">
                        <div class="form-text text-muted mt-1" style="font-size:0.75rem;">
                            <span class="material-icons me-1 text-warning" style="font-size:0.95rem;">info</span>
                            El C.I. será su usuario y contraseña inicial.
                        </div>
                    </div>
                    
                    <!-- CAMPO DE CORREO RE-DISEÑADO DINÁMICO PEGADO -->
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-muted small">Correo electrónico <span class="text-danger">*</span></label>
                        <div class="fake-input-container" onclick="document.getElementById('docente_email_prefix').focus()">
                            <input type="text" id="docente_email_prefix" placeholder="ejemplo" required maxlength="30">
                            <span class="fake-input-sufijo" id="sufijo_gmail">@gmail.com</span>
                            <input type="hidden" name="email_prefix" id="email_prefix_hidden">
                        </div>
                    </div>
                    <div class="form-text text-muted mt-2" style="font-size:0.75rem;">
                        <span class="material-icons me-1" style="font-size:0.95rem;">apartment</span>
                        Se registrará en la sucursal <b><?= $sucursal_admin === 'LP' ? 'La Paz' : 'El Alto'; ?></b>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="create_docente" class="btn btn-success px-4" style="border-radius:8px;">Registrar Docente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var CURSOS_POR_CARRERA = <?= json_encode($cursos_por_carrera); ?>;
$(document).ready(function() {
    // ── Pestañas de CARRERA ───────────────────────────────────────────────────
    $(document).on('click', '[data-bs-tab="carrera"]', function(e) {
        e.preventDefault();
        var idCa = $(this).data('id-carrera');
        $('#tabs-carrera-asig .nav-link').removeClass('active');
        $(this).addClass('active');
        $('.tab-carrera-content').addClass('d-none');
        $('.tab-carrera-content[data-carrera="' + idCa + '"]').removeClass('d-none');
    });
    // ── Pestañas de CURSO ─────────────────────────────────────────────────────
    $(document).on('click', '[data-bs-tab="curso"]', function(e) {
        e.preventDefault();
        var idCu = $(this).data('id-curso');
        var idCa = $(this).data('carrera');
        $('#tabs-curso-' + idCa + ' .nav-link').removeClass('active');
        $(this).addClass('active');
        $('.tab-curso-content[data-carrera="' + idCa + '"]').addClass('d-none');
        $('.tab-curso-content[data-curso="' + idCu + '"][data-carrera="' + idCa + '"]').removeClass('d-none');
    });
    // ── Formulario: Carrera → Curso ───────────────────────────────────────────
    $('#select_carrera').change(function() {
        var idCarrera = parseInt($(this).val());
        var $selCurso = $('#select_curso');
        var $selMat   = $('#select_materia');
        $selMat.html('<option value="">-- Elige un curso primero --</option>').attr('disabled', true);
        if (!idCarrera) {
            $selCurso.html('<option value="">-- Elige una carrera primero --</option>').attr('disabled', true);
            return;
        }
        var lista = CURSOS_POR_CARRERA[idCarrera] || [];
        if (lista.length === 0) {
            $selCurso.html('<option value="">Sin cursos activos para esta carrera</option>').attr('disabled', true);
            return;
        }
        lista.sort(function(a,b) {
            return a.id_nivel !== b.id_nivel ? a.id_nivel - b.id_nivel : a.paralelo.localeCompare(b.paralelo);
        });
        var opts = '<option value="">-- Seleccionar Curso --</option>';
        lista.forEach(function(c) {
            opts += '<option value="' + c.id_curso + '">' + c.id_nivel + '° Año — Paralelo ' + c.paralelo + '</option>';
        });
        $selCurso.html(opts).attr('disabled', false);
    });
    // ── Formulario: Curso → Materias ─────────────────────────────────────────
    $('#select_curso').change(function() {
        var idCurso = $(this).val();
        var $selMat = $('#select_materia');
        if (!idCurso) {
            $selMat.html('<option value="">-- Elige un curso primero --</option>').attr('disabled', true);
            return;
        }
        $selMat.html('<option value="">Cargando materias...</option>').attr('disabled', true);
        $.ajax({
            url: API_BASE_URL + '/asignaciones/curso/' + idCurso,
            type: 'GET', dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data.length > 0) {
                    var options = '<option value="">-- Seleccionar Materia --</option>';
                    response.data.forEach(function(mat) {
                        options += '<option value="' + mat.id_materia + '">' + mat.sigla + ' - ' + mat.materia_nombre + '</option>';
                    });
                    $selMat.html(options).attr('disabled', false);
                } else {
                    $selMat.html('<option value="">⚠️ Sin materias en este plan</option>').attr('disabled', true);
                }
            },
            error: function() {
                $selMat.html('<option value="">❌ Error al cargar materias</option>').attr('disabled', true);
            }
        });
    });

    // ── LÓGICA DE AUTO-AJUSTE PARA EL SUFIJO PEGADO DEL DOCENTE ─────────────
    const docEmailInput = document.getElementById('docente_email_prefix');
    
    function ajustarAnchoDocenteInput() {
        const tempSpan = document.createElement('span');
        tempSpan.style.visibility = 'hidden';
        tempSpan.style.position = 'absolute';
        tempSpan.style.whiteSpace = 'pre';
        tempSpan.style.font = window.getComputedStyle(docEmailInput).font;
        tempSpan.textContent = docEmailInput.value || docEmailInput.placeholder;
        document.body.appendChild(tempSpan);
        
        docEmailInput.style.width = (tempSpan.getBoundingClientRect().width + 4) + 'px';
        document.body.removeChild(tempSpan);
    }

    docEmailInput.addEventListener('input', ajustarAnchoDocenteInput);
    ajustarAnchoDocenteInput(); // Inicialización
});

function quitarAsignacion(id, docente, materia) {
    Swal.fire({
        title: '¿Remover Docente?',
        text: `¿Estás seguro de quitar a "${docente}" de la materia "${materia}"? Perderá el acceso al registro de notas de este grupo.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, remover',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `asignaciones.php?sucursal=<?= $sucursal_admin; ?>&delete_id=${id}`;
        }
    });
}

// ── Validaciones en tiempo real (modal de registro de docente) ─────────────
document.addEventListener('input', function(e) {
    const el = e.target;
    if (el.name === 'name' && el.form && el.form.id === 'formCrearDocente') {
        el.value = el.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '');
        el.value = el.value.replace(/\b\w/g, l => l.toUpperCase());
    }
    if (el.name === 'carnet_ci' && el.form && el.form.id === 'formCrearDocente') {
        el.value = el.value.replace(/[^0-9]/g, '').slice(0, 12);
    }
    if (el.id === 'docente_email_prefix') {
        el.value = el.value.toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 30);
    }
});

// ── Validación antes de enviar el formulario de docente ─────────────────────
document.getElementById('formCrearDocente').addEventListener('submit', function(e) {
    const prefix = document.getElementById('docente_email_prefix');
    if (prefix.value.trim().length < 1) {
        e.preventDefault();
        Swal.fire('Error', 'El prefijo del correo no puede estar vacío.', 'error');
    } else {
        document.getElementById('email_prefix_hidden').value = prefix.value.trim();
    }
});
</script>
<?php layout_foot(); ?>