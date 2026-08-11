<?php
session_start();
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2], true)) {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/config_api.php';
$id_url = $_GET['id'] ?? null;
if (!$id_url) { header("Location: activos.php"); exit; }

$url = API_BASE_URL . "/activos"; 
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

$nombres_unicos = array_unique(array_column($todos_los_activos, 'nombre'));
sort($nombres_unicos);

$ubicaciones_unicas = array_unique(array_column($todos_los_activos, 'ubicacion'));
sort($ubicaciones_unicas);

$responsables_unicos = array_unique(array_column($todos_los_activos, 'responsable'));
$responsables_unicos = array_filter($responsables_unicos); 
sort($responsables_unicos);

include 'header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .sugerencias-container { position: relative; }
    .lista-desplegable {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
        max-height: 150px; overflow-y: auto; background: white;
        border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: none;
    }
    .opcion-item { padding: 7px 12px; cursor: pointer; font-size: 0.85rem; border-bottom: 1px solid #f1f1f1; color: #333; }
    .opcion-item:hover { background-color: #f8f9fa; color: #dc3545; }
</style>

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
                <h6 class="fw-bold">Activo no encontrado</h6>
                <a href="activos.php" class="btn btn-sm btn-outline-secondary">Volver</a>
            </div>
        <?php else: ?>
            <div class="card shadow border-0 mb-5">
                <div class="card-body p-4">
                    <form method="POST" action="actualizar.php" id="formEditar" autocomplete="off">
                        <input type="hidden" name="id" value="<?= $activo['id'] ?>">

                        <div class="mb-3 sugerencias-container">
                            <label class="form-label fw-bold small text-secondary">Nombre del Activo</label>
                            <input type="text" id="nombre_input" class="form-control" 
                                   value="<?= htmlspecialchars($activo['nombre'] ?? '') ?>" 
                                   required maxlength="25" oninput="validarTexto(this, 'solo_letras')">
                            <input type="hidden" name="nombre" id="nombre_real" value="<?= htmlspecialchars($activo['nombre'] ?? '') ?>">
                            <div id="lista_nom" class="lista-desplegable">
                                <?php foreach($nombres_unicos as $nom): ?>
                                    <div class="opcion-item" onclick="seleccionar('nombre', '<?= addslashes(htmlspecialchars($nom)) ?>')"><?= htmlspecialchars($nom) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Código (No editable)</label>
                            <input type="text" name="codigo_activo" id="codigo_edit" class="form-control bg-light text-muted fw-bold" 
                                   value="<?= htmlspecialchars($activo['codigo_activo'] ?? '') ?>" readonly>
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

                        <div class="mb-3 sugerencias-container">
                            <label class="form-label fw-bold small text-secondary">Ubicación</label>
                            <input type="text" id="ubicacion_input" class="form-control" 
                                   value="<?= htmlspecialchars($activo['ubicacion'] ?? '') ?>" 
                                   maxlength="20" oninput="validarTexto(this, 'numeros')">
                            <input type="hidden" name="ubicacion" id="ubicacion_real" value="<?= htmlspecialchars($activo['ubicacion'] ?? '') ?>">
                            <div id="lista_ubi" class="lista-desplegable">
                                <?php foreach($ubicaciones_unicas as $ubi): ?>
                                    <div class="opcion-item" onclick="seleccionar('ubicacion', '<?= addslashes(htmlspecialchars($ubi)) ?>')"><?= htmlspecialchars($ubi) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3 sugerencias-container">
                            <label class="form-label fw-bold small text-secondary">Responsable</label>
                            <input type="text" id="responsable_input" class="form-control" 
                                   value="<?= htmlspecialchars($activo['responsable'] ?? '') ?>" 
                                   maxlength="25" oninput="validarTexto(this, 'letras')">
                            <input type="hidden" name="responsable" id="responsable_real" value="<?= htmlspecialchars($activo['responsable'] ?? '') ?>">
                            <div id="lista_resp" class="lista-desplegable">
                                <?php foreach($responsables_unicos as $resp): ?>
                                    <div class="opcion-item" onclick="seleccionar('responsable', '<?= addslashes(htmlspecialchars($resp)) ?>')"><?= htmlspecialchars($resp) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-secondary">Precio (Bs.)</label>
                                <input type="number" step="0.01" name="precio_compra" class="form-control" 
                                       value="<?= $activo['precio_compra'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-secondary">Fecha de Compra</label>
                                <input type="date" name="fecha_compra" class="form-control" 
                                       value="<?= $activo['fecha_compra'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" 
                                      maxlength="50" oninput="validarTexto(this, 'observacion_format')"><?= htmlspecialchars($activo['observaciones'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-2 border-top pt-3">
                            <button type="button" onclick="confirmarActualizacion()" class="btn btn-dark py-2">Actualizar Cambios</button>
                            <a href="activos.php" class="btn btn-outline-secondary py-2">Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmarActualizacion() {
    const nombre = document.getElementById('nombre_real').value;
    const codigo = document.getElementById('codigo_edit').value;

    if(!nombre.trim()){
        Swal.fire('Atención', 'El nombre es obligatorio.', 'warning');
        return;
    }

    Swal.fire({
        title: '¿Guardar Cambios?',
        text: `Se actualizará el activo: ${nombre} (${codigo})`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#2d3436',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formEditar').submit();
        }
    });
}

function validarTexto(input, tipo) {
    let regex;
    if (tipo === 'observacion_format') regex = /[^a-zA-ZáéíóúÁÉÍÓÚñÑ ,.]/g;
    else if (tipo === 'numeros') regex = /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]/g; 
    else regex = /[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g; 
    
    let valor = input.value.replace(regex, '').replace(/\s+/g, ' ');

    if (tipo === 'observacion_format') {
        if (valor.length > 0) {
            valor = valor.charAt(0).toUpperCase() + valor.slice(1).toLowerCase();
        }
    } else {
        const excepciones = ['de', 'del', 'la', 'las', 'el', 'los', 'con', 'en', 'y', 'o'];
        let palabras = valor.toLowerCase().split(' ');
        for (let i = 0; i < palabras.length; i++) {
            if (i === 0 || !excepciones.includes(palabras[i])) {
                palabras[i] = palabras[i].charAt(0).toUpperCase() + palabras[i].slice(1);
            }
        }
        valor = palabras.join(' ');
    }
    
    input.value = valor;

    const idReal = input.id.replace('_input', '_real');
    const hidden = document.getElementById(idReal);
    if(hidden) hidden.value = valor;
}

function configurarBuscador(inputId, listaId, realId) {
    const input = document.getElementById(inputId);
    const lista = document.getElementById(listaId);
    const hidden = document.getElementById(realId);
    const items = lista.querySelectorAll('.opcion-item');

    input.addEventListener('input', function() {
        const filtro = this.value.toLowerCase();
        lista.style.display = filtro.length > 0 ? 'block' : 'none';
        items.forEach(item => {
            item.style.display = item.textContent.toLowerCase().includes(filtro) ? 'block' : 'none';
        });
    });

    input.addEventListener('click', function() {
        if(this.value.length > 0) lista.style.display = 'block';
    });
}

configurarBuscador('nombre_input', 'lista_nom', 'nombre_real');
configurarBuscador('ubicacion_input', 'lista_ubi', 'ubicacion_real');
configurarBuscador('responsable_input', 'lista_resp', 'responsable_real');

function seleccionar(tipo, valor) {
    document.getElementById(tipo + '_input').value = valor;
    document.getElementById(tipo + '_real').value = valor;
    const mapaListas = { 'nombre': 'lista_nom', 'ubicacion': 'lista_ubi', 'responsable': 'lista_resp' };
    document.getElementById(mapaListas[tipo]).style.display = 'none';
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.sugerencias-container')) {
        document.querySelectorAll('.lista-desplegable').forEach(l => l.style.display = 'none');
    }
});
</script>

<?php include 'footer.php'; ?>