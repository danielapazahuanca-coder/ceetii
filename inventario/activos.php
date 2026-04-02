<?php
// logica de datos
$busqueda_texto = $_GET['buscar'] ?? '';
$filtro_ubicacion = $_GET['ubicacion'] ?? '';
$ver_resp = isset($_GET['col_resp']) || !isset($_GET['filtrar']);
$ver_precio = isset($_GET['col_precio']) || !isset($_GET['filtrar']);
$ver_fecha = isset($_GET['col_fecha']) || !isset($_GET['filtrar']);
$ver_obs = isset($_GET['col_obs']) || !isset($_GET['filtrar']);

$url = "http://localhost/api_ceti/public/index.php/activos?buscar=" . urlencode($busqueda_texto) . "&ubicacion=" . urlencode($filtro_ubicacion);
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$activos = $resultado['data'] ?? [];

$url_base = "http://localhost/api_ceti/public/index.php/activos";
$res_base = @file_get_contents($url_base);
$json_base = json_decode($res_base, true);
$todas_las_ubicaciones = array_unique(array_column($json_base['data'] ?? [], 'ubicacion'));
sort($todas_las_ubicaciones);

function nombreEstado($id) {
    $estados = [1 => "Bueno", 2 => "Regular", 3 => "Malo", 4 => "Para desechar"];
    return $estados[$id] ?? "N/A";
}
$params_reporte = $_SERVER['QUERY_STRING'] ?? '';

include 'header.php'; 
?>

<div class="d-flex align-items-center justify-content-center mb-4 p-3 bg-white shadow-sm rounded-3 border-top border-4 border-danger">
    <img src="img/logo.png" alt="Logo CEETII" style="max-height: 75px; margin-right: 25px;">
    <div class="text-start">
        <h2 class="mb-0 fw-bold text-dark" style="letter-spacing: -1px; line-height: 1;">SISTEMA DE ACTIVOS</h2>
        <p class="text-muted mb-0 small text-uppercase" style="letter-spacing: 1px;">Gestión e Inventarios - CEETII</p>
    </div>
</div>

<div class="card shadow mb-4 border-0"> 
    <div class="card-body p-4">
        <h5 class="card-title mb-3 text-dark fw-bold">Buscador de Activos</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Palabra clave:</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..." value="<?= htmlspecialchars($busqueda_texto) ?>">
            </div>
            
            <div class="col-md-4">
                <label class="form-label small fw-bold">Ubicación:</label>
                <select name="ubicacion" class="form-select">
                    <option value="">-- Todas --</option>
                    <?php foreach($todas_las_ubicaciones as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>" <?= ($filtro_ubicacion == $u) ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <input type="hidden" name="filtrar" value="1">
                <button type="submit" class="btn btn-dark w-100">Filtrar</button>
                <a href="activos.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>

            <div class="col-12 mt-3 pt-3 border-top">
                <p class="mb-1 fw-bold text-muted small">Configurar columnas de la tabla:</p>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="col_resp" id="c1" <?= $ver_resp ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="c1">Responsable</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="col_precio" id="c2" <?= $ver_precio ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="c2">Precio</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="col_fecha" id="c3" <?= $ver_fecha ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="c3">Fecha</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="col_obs" id="c4" <?= $ver_obs ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="c4">Obs.</label>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between mb-3">
    <a href="crear.php" class="btn btn-success shadow-sm px-4">
        <span class="material-icons align-middle me-1">add_box</span> Nuevo Activo
    </a>
    <div>
        <a href="reporte.php?<?= $params_reporte ?>" target="_blank" class="btn btn-outline-danger btn-sm">PDF</a>
        <a href="excel.php?<?= $params_reporte ?>" class="btn btn-outline-success btn-sm">EXCEL</a>
    </div>
</div>

<div class="card shadow border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Nombre del Activo</th>
                    <th>Código</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <?php if($ver_resp): ?> <th>Responsable</th> <?php endif; ?>
                    <?php if($ver_precio): ?> <th>Precio</th> <?php endif; ?>
                    <?php if($ver_fecha): ?> <th>Registro</th> <?php endif; ?>
                    <?php if($ver_obs): ?> <th>Obs.</th> <?php endif; ?>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($activos as $a): ?>
                <tr>
                    <td class="ps-3 fw-bold"><?= htmlspecialchars($a['nombre']) ?></td>
                    <td><span class="text-muted small"><?= htmlspecialchars($a['codigo_activo']) ?></span></td>
                    <td><?= htmlspecialchars($a['ubicacion']) ?></td>
                    <td>
                        <?php 
                            $color = match($a['estado_id']) { 1 => 'success', 2 => 'warning', 3 => 'danger', default => 'secondary' };
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= nombreEstado($a['estado_id']) ?></span>
                    </td>
                    <?php if($ver_resp): ?> <td><?= htmlspecialchars($a['responsable']) ?></td> <?php endif; ?>
                    <?php if($ver_precio): ?> <td class="fw-bold">Bs. <?= number_format($a['precio_compra'], 2) ?></td> <?php endif; ?>
                    <?php if($ver_fecha): ?> <td class="small"><?= $a['fecha_registro'] ?></td> <?php endif; ?>
                    <?php if($ver_obs): ?> <td class="small italic text-muted"><?= htmlspecialchars($a['observaciones'] ?? '') ?></td> <?php endif; ?>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="editar.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <a href="eliminar.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')">Borrar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>