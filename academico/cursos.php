<?php
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php"); 
    exit();
}

require_once __DIR__ . '/config_api.php';

$msg = '';
$api_cursos   = API_BASE_URL . "/cursos";
$api_carreras = API_BASE_URL . "/carreras";
$api_gestion  = API_BASE_URL . "/gestion"; 

// Control de sucursal (centralizado: Admin y Secretaria pueden cambiarla desde la cabecera)
$sucursal_usuario = resolver_sucursal($_SESSION['role_id']);

// Mensajes adaptados con contenedores estilizados Bootstrap 5
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

if (isset($_POST['create_curso'])) {
    $payload = [
        'id_carrera' => (int)$_POST['id_carrera'],
        'id_nivel'   => (int)$_POST['id_nivel'],
        'id_gestion' => (int)$_POST['id_gestion'],
        'paralelo'   => strtoupper(trim($_POST['paralelo']))
    ];
    $opts = ['http' => ['header'=>"Content-Type: application/json\r\n",'method'=>'POST','content'=>json_encode($payload),'ignore_errors'=>true]];
    $res = json_decode(@file_get_contents($api_cursos, false, stream_context_create($opts)), true);
    if ($res && $res['status'] === 'success') {
        $msg = ok($res['message'] ?? 'Curso y paralelo habilitados con éxito.');
    } else {
        $msg = err($res['message'] ?? 'Error de conexión con el servidor central al crear el curso.');
    }
}

if (isset($_POST['update_curso'])) {
    $id_curso = (int)$_POST['id_curso'];
    $payload = [
        'id_carrera' => (int)$_POST['id_carrera'],
        'id_nivel'   => (int)$_POST['id_nivel'],
        'id_gestion' => (int)$_POST['id_gestion'],
        'paralelo'   => strtoupper(trim($_POST['paralelo'])),
        'estado'     => (int)$_POST['estado']
    ];
    $opts = ['http' => ['header'=>"Content-Type: application/json\r\n",'method'=>'PUT','content'=>json_encode($payload),'ignore_errors'=>true]];
    @file_get_contents($api_cursos . "/" . $id_curso, false, stream_context_create($opts));
    header("Location: cursos.php" . ($_SESSION['role_id'] === 1 ? "?sucursal=" . $sucursal_usuario : ""));
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id_curso = (int)$_GET['id'];
    $payload = ['estado_actual' => (int)$_GET['est']];
    $opts = ['http' => ['header'=>"Content-Type: application/json\r\n",'method'=>'PUT','content'=>json_encode($payload),'ignore_errors'=>true]];
    @file_get_contents($api_cursos . "/toggle/" . $id_curso, false, stream_context_create($opts));
    header("Location: cursos.php" . ($_SESSION['role_id'] === 1 ? "?sucursal=" . $sucursal_usuario : ""));
    exit();
}

$cursos_todos   = json_decode(@file_get_contents($api_cursos), true)['data'] ?? [];
$carreras_todas = json_decode(@file_get_contents($api_carreras), true)['data'] ?? [];
$gestiones_raw  = @file_get_contents($api_gestion);
$todas_gestiones = $gestiones_raw ? (json_decode($gestiones_raw, true)['data'] ?? []) : [];

// 1. EXTRAER LA GESTIÓN ACACTIVA DE LA SUCURSAL SELECCIONADA
$id_gestion_activa_actual = null;
$gestiones_activas = array_filter($todas_gestiones, function($g) use ($sucursal_usuario, &$id_gestion_activa_actual) {
    $activa = (int)($g['estado_bt'] ?? $g['estado'] ?? 0) === 1;
    if (!$activa) return false;
    $suc_gestion = $g['sucursal_varchar'] ?? $g['sucursal'] ?? '';
    if (strtoupper(trim($suc_gestion)) === $sucursal_usuario) {
        $id_gestion_activa_actual = (int)$g['id_gestion']; // Capturamos la ID única activa
        return true;
    }
    return false;
});

// 2. FILTRAR CARRERAS POR SUCURSAL
$carreras = array_values(array_filter($carreras_todas, function($c) use ($sucursal_usuario) {
    if ((int)($c['estado'] ?? 1) !== 1) return false;
    $suc_carrera = $c['sucursal'] ?? $c['sucursal_varchar'] ?? '';
    return strtoupper(trim($suc_carrera)) === $sucursal_usuario;
}));

