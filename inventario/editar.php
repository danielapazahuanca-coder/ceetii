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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nombre del Activo</label>
                            <input type="text" name="nombre" id="nombre_edit" class="form-control" 
                                   value="<?= htmlspecialchars($activo['nombre'] ?? '') ?>" 
                                   required oninput="validarTexto(this, 'letras')">
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

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control" 
                                   value="<?= htmlspecialchars($activo['ubicacion'] ?? '') ?>" oninput="validarTexto(this, 'numeros')">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Responsable</label>
                            <input type="text" name="responsable" class="form-control" 
                                   value="<?= htmlspecialchars($activo['responsable'] ?? '') ?>" oninput="validarTexto(this, 'letras')">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Precio (Bs.)</label>
                            <input type="number" step="0.01" name="precio_compra" class="form-control" 
                                   value="<?= $activo['precio_compra'] ?? '' ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" oninput="validarTexto(this, 'completo')"><?= htmlspecialchars($activo['observaciones'] ?? '') ?></textarea>
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
    const nombre = document.getElementById('nombre_edit').value;
    const codigo = document.getElementById('codigo_edit').value;

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
    if (tipo === 'completo') {
        regex = /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ .,-]/g;
    } else if (tipo === 'numeros') {
        regex = /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]/g;
    } else {
        regex = /[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g;
    }
    
    let valor = input.value.replace(regex, '');
    valor = valor.replace(/\s+/g, ' ');

    const excepciones = ['de', 'del', 'la', 'las', 'el', 'los', 'con', 'en', 'y', 'o'];
    let palabras = valor.toLowerCase().split(' ');

    for (let i = 0; i < palabras.length; i++) {
        if (i === 0 || !excepciones.includes(palabras[i])) {
            palabras[i] = palabras[i].charAt(0).toUpperCase() + palabras[i].slice(1);
        }
    }
    input.value = palabras.join(' ');
}
</script>

<?php include 'footer.php'; ?>