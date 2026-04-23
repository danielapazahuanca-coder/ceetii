<?php
include 'header.php'; 

$url_activos = "http://localhost/api_ceti/public/index.php/activos";
$url_prestamos = "http://localhost/api_ceti/public/index.php/prestamos";

$res_activos = @file_get_contents($url_activos);
$activos = json_decode($res_activos, true)['data'] ?? [];

$res_prestamos = @file_get_contents($url_prestamos);
$lista_prestamos = json_decode($res_prestamos, true)['data'] ?? [];
$lista_prestamos = array_reverse($lista_prestamos); 
?>

<div class="card shadow border-0 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-dark">GESTIÓN DE PRÉSTAMOS</h4>
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
                <span class="material-icons align-middle me-1">add</span> Nuevo Préstamo
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Activo</th>
                        <th>Cant.</th>
                        <th>Solicitante</th>
                        <th>CI / Doc.</th>
                        <th>Fecha Préstamo</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_prestamos as $p): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($p['nombre_activo'] ?? 'S/N') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($p['cantidad'] ?? '1') ?></span></td>
                        <td><?= htmlspecialchars($p['solicitante']) ?></td>
                        <td><?= htmlspecialchars($p['documento_identidad']) ?></td>
                        <td class="small"><?= date('d/m/Y H:i', strtotime($p['fecha_prestamo'])) ?></td>
                        <td>
                            <?= empty($p['fecha_devolucion']) 
                                ? '<span class="badge bg-warning text-dark">Prestado</span>' 
                                : '<span class="badge bg-success">Devuelto</span>' ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="generar_comprobante.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                    <span class="material-icons" style="font-size: 1.1rem;">picture_as_pdf</span>
                                </a>
                                <?php if(empty($p['fecha_devolucion'])): ?>
                                <button onclick="devolverActivo(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-primary">
                                    <span class="material-icons" style="font-size: 1.1rem;">assignment_return</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPrestamo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="guardar_prestamo.php" method="POST">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Registrar Nuevo Préstamo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Seleccionar Activo</label>
                        <select name="activo_id" class="form-select" required>
                            <option value="">-- Seleccione un activo --</option>
                            <?php foreach($activos as $a): ?>
                                <option value="<?= $a['id'] ?>">
                                    <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['codigo_activo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Nombre del Solicitante</label>
                        <input type="text" name="solicitante" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Documento de Identidad (CI)</label>
                        <input type="text" name="documento_identidad" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Guardar Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function devolverActivo(id) {
    if(confirm('¿Confirmar devolución de este activo?')) {
        window.location.href = `devolver_prestamo.php?id=${id}`;
    }
}
</script>
<?php include 'footer.php'; ?>