// 3. NUEVO FILTRO CRÍTICO: SOLO CURSOS DE LA SUCURSAL SELECCIONADA Y QUE PERTENEZCAN A LA GESTIÓN ACTIVA ACTUAL
$cursos = array_values(array_filter($cursos_todos, function($cu) use ($sucursal_usuario, $id_gestion_activa_actual) {
    $pertenece_sucursal = strtoupper(trim($cu['sucursal_varchar'] ?? $cu['sucursal'] ?? '')) === $sucursal_usuario;
    $pertenece_gestion_activa = (int)$cu['id_gestion'] === $id_gestion_activa_actual;
    
    return $pertenece_sucursal && $pertenece_gestion_activa;
}));

if (empty($gestiones_activas)) {
    $msg = '<div class="alert alert-warning border-0 shadow-sm p-3 mb-4 rounded-3 d-flex align-items-center" role="alert">
                <span class="material-icons me-2">warning</span>
                <div><b>Atención:</b> No hay ninguna gestión académica activa para la sucursal actual. Puedes habilitar una en el <a href="gestiones.php" class="alert-link fw-bold">Control de Gestiones</a>.</div>
            </div>';
}

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Cursos y Aulas', $current_page, $_SESSION['role_id']);
?>

<style>
    /* Estilos de pestañas clonados de vista_cursos.php */
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
</style>

<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">sitemap</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Gestión de Cursos y Paralelos</h4>
                <p class="text-muted small mb-0">
                    Entorno de Trabajo: <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded fw-bold text-uppercase"><?= $sucursal_usuario === 'LP' ? 'La Paz' : 'El Alto'; ?></span>
                </p>
            </div>
        </div>
        <div>
            <button class="btn btn-primary px-3 py-2 d-inline-flex align-items-center fw-medium" data-bs-toggle="modal" data-bs-target="#addCursoModal" style="border-radius: 8px;">
                <span class="material-icons fs-5 me-1">add_circle</span> Habilitar Nuevo Curso
            </button>
        </div>
    </div>
</div>

<?= $msg; ?>

<?php if (empty($carreras)): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center" role="alert">
        <span class="material-icons me-2">warning</span>
        <div>No se registran carreras activas bajo la sucursal seleccionada.</div>
    </div>
