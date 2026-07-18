<?php
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php"); 
    exit();
}

require_once __DIR__ . '/config_api.php';

$msg = '';
$api = API_BASE_URL . "/carreras";

function api_call(string $method, string $url, array $payload = []): ?array {
    $opts = ['http' => [
        'header'        => "Content-Type: application/json\r\n",
        'method'        => $method,
        'content'       => $payload ? json_encode($payload) : '',
        'ignore_errors' => true
    ]];
    return json_decode(@file_get_contents($url, false, stream_context_create($opts)), true);
}

// Mensajes adaptados a Bootstrap 5
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

// ============================================
// 1. PRIMERO: obtener las carreras desde la API
//    (esto tiene que existir ANTES de procesar cualquier POST,
//    porque la validación de duplicados necesita leer $carreras)
// ============================================
function cargar_carreras(string $api): array {
    $data = json_decode(@file_get_contents($api), true);
    return ($data && $data['status'] === 'success') ? $data['data'] : [];
}

$carreras = cargar_carreras($api);

$sucursal_sesion = resolver_sucursal($_SESSION['role_id']);

// ============================================
// 2. Procesar creación de carrera
// ============================================
if (isset($_POST['create_carrera'])) {
    $nombre = trim($_POST['nombre']);
    // Convertir a mayúsculas la primera letra de cada palabra
    $nombre = ucwords(strtolower($nombre));
    $sucursal = $_POST['sucursal_varchar'];

    // Validar que no exista una carrera con el mismo nombre en la misma sucursal
    $carrera_existe = false;
    foreach ($carreras as $c) {
        $suc_carrera = strtoupper(trim($c['sucursal'] ?? $c['sucursal_varchar'] ?? ''));
        if (strcasecmp($c['nombre'], $nombre) === 0 && $suc_carrera === strtoupper($sucursal)) {
            $carrera_existe = true;
            break;
        }
    }

    if ($carrera_existe) {
        $msg = err("La carrera \"$nombre\" ya está registrada en esta sede. Por favor, use un nombre diferente.");
    } else {
        $res = api_call('POST', $api, [
            'nombre' => $nombre,
            'sucursal_varchar' => $sucursal
        ]);
        $msg = ($res && $res['status'] === 'success') ? ok('Carrera creada correctamente.') : err($res['message'] ?? 'Error al crear la carrera.');

        // Si se creó bien, refrescamos $carreras para que la tabla
        // y el JSON usado en el JS de validación queden actualizados
        if ($res && $res['status'] === 'success') {
            $carreras = cargar_carreras($api);
        }
    }
}

// ============================================
// 3. Procesar actualización de carrera
// ============================================
if (isset($_POST['update_carrera'])) {
    $id  = (int)$_POST['id_carrera'];
    $nombre = trim($_POST['nombre']);
    // Convertir a mayúsculas la primera letra de cada palabra
    $nombre = ucwords(strtolower($nombre));

    $res = api_call('PUT', "{$api}/{$id}", [
        'nombre'           => $nombre,
        'sucursal_varchar' => $_POST['sucursal_varchar'],
        'estado'           => (int)$_POST['estado']
    ]);
    $msg = ($res && $res['status'] === 'success') ? ok('Carrera actualizada correctamente.') : err($res['message'] ?? 'Error al actualizar.');

    // Refrescar $carreras tras editar, para que la tabla no muestre datos viejos
    if ($res && $res['status'] === 'success') {
        $carreras = cargar_carreras($api);
    }
}

// ============================================
// 4. Procesar activar/desactivar (toggle)
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    api_call('PUT', "{$api}/toggle/" . (int)$_GET['id'], ['estado_actual' => (int)($_GET['est'] ?? 1)]);
    header("Location: carreras.php?sucursal=" . urlencode($_GET['suc'] ?? 'EA'));
    exit();
}

// ============================================
// 5. Filtrar por sucursal para mostrar en la tabla
// ============================================
$carreras = array_filter($carreras, function($c) use ($sucursal_sesion) {
    $suc = strtoupper(trim($c['sucursal'] ?? $c['sucursal_varchar'] ?? ''));
    return $suc === strtoupper($sucursal_sesion);
});

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Carreras', $current_page, $_SESSION['role_id']);
?>

<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">school</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Carreras de la Institución</h4>
            </div>
        </div>
        <div>
            <button class="btn btn-success px-4 py-2 d-flex align-items-center fw-medium" data-bs-toggle="modal" data-bs-target="#modalCrear" style="border-radius: 8px;">
                <span class="material-icons me-2 fs-5">add_circle</span> Nueva Carrera
            </button>
        </div>
    </div>
