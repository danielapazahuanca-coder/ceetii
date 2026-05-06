<?php
$busqueda_texto = $_GET['buscar'] ?? '';
$filtro_ubicacion = $_GET['ubicacion'] ?? '';

$url = "http://localhost/api_ceti/public/index.php/activos?papelera=1&buscar=" . urlencode($busqueda_texto) . "&ubicacion=" . urlencode($filtro_ubicacion);
$response = @file_get_contents($url);
$resultado = json_decode($response, true);

$todos_los_activos = array_filter($resultado['data'] ?? [], function($a) {
    return isset($a['activo_sistema']) && $a['activo_sistema'] == 0;
});

function nombreEstado($id) {
    $estados = [1 => "Bueno", 2 => "Regular", 3 => "Malo", 4 => "Para desechar"];
    return $estados[$id] ?? "N/A";
}

include 'header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex align-items-center justify-content-center mb-4 p-3 bg-white shadow-sm rounded-3 border-top border-4 border-secondary">
    <div class="text-center">
        <h2 class="mb-0 fw-bold text-secondary">PAPELERA DE RECICLAJE</h2>
        <p class="text-muted mb-0 small text-uppercase">Activos eliminados del sistema</p>
    </div>
</div>

<div class="card shadow border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-secondary">
                <tr>
                    <th class="ps-3">Nombre del Activo</th>
                    <th>Código</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($todos_los_activos)): ?>
                    <tr><td colspan="6" class="text-center p-4 text-muted">No hay activos en la papelera.</td></tr>
                <?php endif; ?>
                <?php foreach($todos_los_activos as $a): ?>
                <tr>
                    <td class="ps-3 fw-bold"><?= htmlspecialchars($a['nombre']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($a['codigo_activo']) ?></span></td>
                    <td><?= htmlspecialchars($a['ubicacion']) ?></td>
                    <td>
                        <span class="badge bg-secondary"><?= nombreEstado($a['estado_id']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($a['responsable']) ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-success px-3" 
                                onclick="confirmarRestaurar('<?= $a['id'] ?>', '<?= htmlspecialchars($a['nombre']) ?>')">
                            Restaurar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="activos.php" class="btn btn-outline-dark">Volver a Activos</a>
</div>

<script>
function confirmarRestaurar(id, nombre) {
    Swal.fire({
        title: '¿Restaurar Activo?',
        text: `El activo "${nombre}" volverá a aparecer en la lista principal.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, restaurar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `restaurar.php?id=${id}`;
        }
    });
}
</script>

<?php include 'footer.php'; ?>