<?php else: ?>

    <div class="mb-4">
        <ul class="nav nav-tabs nav-tabs-carreras" id="tabs-carrera" role="tablist">
            <?php foreach ($carreras as $i => $ca): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2 py-2.5 px-4 <?= $i === 0 ? 'active' : ''; ?>" 
                            id="tab-car-<?= $ca['id_carrera']; ?>" 
                            data-bs-toggle="tab" 
                            data-bs-target="#panel-car-<?= $ca['id_carrera']; ?>" 
                            type="button" 
                            role="tab" 
                            aria-controls="panel-car-<?= $ca['id_carrera']; ?>" 
                            aria-selected="<?= $i === 0 ? 'true' : 'false'; ?>"
                            data-id-carrera="<?= $ca['id_carrera']; ?>">
                        <span class="material-icons fs-5">school</span>
                        <?= htmlspecialchars($ca['nombre']); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="tab-content" id="tabs-carreras-content">
        <?php foreach ($carreras as $i => $ca): ?>
            <div class="tab-pane fade <?= $i === 0 ? 'show active' : ''; ?>" 
                 id="panel-car-<?= $ca['id_carrera']; ?>" 
                 role="tabpanel" 
                 aria-labelledby="tab-car-<?= $ca['id_carrera']; ?>">
                 
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2 fw-bold text-dark">
                            <span class="material-icons text-secondary">lan</span>
                            <span>Paralelos en <?= htmlspecialchars($ca['nombre']); ?> (Gestión Vigente)</span>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light text-muted small uppercase">
                                <tr>
                                    <th class="ps-4">Gestión</th>
                                    <th>Curso / Paralelo</th>
                                    <th>Año Académico</th>
                                    <th>Sucursal</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4" style="width: 240px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $cursos_filtrados = array_filter($cursos, function($cu) use ($ca) {
                                    return (int)$cu['id_carrera'] === (int)$ca['id_carrera'];
                                });
                                ?>
                                <?php if (empty($cursos_filtrados)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted small">
                                            <span class="material-icons fs-2 mb-2 d-block text-opacity-25 text-dark">grid_off</span>
                                            No existen cursos estructurados para esta carrera en la gestión activa.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cursos_filtrados as $c): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded d-inline-flex align-items-center fw-semibold">
                                                <span class="material-icons fs-6 me-1">check</span> <?= htmlspecialchars($c['gestion_varchar'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold fs-5 text-primary font-monospace"><?= htmlspecialchars($c['id_nivel'] . ($c['paralelo'] ?? '')); ?></span>
                                        </td>
                                        <td><span class="text-secondary small fw-medium"><?= htmlspecialchars($c['nivel_nombre'] ?? ''); ?></span></td>
                                        <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($c['sucursal_varchar'] ?? 'LP'); ?></span></td>
                                        <td>
                                            <?= (isset($c['estado']) && (int)$c['estado'] === 1)
                                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded fw-semibold">Activo</span>'
                                                : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded fw-semibold">Inactivo</span>'; 
                                            ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex gap-2">
                                                <button class="btn btn-warning btn-sm px-2.5 edit-btn d-flex align-items-center" style="border-radius: 6px;"
                                                        data-id="<?= $c['id_curso']; ?>"
                                                        data-carrera="<?= $c['id_carrera']; ?>"
                                                        data-nivel="<?= $c['id_nivel']; ?>"
                                                        data-gestion="<?= $c['id_gestion']; ?>"
                                                        data-paralelo="<?= htmlspecialchars($c['paralelo']); ?>"
                                                        data-estado="<?= $c['estado']; ?>">
                                                    <span class="material-icons fs-6 me-1">edit</span> Editar
                                                </button>
                                                
                                                <?php if ((int)($c['estado'] ?? 1) === 1): ?>
                                                    <button class="btn btn-outline-danger btn-sm px-2.5 d-flex align-items-center" style="border-radius: 6px;"
                                                            onclick="confirmarToggle(<?= (int)$c['id_curso']; ?>, 1, '<?= htmlspecialchars($c['id_nivel'].$c['paralelo'], ENT_QUOTES); ?>')">
                                                        <span class="material-icons fs-6 me-1">block</span> Desactivar
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-success btn-sm px-2.5 d-flex align-items-center" style="border-radius: 6px; background-color: #198754;"
                                                            onclick="confirmarToggle(<?= (int)$c['id_curso']; ?>, 0, '<?= htmlspecialchars($c['id_nivel'].$c['paralelo'], ENT_QUOTES); ?>')">
                                                        <span class="material-icons fs-6 me-1">check</span> Activar
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<div class="modal fade" id="addCursoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="cursos.php<?= $_SESSION['role_id'] === 1 ? '?sucursal='.$sucursal_usuario : '' ?>" method="post">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <span class="material-icons text-primary me-2">add_circle</span> Habilitar Curso Académico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if (empty($gestiones_activas)): ?>
                        <div class="alert alert-danger border-0 d-flex align-items-center mb-0" role="alert">
                            <span class="material-icons me-2">error</span>
                            <div>No hay una gestión activa disponible. Por favor, diríjase a <a href="gestiones.php" class="alert-link fw-bold">Control de Gestiones</a> antes de continuar.</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3 bg-light p-3 rounded-3 border">
                            <label class="form-label fw-bold text-muted small d-block mb-2">Gestión Académica Sincronizada</label>
                            <?php foreach($gestiones_activas as $g): ?>
                                <input type="hidden" name="id_gestion" value="<?= $g['id_gestion']; ?>">
                                <div class="d-flex align-items-center text-success fw-semibold">
                                    <span class="material-icons me-1 fs-5">verified</span> 
                                    Gestión <?= htmlspecialchars($g['gestion_varchar']); ?> — Sucursal <?= htmlspecialchars($g['sucursal_varchar']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Carrera Destino *</label>
                            <select class="form-select p-2.5" name="id_carrera" id="modal_add_carrera" style="border-radius: 8px;" required>
                                <option value="">— Selecciona una carrera —</option>
                                <?php foreach($carreras as $car): ?>
                                    <option value="<?= $car['id_carrera']; ?>"><?= htmlspecialchars($car['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Año del Nivel Estudiantil *</label>
                            <select class="form-select p-2.5" name="id_nivel" style="border-radius: 8px;" required>
                                <option value="1">Primer Año</option>
                                <option value="2">Segundo Año</option>
                                <option value="3">Tercer Año</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-muted small">Letra Identificadora del Paralelo *</label>
                            <input type="text" class="form-control p-2.5 fw-bold font-monospace text-center" 
                                   style="border-radius: 8px; width: 100px; text-transform: uppercase;" 
                                   name="paralelo" placeholder="Ej: A" maxlength="1" required autocomplete="off" />
                            <div class="form-text text-muted small mt-1">Escriba únicamente la letra (A, B, C, etc).</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 6px;">Cerrar</button>
                    <?php if (!empty($gestiones_activas)): ?>
                        <button type="submit" name="create_curso" class="btn btn-primary px-4" style="border-radius: 6px;">Habilitar Curso</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCursoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="cursos.php<?= $_SESSION['role_id'] === 1 ? '?sucursal='.$sucursal_usuario : '' ?>" method="post">
                <div class="modal-header py-3" style="background-color: #fcf8e3; border-bottom: 1px solid #fbeed5;">
                    <h5 class="modal-title fw-bold text-warning-emphasis d-flex align-items-center">
                        <span class="material-icons me-2">edit</span> Modificar Atributos de Curso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_curso" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Gestión Académica Vincular</label>
                        <select class="form-select p-2.5" name="id_gestion" id="edit_gestion" style="border-radius: 8px;" required>
                            <?php foreach($todas_gestiones as $g): ?>
                                <option value="<?= $g['id_gestion']; ?>"><?= htmlspecialchars($g['gestion_varchar'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Carrera Vinculada</label>
                        <select class="form-select p-2.5" name="id_carrera" id="edit_carrera" style="border-radius: 8px;" required>
                            <?php foreach($carreras_todas as $car): ?>
                                <option value="<?= $car['id_carrera']; ?>"><?= htmlspecialchars($car['nombre'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Año de la Carrera</label>
                        <select class="form-select p-2.5" name="id_nivel" id="edit_nivel" style="border-radius: 8px;" required>
                            <option value="1">Primer Año</option>
                            <option value="2">Segundo Año</option>
                            <option value="3">Tercer Año</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Paralelo</label>
                        <input type="text" class="form-control p-2.5 fw-bold font-monospace text-center" 
                               style="border-radius: 8px; width: 100px; text-transform: uppercase;" 
                               name="paralelo" id="edit_paralelo" maxlength="1" required />
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Estado Institucional</label>
                        <select class="form-select p-2.5" name="estado" id="edit_estado" style="border-radius: 8px;">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 6px;">Cancelar</button>
                    <button type="submit" name="update_curso" class="btn btn-warning px-4 text-dark fw-medium" style="border-radius: 6px;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const modalEditarElement = document.getElementById('editCursoModal');
    const bsModalEditar = new bootstrap.Modal(modalEditarElement);

    $('.edit-btn').click(function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_carrera').val($(this).data('carrera'));
        $('#edit_nivel').val($(this).data('nivel'));
        $('#edit_gestion').val($(this).data('gestion'));
        $('#edit_paralelo').val($(this).data('paralelo'));
        $('#edit_estado').val($(this).data('estado'));
        bsModalEditar.show();
    });

    // Evento dinámico: Al abrir el modal, preselecciona la carrera de la pestaña activa
    $('#addCursoModal').on('show.bs.modal', function () {
        var activeCarreraId = $('#tabs-carrera button.active').data('id-carrera');
        if(activeCarreraId) {
            $('#modal_add_carrera').val(activeCarreraId);
        }
    });
});

function confirmarToggle(id, estadoActual, identificadorCurso) {
    const titulo = estadoActual === 1 ? '¿Inhabilitar Curso?' : '¿Habilitar Curso?';
    const texto = estadoActual === 1 
        ? `¿Estás seguro de desactivar el aula / paralelo "${identificadorCurso}"? Las inscripciones y asignaciones docentes ligadas se pausarán.`
        : `¿Deseas activar el aula / paralelo "${identificadorCurso}" para procesar operaciones académicas vigentes?`;
    
    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: estadoActual === 1 ? '#d33' : '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: estadoActual === 1 ? 'Sí, desactivar' : 'Sí, activar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?action=toggle&id=${id}&est=${estadoActual}&sucursal=<?= $sucursal_usuario; ?>`;
        }
    });
}
</script>

<?php layout_foot(); ?>