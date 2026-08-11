<?php
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header("Location: ../login.php"); 
    exit();
}

require_once 'functions.php'; // Herramientas globales de la API (api_get, api_post, api_put, ok, err)
require_once '_layout.php';

$msg = '';
$api = API_BASE_URL . "/gestion";

// ── Crear gestión ──────────────────────────────────────────────────────────────
if (isset($_POST['create_gestion'])) {
    $payload = [
        'gestion_varchar' => trim($_POST['gestion_varchar']),
        'sucursal_varchar' => $_POST['sucursal_varchar']
    ];
    
    $resData = api_post($api, $payload);
    $msg = ($resData && $resData['status'] === 'success')
        ? ok('Gestión <b>' . htmlspecialchars($payload['gestion_varchar']) . '</b> creada. Recuerda activarla cuando corresponda.')
        : err(htmlspecialchars($resData['message'] ?? 'No se pudo registrar la gestión.'));
}

// ── Activar gestión ────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'activate' && isset($_GET['id'], $_GET['suc'])) {
    $payload = ['sucursal_varchar' => $_GET['suc']];
    api_put("{$api}/activate/" . (int)$_GET['id'], $payload);
    
    header("Location: gestiones.php?ok=1"); 
    exit();
}

// ── Cargar lista de gestiones ──────────────────────────────────────────────────
$resList = api_get($api);
$gestiones = ($resList && $resList['status'] === 'success') ? $resList['data'] : [];

// Separar por sucursal de manera eficiente usando colecciones filtradas
$gestiones_lp = array_filter($gestiones, fn($g) => $g['sucursal_varchar'] === 'LP');
$gestiones_ea = array_filter($gestiones, fn($g) => $g['sucursal_varchar'] === 'EA');

$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Control de Gestiones', $current_page, 1);
?>

<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">calendar_today</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Control de Gestiones Académicas</h4>
                <p class="text-muted small mb-0">Configuración de los periodos vigentes. Cada sucursal opera bajo un único entorno activo.</p>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['ok'])): ?>
    <?= ok('<b>Gestión activada correctamente.</b> El nuevo periodo ya se encuentra vigente en el sistema.'); ?>
<?php endif; ?>
<?= $msg; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                    <span class="material-icons text-success me-2">add_circle</span> Aperturar Nuevo Periodo
                </h6>
            </div>
            <div class="card-body pt-2">
                <form method="post" action="gestiones.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Año o Código de Periodo <span class="text-danger">*</span></label>
                        <input type="text" name="gestion_varchar" class="form-control p-2.5" style="border-radius: 8px;" placeholder="Ej: 2027 o I-2027" maxlength="10" required />
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted small">Sucursal Destino <span class="text-danger">*</span></label>
                        <select name="sucursal_varchar" class="form-select p-2.5" style="border-radius: 8px;" required>
                            <option value="LP">La Paz (LP)</option>
                            <option value="EA">El Alto (EA)</option>
                        </select>
                    </div>
                    <button type="submit" name="create_gestion" class="btn btn-success btn-dark w-100 py-2.5 fw-medium d-flex align-items-center justify-content-center" style="border-radius: 8px; background-color: #198754; border-color: #198754;">
                        <span class="material-icons me-2 fs-5">save</span> Guardar Gestión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="d-flex flex-column gap-4">
            
            <div class="card border-0 shadow-sm" style="border-left: 5px solid #0d6efd !important;">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                        <span class="material-icons text-primary me-2">location_on</span> Sede La Paz (LP)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light text-muted small uppercase">
                            <tr>
                                <th class="ps-4">Periodo Académico</th>
                                <th>Estado Operacional</th>
                                <th class="text-end pe-4" style="width: 140px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gestiones_lp)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Sin gestiones registradas para esta sede.</td></tr>
                            <?php else: ?>
                                <?php foreach ($gestiones_lp as $g): 
                                    $activa = (int)$g['estado_bt'] === 1;
                                ?>
                                <tr class="<?= $activa ? 'table-success fw-medium' : ''; ?>">
                                    <td class="ps-4">
                                        <span class="material-icons align-middle text-muted me-1 fs-5">calendar_today</span>
                                        <span class="text-dark"><?= htmlspecialchars($g['gestion_varchar']); ?></span>
                                    </td>
                                    <td>
                                        <?= $activa 
                                            ? '<span class="badge bg-success text-white px-2.5 py-1.5 rounded fw-semibold d-inline-flex align-items-center"><span class="material-icons fs-6 me-1">play_arrow</span> EN CURSO</span>' 
                                            : '<span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded fw-normal">Archivada</span>'; 
                                        ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if (!$activa): ?>
                                            <button class="btn btn-primary btn-sm px-3" style="border-radius: 6px;" onclick="confirmarActivacion(<?= (int)$g['id_gestion']; ?>, '<?= htmlspecialchars($g['gestion_varchar'], ENT_QUOTES); ?>', 'LP')">
                                                Activar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success btn-sm px-3 border-0" disabled><span class="material-icons align-middle fs-5">stars</span> Vigente</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-left: 5px solid #e67e22 !important;">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                        <span class="material-icons style-icon me-2" style="color: #e67e22;">location_on</span> Sede El Alto (EA)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light text-muted small uppercase">
                            <tr>
                                <th class="ps-4">Periodo Académico</th>
                                <th>Estado Operacional</th>
                                <th class="text-end pe-4" style="width: 140px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gestiones_ea)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Sin gestiones registradas para esta sede.</td></tr>
                            <?php else: ?>
                                <?php foreach ($gestiones_ea as $g): 
                                    $activa = (int)$g['estado_bt'] === 1;
                                ?>
                                <tr class="<?= $activa ? 'table-success fw-medium' : ''; ?>">
                                    <td class="ps-4">
                                        <span class="material-icons align-middle text-muted me-1 fs-5">calendar_today</span>
                                        <span class="text-dark"><?= htmlspecialchars($g['gestion_varchar']); ?></span>
                                    </td>
                                    <td>
                                        <?= $activa 
                                            ? '<span class="badge bg-success text-white px-2.5 py-1.5 rounded fw-semibold d-inline-flex align-items-center"><span class="material-icons fs-6 me-1">play_arrow</span> EN CURSO</span>' 
                                            : '<span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded fw-normal">Archivada</span>'; 
                                        ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if (!$activa): ?>
                                            <button class="btn btn-primary btn-sm px-3" style="border-radius: 6px;" onclick="confirmarActivacion(<?= (int)$g['id_gestion']; ?>, '<?= htmlspecialchars($g['gestion_varchar'], ENT_QUOTES); ?>', 'EA')">
                                                Activar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success btn-sm px-3 border-0" disabled><span class="material-icons align-middle fs-5">stars</span> Vigente</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmarActivacion(id, periodo, sucursal) {
    Swal.fire({
        title: '¿Vigencia de Periodo?',
        text: `¿Estás seguro de establecer el periodo "${periodo}" como ACTIVO para la sucursal [${sucursal}]? El periodo actual pasará al archivo histórico.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, activar periodo',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?action=activate&id=${id}&suc=${sucursal}`;
        }
    });
}
</script>

<?php layout_foot(); ?>