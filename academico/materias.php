<?php
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php"); 
    exit();
}

require_once __DIR__ . '/config_api.php';

$msg = '';
$api_materias = API_BASE_URL . "/materias";
$api_carreras = API_BASE_URL . "/carreras";

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

// ---- ACCIÓN: REGISTRAR MATERIA ----
if (isset($_POST['create_materia'])) {
    $payload = [
        'id_carrera' => (int)$_POST['id_carrera'],
        'id_nivel'   => (int)$_POST['id_nivel'],
        'sigla'      => trim($_POST['sigla']),
        'nombre'     => trim($_POST['nombre'])
    ];
    $opts = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'POST', 'content' => json_encode($payload), 'ignore_errors' => true]];
    $res = json_decode(@file_get_contents($api_materias, false, stream_context_create($opts)), true);
    
    $id_carrera_filtro = (int)$_POST['id_carrera'];
    $id_nivel_filtro = (int)$_POST['id_nivel'];
    
    if ($res && $res['status'] === 'success') {
        $msg = ok('Materia guardada correctamente en la malla curricular.');
    } else {
        $msg = err($res['message'] ?? 'Ocurrió un error al intentar guardar la materia.');
    }
}

// ---- ACCIÓN: EDITAR MATERIA ----
if (isset($_POST['update_materia'])) {
    $id_materia = (int)$_POST['id_materia'];
    $payload = [
        'id_carrera' => (int)$_POST['id_carrera'],
        'id_nivel'   => (int)$_POST['id_nivel'],
        'sigla'      => trim($_POST['sigla']),
        'nombre'     => trim($_POST['nombre']),
        'estado'     => (int)$_POST['estado']
    ];
    $opts = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'PUT', 'content' => json_encode($payload), 'ignore_errors' => true]];
    json_decode(@file_get_contents($api_materias . "/" . $id_materia, false, stream_context_create($opts)), true);
    
    header("Location: materias.php?id_carrera=" . (int)$_POST['id_carrera'] . "&nivel=" . (int)$_POST['id_nivel']);
    exit();
}

// ---- ACCIÓN: TOGGLE ESTADO ----
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id_materia = (int)$_GET['id'];
    $payload = ['estado_actual' => (int)($_GET['est'] ?? 1)];
    $opts = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'PUT', 'content' => json_encode($payload), 'ignore_errors' => true]];
    @file_get_contents($api_materias . "/toggle/" . $id_materia, false, stream_context_create($opts));
    
    header("Location: materias.php?id_carrera=" . (int)($_GET['id_carrera'] ?? 0) . "&nivel=" . (int)($_GET['nivel'] ?? 1));
    exit();
}

// ---- CONSULTAR LISTADOS ----
$materias = json_decode(@file_get_contents($api_materias), true)['data'] ?? [];
$carreras = json_decode(@file_get_contents($api_carreras), true)['data'] ?? [];

$id_carrera_filtro = isset($_GET['id_carrera']) ? (int)$_GET['id_carrera'] : 0;
$carrera_actual = null;

if ($id_carrera_filtro > 0) {
    foreach ($carreras as $c) {
        if ((int)$c['id_carrera'] === $id_carrera_filtro) {
            $carrera_actual = $c;
            break;
        }
    }
    $materias = array_filter($materias, fn($m) => (int)$m['id_carrera'] === $id_carrera_filtro);
}

$id_nivel_filtro = isset($_GET['nivel']) ? (int)$_GET['nivel'] : 1;
$materias = array_filter($materias, fn($m) => (int)$m['id_nivel'] === $id_nivel_filtro);

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Materias', $current_page, 1);
?>

