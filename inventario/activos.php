<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
date_default_timezone_set('America/La_Paz');

$busqueda_texto = $_GET['buscar'] ?? '';
$filtro_ubicacion = $_GET['ubicacion'] ?? '';

$ver_resp = isset($_GET['col_resp']) || !isset($_GET['filtrar']);
$ver_precio = isset($_GET['col_precio']) || !isset($_GET['filtrar']);
$ver_fecha_compra = isset($_GET['col_fecha_c']) || !isset($_GET['filtrar']);
$ver_fecha = isset($_GET['col_fecha']) || !isset($_GET['filtrar']);
$ver_obs = isset($_GET['col_obs']) || !isset($_GET['filtrar']);

$por_pagina = 10;
$pagina_actual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$url = API_BASE_URL . "/activos?buscar=" . urlencode($busqueda_texto) . "&ubicacion=" . urlencode($filtro_ubicacion);
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$todos_los_activos = $resultado['data'] ?? [];

$todos_los_activos = array_reverse($todos_los_activos);

$total_activos = count($todos_los_activos);
$total_paginas = ceil($total_activos / $por_pagina);
$indice_inicio = ($pagina_actual - 1) * $por_pagina;
$activos = array_slice($todos_los_activos, $indice_inicio, $por_pagina);

$url_base = API_BASE_URL . "/activos";
$res_base = @file_get_contents($url_base);
$json_base = json_decode($res_base, true);
$todas_las_ubicaciones = array_unique(array_column($json_base['data'] ?? [], 'ubicacion'));
sort($todas_las_ubicaciones);

function nombreEstado($id) {
    $estados = [1 => "Bueno", 2 => "Regular", 3 => "Malo", 4 => "Para desechar"];
    return $estados[$id] ?? "N/A";
}

$query_params = $_GET;
unset($query_params['p']); 
$url_filtros = http_build_query($query_params);
$params_reporte = $_SERVER['QUERY_STRING'] ?? '';

include 'header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                    <input class="form-check-input" type="checkbox" name="col_fecha_c" id="c_fc" <?= $ver_fecha_compra ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="c_fc">Fecha Compra</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="col_fecha" id="c3" <?= $ver_fecha ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="c3">Fecha Registro</label>
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
    <div class="d-flex gap-2">
        <a href="crear.php" class="btn btn-success shadow-sm px-4">
            <span class="material-icons align-middle me-1">add_box</span> Nuevo Activo
        </a>
        <button id="btnOrden" class="btn btn-outline-dark shadow-sm px-3" onclick="invertirTabla()">
            <span class="material-icons align-middle me-1">sort</span> Antiguos primero
        </button>
    </div>
    <div>
        <a href="reporte.php?<?= $params_reporte ?>" target="_blank" class="btn btn-outline-danger btn-sm">PDF</a>
        <a href="excel.php?<?= $params_reporte ?>" class="btn btn-outline-success btn-sm">EXCEL</a>
    </div>
</div>

<div id="seccion-tabla" class="card shadow border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaActivos">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Nombre del Activo</th>
                    <th>Código</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <?php if($ver_resp): ?> <th>Responsable</th> <?php endif; ?>
                    <?php if($ver_precio): ?> <th>Precio</th> <?php endif; ?>
                    <?php if($ver_fecha_compra): ?> <th>F. Compra</th> <?php endif; ?>
                    <?php if($ver_fecha): ?> <th>Registro</th> <?php endif; ?>
                    <?php if($ver_obs): ?> <th>Obs.</th> <?php endif; ?>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($activos as $a): ?>
                <tr>
                    <td class="ps-3 fw-bold"><?= htmlspecialchars($a['nombre']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($a['codigo_activo']) ?></span></td>
                    <td><?= htmlspecialchars($a['ubicacion']) ?></td>
                    <td>
                        <?php $color = match((int)$a['estado_id']) { 1 => 'success', 2 => 'warning', 3 => 'danger', default => 'secondary' }; ?>
                        <span class="badge bg-<?= $color ?>"><?= nombreEstado($a['estado_id']) ?></span>
                    </td>
                    <?php if($ver_resp): ?> <td><?= htmlspecialchars($a['responsable']) ?></td> <?php endif; ?>
                    <?php if($ver_precio): ?> <td class="fw-bold">Bs. <?= number_format($a['precio_compra'] ?? 0, 2) ?></td> <?php endif; ?>
                    
                    <?php if($ver_fecha_compra): ?> 
                        <td>
                            <?php 
                                $f_compra = $a['fecha_compra'] ?? null;
                                if($f_compra && $f_compra !== '0000-00-00' && $f_compra !== '1970-01-01'): 
                                    $ts = strtotime($f_compra);
                                    echo $ts ? date('d/m/Y', $ts) : htmlspecialchars($f_compra);
                                else: 
                                    echo '<span class="text-muted small">Sin fecha</span>';
                                endif; 
                            ?>
                        </td> 
                    <?php endif; ?>

                    <?php if($ver_fecha): ?> <td class="small"><?= $a['fecha_registro'] ?></td> <?php endif; ?>
                    <?php if($ver_obs): ?> <td class="small italic text-muted"><?= htmlspecialchars($a['observaciones'] ?? '') ?></td> <?php endif; ?>
                    
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="editar.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar('<?= $a['id'] ?>', '<?= htmlspecialchars($a['nombre']) ?>')">Borrar</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_paginas > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
            <a class="page-link text-dark" href="?p=<?= $pagina_actual - 1 ?>&<?= $url_filtros ?>#seccion-tabla">Anterior</a>
        </li>
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                <a class="page-link <?= ($pagina_actual == $i) ? 'bg-danger border-danger text-white' : 'text-dark' ?>" href="?p=<?= $i ?>&<?= $url_filtros ?>#seccion-tabla"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link text-dark" href="?p=<?= $pagina_actual + 1 ?>&<?= $url_filtros ?>#seccion-tabla">Siguiente</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script>
let ordenDescendente = true; 
function invertirTabla() {
    const tableBody = document.querySelector("#tablaActivos tbody");
    const filas = Array.from(tableBody.querySelectorAll("tr"));
    const btn = document.getElementById("btnOrden");
    filas.reverse();
    tableBody.innerHTML = "";
    filas.forEach(f => tableBody.appendChild(f));
    ordenDescendente = !ordenDescendente;
    btn.innerHTML = ordenDescendente 
        ? '<span class="material-icons align-middle me-1">sort</span> Antiguos primero' 
        : '<span class="material-icons align-middle me-1">sort</span> Nuevos primero';
}

function confirmarEliminar(id, nombre) {
    Swal.fire({
        title: '¿Eliminar Activo?',
        text: `¿Estás seguro de que quieres borrar "${nombre}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, borrar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `eliminar.php?id=${id}`;
        }
    });
}
</script>

<?php include 'footer.php'; ?>