<?php
$id_url = $_GET['id'] ?? null;
if (!$id_url) { header("Location: activos.php"); exit; }

$url = "http://localhost/api_ceti/public/index.php/activos"; 
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$todos_los_activos = $resultado['data'] ?? [];

$activo = null;
foreach ($todos_los_activos as $a) {
    if ($a['id'] == $id_url) {
        $activo = $a;
        break;
    }
}

include 'header.php'; 
?>

<div class="d-flex align-items-center justify-content-center mb-4 p-3 bg-white shadow-sm rounded-3 border-top border-4 border-danger">
    <img src="img/logo.png" alt="Logo CEETII" style="max-height: 70px; margin-right: 20px;">
    <div class="text-start">
        <h2 class="mb-0 fw-bold text-dark" style="letter-spacing: -1px; line-height: 1;">EDITAR ACTIVO</h2>
        <p class="text-muted mb-0 small text-uppercase">Modificación de Registro #<?= htmlspecialchars($id_url) ?></p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        
        <?php if (!$activo): ?>
            <div class="alert alert-warning text-center shadow-sm border-0">
                <span class="material-icons d-block mb-2">search_off</span>
                <h6 class="fw-bold">Activo no encontrado</h6>
                <p class="small">El ID #<?= $id_url ?> no existe en la lista actual.</p>
                <a href="activos.php" class="btn btn-sm btn-outline-secondary">Volver</a>
            </div>
        <?php else: ?>

            <div class="card shadow border-0 mb-5">
                <div class="card-body p-4">
                    <form method="POST" action="actualizar.php">
                        <input type="hidden" name="id" value="<?= $activo['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nombre del Activo</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($activo['nombre'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Código / ID</label>
                            <input type="text" name="codigo_activo" class="form-control" value="<?= htmlspecialchars($activo['codigo_activo'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Estado Actual</label>
                            <select name="estado_id" class="form-select">
                                <?php $est = $activo['estado_id'] ?? 0; ?>
                                <option value="1" <?= ($est == 1) ? 'selected' : '' ?>>Bueno</option>
                                <option value="2" <?= ($est == 2) ? 'selected' : '' ?>>Regular</option>
                                <option value="3" <?= ($est == 3) ? 'selected' : '' ?>>Malo</option>
                                <option value="4" <?= ($est == 4) ? 'selected' : '' ?>>Para desechar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control" value="<?= htmlspecialchars($activo['ubicacion'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Responsable</label>
                            <input type="text" name="responsable" class="form-control" value="<?= htmlspecialchars($activo['responsable'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Precio (Bs.)</label>
                            <input type="number" step="0.01" name="precio_compra" class="form-control" value="<?= $activo['precio_compra'] ?? '' ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"><?= htmlspecialchars($activo['observaciones'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-2 border-top pt-3">
                            <button type="submit" class="btn btn-ceetii py-2">Actualizar Cambios</button>
                            <a href="activos.php" class="btn btn-link btn-sm text-decoration-none text-muted">Cancelar y volver</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>