<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">menu_book</span>
            </div>
            <div>
                <?php if ($carrera_actual): ?>
                    <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Materias de: <?= htmlspecialchars($carrera_actual['nombre']); ?></h4>
                <?php else: ?>
                    <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Gestión de Materias</h4>
                    <p class="text-muted small mb-0">Mallas curriculares institucionales organizadas por programas de estudio y niveles.</p>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($carrera_actual): ?>
                <a href="carreras.php?sucursal=<?= urlencode($carrera_actual['sucursal'] ?? $carrera_actual['sucursal_varchar'] ?? 'LP'); ?>" class="btn btn-outline-secondary px-3 py-2 d-inline-flex align-items-center fw-medium" style="border-radius: 8px;">
                    <span class="material-icons me-1 fs-5">arrow_back</span> Volver a Carreras
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $msg; ?>

<?php if ($id_carrera_filtro > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="nav nav-pills bg-white p-1.5 rounded shadow-sm d-inline-flex" style="border: 1px solid #dee2e6;">
            <a href="materias.php?id_carrera=<?= $id_carrera_filtro; ?>&nivel=1" class="nav-link px-4 py-2 fw-medium d-flex align-items-center <?= ($id_nivel_filtro == 1) ? 'active bg-primary text-white' : 'text-secondary'; ?>" style="border-radius: 6px;">
                <span class="material-icons me-2 fs-5">looks_one</span> Primer Año
            </a>
            <a href="materias.php?id_carrera=<?= $id_carrera_filtro; ?>&nivel=2" class="nav-link px-4 py-2 fw-medium d-flex align-items-center <?= ($id_nivel_filtro == 2) ? 'active bg-primary text-white' : 'text-secondary'; ?>" style="border-radius: 6px;">
                <span class="material-icons me-2 fs-5">looks_two</span> Segundo Año
            </a>
            <a href="materias.php?id_carrera=<?= $id_carrera_filtro; ?>&nivel=3" class="nav-link px-4 py-2 fw-medium d-flex align-items-center <?= ($id_nivel_filtro == 3) ? 'active bg-primary text-white' : 'text-secondary'; ?>" style="border-radius: 6px;">
                <span class="material-icons me-2 fs-5">looks_3</span> Tercer Año
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2 fw-bold text-dark">
            <span class="material-icons text-secondary">list</span>
            <span>
                <?php
                $nombreNivel = [1 => 'Primer Año', 2 => 'Segundo Año', 3 => 'Tercer Año'];
                echo "Listado de Materias — " . ($nombreNivel[$id_nivel_filtro] ?? 'General');
                ?>
            </span>
        </div>
        <button class="btn btn-primary btn-sm px-3 py-1.5 d-flex align-items-center fw-medium" data-bs-toggle="modal" data-bs-target="#addMateriaModal" style="border-radius: 6px;">
            <span class="material-icons fs-5 me-1">add</span> Nueva Materia
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead class="table-light text-muted small uppercase">
                <tr>
                    <th class="ps-4">Nombre de la Asignatura</th>
                    <th style="width: 120px;">Sigla</th>
                    <th>Carrera / Programa</th>
                    <th>Nivel / Gestión</th>
                    <th>Estado</th>
                    <th class="text-end pe-4" style="width: 240px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materias)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted small">
                            <span class="material-icons fs-2 mb-2 d-block text-opacity-25 text-dark">auto_stories</span>
                            No existen materias registradas en este nivel académico.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($m['nombre'] ?? ''); ?></span></td>
                        <td><span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold font-monospace"><?= htmlspecialchars($m['sigla'] ?? ''); ?></span></td>
                        <td><span class="text-secondary small"><?= htmlspecialchars($m['carrera_nombre'] ?? 'Sin asignar'); ?></span></td>
                        <td>
                            <span class="badge bg-light text-primary border px-2.5 py-1.5 rounded fw-medium">
                                <?= htmlspecialchars($m['nivel_nombre'] ?? ''); ?>
                            </span>
                        </td>
                        <td>
                            <?= ((int)$m['estado'] === 1)
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded fw-semibold">Activo</span>'
                                : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded fw-semibold">Inactivo</span>'; 
                            ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-2">
                                <button class="btn btn-warning btn-sm px-2.5 edit-btn d-flex align-items-center" style="border-radius: 6px;"
                                    data-id="<?= $m['id_materia'] ?? ''; ?>"
                                    data-sigla="<?= htmlspecialchars($m['sigla'] ?? ''); ?>"
                                    data-nombre="<?= htmlspecialchars($m['nombre'] ?? ''); ?>"
                                    data-carrera="<?= $m['id_carrera'] ?? ''; ?>"
                                    data-nivel="<?= $m['id_nivel'] ?? ''; ?>"
                                    data-estado="<?= $m['estado'] ?? '1'; ?>">
                                    <span class="material-icons fs-6 me-1">edit</span> Editar
                                </button>
                                
                                <?php if ((int)$m['estado'] === 1): ?>
                                    <button class="btn btn-outline-danger btn-sm px-2.5 d-flex align-items-center" style="border-radius: 6px;"
                                            onclick="confirmarToggle(<?= (int)$m['id_materia']; ?>, 1, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES); ?>', <?= $id_carrera_filtro; ?>, <?= $id_nivel_filtro; ?>)">
                                        <span class="material-icons fs-6 me-1">block</span> Desactivar
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm px-2.5 d-flex align-items-center" style="border-radius: 6px; background-color: #198754;"
                                            onclick="confirmarToggle(<?= (int)$m['id_materia']; ?>, 0, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES); ?>', <?= $id_carrera_filtro; ?>, <?= $id_nivel_filtro; ?>)">
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

<div class="modal fade" id="addMateriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="materias.php" method="post">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <span class="material-icons text-primary me-2">add_circle</span> Registrar Nueva Materia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Carrera Destino *</label>
                        <select class="form-select p-2.5" name="id_carrera" style="border-radius: 8px;" required <?= $id_carrera_filtro ? 'disabled' : ''; ?>>
                            <?php foreach($carreras as $c): ?>
                                <option value="<?= $c['id_carrera']; ?>" <?= ($id_carrera_filtro == $c['id_carrera']) ? 'selected' : ''; ?>><?= htmlspecialchars($c['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if($id_carrera_filtro): ?>
                            <input type="hidden" name="id_carrera" value="<?= $id_carrera_filtro; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Año Académico / Nivel *</label>
                        <select class="form-select p-2.5" name="id_nivel" style="border-radius: 8px;" required>
                            <option value="1" <?= ($id_nivel_filtro == 1) ? 'selected' : ''; ?>>Primer Año</option>
                            <option value="2" <?= ($id_nivel_filtro == 2) ? 'selected' : ''; ?>>Segundo Año</option>
                            <option value="3" <?= ($id_nivel_filtro == 3) ? 'selected' : ''; ?>>Tercer Año</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre de la Asignatura *</label>
                        <input type="text" class="form-control p-2.5 nombre-input" style="border-radius: 8px;" name="nombre" placeholder="Ej: Programación I" required autocomplete="off" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Sigla Identificadora *</label>
                        <input type="text" class="form-control p-2.5 font-monospace sigla-input" style="border-radius: 8px; text-transform: uppercase;" name="sigla" placeholder="Ej: PRO-101" required autocomplete="off" />
                        <small class="form-text text-muted">3 letras + guion + números (Ej: SMO-643)</small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 6px;">Cerrar</button>
                    <button type="submit" name="create_materia" class="btn btn-primary px-4" style="border-radius: 6px;">Guardar Materia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editMateriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="materias.php" method="post">
                <div class="modal-header py-3" style="background-color: #fcf8e3; border-bottom: 1px solid #fbeed5;">
                    <h5 class="modal-title fw-bold text-warning-emphasis d-flex align-items-center">
                        <span class="material-icons me-2">edit</span> Modificar Datos de Asignatura
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_materia" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Carrera Vincular</label>
                        <select class="form-select p-2.5" name="id_carrera" id="edit_carrera" style="border-radius: 8px;" required>
                            <?php foreach($carreras as $c): ?>
                                <option value="<?= $c['id_carrera']; ?>"><?= htmlspecialchars($c['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Año Académico</label>
                        <select class="form-select p-2.5" name="id_nivel" id="edit_nivel" style="border-radius: 8px;" required>
                            <option value="1">Primer Año</option>
                            <option value="2">Segundo Año</option>
                            <option value="3">Tercer Año</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre de la Asignatura</label>
                        <input type="text" class="form-control p-2.5 nombre-input" style="border-radius: 8px;" name="nombre" id="edit_nombre" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Sigla</label>
                        <input type="text" class="form-control p-2.5 font-monospace sigla-input" style="border-radius: 8px; text-transform: uppercase;" name="sigla" id="edit_sigla" required />
                        <small class="form-text text-muted">3 letras + guion + números (Ej: SMO-643)</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Estado de la Materia</label>
                        <select class="form-select p-2.5" name="estado" id="edit_estado" style="border-radius: 8px;">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 6px;">Cancelar</button>
                    <button type="submit" name="update_materia" class="btn btn-warning px-4 text-dark fw-medium" style="border-radius: 6px;">Modificar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Configuración nativa del disparador del Modal en Bootstrap 5
    const modalEditarElement = document.getElementById('editMateriaModal');
    const bsModalEditar = new bootstrap.Modal(modalEditarElement);

    $('.edit-btn').click(function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_sigla').val($(this).data('sigla'));
        $('#edit_nombre').val($(this).data('nombre'));
        $('#edit_carrera').val($(this).data('carrera'));
        $('#edit_nivel').val($(this).data('nivel'));
        $('#edit_estado').val($(this).data('estado'));
        bsModalEditar.show();
    });

    // Validación de nombre de asignatura: primera letra mayúscula
    $('.nombre-input').on('input', function() {
        let valor = $(this).val();
        if (valor.length > 0) {
            valor = valor.charAt(0).toUpperCase() + valor.slice(1).toLowerCase();
        }
        $(this).val(valor);
    });

    // Validación de sigla en tiempo real - versión mejorada y más flexible
    $('.sigla-input').on('input', function() {
        let valor = $(this).val().toUpperCase().trim();
        
        // Permitir solo letras, guiones y números
        valor = valor.replace(/[^A-Z0-9-]/g, '');
        
        // Si está vacío, permitirlo
        if (valor === '') {
            $(this).val('');
            return;
        }
        
        // Aplicar formato solo si ya tiene al menos 3 caracteres sin guion
        if (!valor.includes('-') && valor.length >= 3) {
            valor = valor.substring(0, 3) + '-' + valor.substring(3);
        }
        
        // Si ya tiene guion, limpiarlo y reformatear
        if (valor.includes('-')) {
            let partes = valor.split('-');
            partes[0] = partes[0].substring(0, 3).replace(/[^A-Z]/g, '');
            partes[1] = partes[1].replace(/[^0-9]/g, '').substring(0, 3);
            valor = partes[0] + (partes[1] ? '-' + partes[1] : '');
        }
        
        // Limitar a máximo 7 caracteres (3-3)
        if (valor.length > 7) {
            valor = valor.substring(0, 7);
        }
        
        $(this).val(valor);
    });
});

// Alertas de Cambio de Estado Modulares con SweetAlert2
function confirmarToggle(id, estadoActual, nombreMateria, idCarrera, nivel) {
    const titulo = estadoActual === 1 ? '¿Inhabilitar Materia?' : '¿Habilitar Materia?';
    const texto = estadoActual === 1 
        ? `¿Estás seguro de desactivar la asignatura "${nombreMateria}"? Ningún docente podrá asignarla en los cursos vigentes.`
        : `¿Deseas activar la asignatura "${nombreMateria}" para su disponibilidad inmediata en el plan académico?`;
    
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
            window.location.href = `?action=toggle&id=${id}&est=${estadoActual}&id_carrera=${idCarrera}&nivel=${nivel}`;
        }
    });
}
</script>

<?php layout_foot(); ?>