</div>

<?= $msg; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead class="table-light text-muted small uppercase">
                <tr>
                    <th class="ps-4" style="width: 70px;">#</th>
                    <th>Nombre de la Carrera</th>
                    <th>Sede / Sucursal</th>
                    <th>Estado</th>
                    <th class="text-center">Malla Curricular</th>
                    <th class="text-end pe-4" style="width: 220px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($carreras)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted small">
                            <span class="material-icons fs-2 mb-2 d-block text-opacity-25 text-dark">folder_open</span>
                            No hay carreras registradas para esta sede académica aún.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $contador = 1;
                    foreach ($carreras as $c): 
                    ?>
                    <tr>
                        <td class="ps-4 text-muted fw-medium"><?= $contador++; ?></td>
                        <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($c['nombre']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border px-2.5 py-1.5 rounded fw-medium">
                                <?= htmlspecialchars($c['sucursal'] ?? $c['sucursal_varchar'] ?? ''); ?>
                            </span>
                        </td>
                        <td>
                            <?= ((int)$c['estado'] === 1)
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded fw-semibold">Activa</span>'
                                : '<span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded fw-normal">Inactiva</span>'; 
                            ?>
                        </td>
                        <td class="text-center">
                            <a href="materias.php?id_carrera=<?= $c['id_carrera']; ?>" class="btn btn-outline-primary btn-sm px-3 d-inline-flex align-items-center" style="border-radius: 6px;">
                                <span class="material-icons me-1 fs-5">menu_book</span> Ver Materias
                            </a>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-2">
                                <button class="btn btn-warning btn-sm px-3 edit-btn d-flex align-items-center" style="border-radius: 6px;"
                                    data-id="<?= $c['id_carrera']; ?>"
                                    data-nombre="<?= htmlspecialchars($c['nombre']); ?>"
                                    data-sucursal="<?= htmlspecialchars($c['sucursal'] ?? $c['sucursal_varchar'] ?? ''); ?>"
                                    data-estado="<?= $c['estado']; ?>">
                                    <span class="material-icons fs-6 me-1">edit</span> Editar
                                </button>

                                <?php if ((int)$c['estado'] === 1): ?>
                                    <button class="btn btn-outline-danger btn-sm px-2.5 d-flex align-items-center" style="border-radius: 6px;"
                                            onclick="confirmarToggle(<?= (int)$c['id_carrera']; ?>, 1, '<?= htmlspecialchars($c['nombre'], ENT_QUOTES); ?>', '<?= $sucursal_sesion; ?>')">
                                        <span class="material-icons fs-6 me-1">block</span> Desactivar
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm px-2.5 d-flex align-items-center" style="border-radius: 6px; background-color: #198754;"
                                            onclick="confirmarToggle(<?= (int)$c['id_carrera']; ?>, 0, '<?= htmlspecialchars($c['nombre'], ENT_QUOTES); ?>', '<?= $sucursal_sesion; ?>')">
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

<div id="modalCrear" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" id="formCrear">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <span class="material-icons text-success me-2">add_circle</span> Registrar Nueva Carrera
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre de la Carrera <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre_carrera" class="form-control p-2.5" style="border-radius: 8px;" 
                               placeholder="Ej: Sistemas Informáticos" required autocomplete="off" 
                               maxlength="25" minlength="3">
                        <div class="form-text text-muted mt-1" style="font-size: 0.75rem;">
                            <span class="material-icons me-1" style="font-size: 0.95rem;">info</span> 
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Sede Asignada <span class="text-danger">*</span></label>
                        <select name="sucursal_varchar" class="form-select p-2.5" style="border-radius: 8px;" required>
                            <option value="">— Seleccione —</option>
                            <option value="EA" <?= $sucursal_sesion === 'EA' ? 'selected' : '' ?>>El Alto (EA)</option>
                            <option value="LP" <?= $sucursal_sesion === 'LP' ? 'selected' : '' ?>>La Paz (LP)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 6px;">Cancelar</button>
                    <button type="submit" name="create_carrera" class="btn btn-success px-4" style="border-radius: 6px;">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalEditar" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" id="formEditar">
                <div class="modal-header py-3" style="background-color: #fcf8e3; border-bottom: 1px solid #fbeed5;">
                    <h5 class="modal-title fw-bold text-warning-emphasis d-flex align-items-center">
                        <span class="material-icons me-2">edit</span> Modificar Datos de la Carrera
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_carrera" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre de la Carrera</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control p-2.5" style="border-radius: 8px;" 
                               required maxlength="35" minlength="3">
                        <div class="form-text text-muted mt-1" style="font-size: 0.75rem;">
                            <span class="material-icons me-1" style="font-size: 0.95rem;">info</span> 
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Sede / Sucursal</label>
                        <select name="sucursal_varchar" id="edit_sucursal" class="form-select p-2.5" style="border-radius: 8px;" required>
                            <option value="EA">El Alto (EA)</option>
                            <option value="LP">La Paz (LP)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted small">Estado del Programa</label>
                        <select name="estado" id="edit_estado" class="form-select p-2.5" style="border-radius: 8px;">
                            <option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 6px;">Cancelar</button>
                    <button type="submit" name="update_carrera" class="btn btn-warning px-4 text-dark fw-medium" style="border-radius: 6px;">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const modalEditarElement = document.getElementById('modalEditar');
    const bsModalEditar = new bootstrap.Modal(modalEditarElement);

    $('.edit-btn').click(function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_nombre').val($(this).data('nombre'));
        $('#edit_sucursal').val($(this).data('sucursal'));
        $('#edit_estado').val($(this).data('estado'));
        bsModalEditar.show();
    });

    // ── Validaciones en tiempo real para el campo nombre ──
    function validarNombreCarrera(input) {
        // 1. Solo letras, espacios y guiones
        let valor = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]/g, '');
        
        // 2. Convertir a minúsculas y luego capitalizar cada palabra
        valor = valor.toLowerCase().replace(/\b\w/g, function(letra) {
            return letra.toUpperCase();
        });
        
        // 3. Limitar caracteres
        if (valor.length > 100) {
            valor = valor.slice(0, 100);
        }
        
        input.value = valor;
    }

    // Aplicar validación a los campos de nombre
    $('#nombre_carrera').on('input', function() {
        validarNombreCarrera(this);
    });

    $('#edit_nombre').on('input', function() {
        validarNombreCarrera(this);
    });

    // ── Validación antes de enviar el formulario ──
    $('#formCrear').on('submit', function(e) {
        const nombre = $('#nombre_carrera').val().trim();
        const sucursal = $('select[name="sucursal_varchar"]').val();
        
        if (nombre.length < 3) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre de la carrera debe tener al menos 3 caracteres.', 'error');
            return false;
        }
        if (nombre.length > 100) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre de la carrera no puede exceder los 100 caracteres.', 'error');
            return false;
        }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]+$/.test(nombre)) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre solo puede contener letras, espacios y guiones.', 'error');
            return false;
        }
        
        // Verificar que no exista una carrera con el mismo nombre en la misma sucursal (validación en cliente)
        const carrerasExistentes = <?= json_encode($carreras); ?>;
        const carreraDuplicada = carrerasExistentes.some(carrera => 
            carrera.nombre.toLowerCase() === nombre.toLowerCase() && 
            (carrera.sucursal || carrera.sucursal_varchar).toUpperCase() === sucursal.toUpperCase()
        );
        
        if (carreraDuplicada) {
            e.preventDefault();
            Swal.fire('Error', `La carrera "${nombre}" ya está registrada en esta sede. Por favor, use un nombre diferente.`, 'error');
            return false;
        }
    });

    $('#formEditar').on('submit', function(e) {
        const nombre = $('#edit_nombre').val().trim();
        if (nombre.length < 3) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre de la carrera debe tener al menos 3 caracteres.', 'error');
            return false;
        }
        if (nombre.length > 100) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre de la carrera no puede exceder los 100 caracteres.', 'error');
            return false;
        }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]+$/.test(nombre)) {
            e.preventDefault();
            Swal.fire('Error', 'El nombre solo puede contener letras, espacios y guiones.', 'error');
            return false;
        }
    });
});

function confirmarToggle(id, estadoActual, nombreCarrera, sucursal) {
    const titulo = estadoActual === 1 ? '¿Desactivar Carrera?' : '¿Activar Carrera?';
    const texto = estadoActual === 1 
        ? `¿Estás seguro de inhabilitar la carrera "${nombreCarrera}"? Esto podría afectar temporalmente la visualización de sus materias.`
        : `¿Deseas dar de alta nuevamente la carrera "${nombreCarrera}"?`;
    
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
            window.location.href = `?action=toggle&id=${id}&est=${estadoActual}&suc=${sucursal}`;
        }
    });
}
</script>

<?php layout_foot(); ?>