<?php 
include 'header.php'; 

$url_api = "http://localhost/api_ceti/public/index.php/activos";
$res_api = @file_get_contents($url_api);
$json_api = json_decode($res_api, true);
$activos_existentes = $json_api['data'] ?? [];

$nombres_unicos = array_unique(array_column($activos_existentes, 'nombre'));
sort($nombres_unicos);
$ubicaciones_unicas = array_unique(array_column($activos_existentes, 'ubicacion'));
sort($ubicaciones_unicas);
$codigos_existentes = array_column($activos_existentes, 'codigo_activo');
?>

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
    <img src="img/logo.png" alt="Logo" style="max-height: 70px; margin-right: 20px;">
    <div class="text-start">
        <h2 class="mb-0 fw-bold text-dark" style="letter-spacing: -1px;">REGISTRAR ACTIVO</h2>
        <p class="text-muted mb-0 small text-uppercase">Formulario - CEETII</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5"> 
        <div class="card shadow border-0 mb-5">
            <div class="card-body p-4">
                <form method="POST" action="guardar.php" autocomplete="off">
                    
                    <div class="mb-3 sugerencias-container">
                        <label class="form-label fw-bold small text-secondary">Nombre del Activo</label>
                        <input type="text" id="nombre_input" class="form-control form-control-sm" placeholder="Laptop Nitro..." required autofocus oninput="validarTexto(this, 'letras')">
                        <input type="hidden" name="nombre" id="nombre_real">
                        <div id="lista_nom" class="lista-desplegable">
                            <?php foreach($nombres_unicos as $nom): ?>
                                <div class="opcion-item" onclick="seleccionar('nombre', '<?= addslashes(htmlspecialchars($nom)) ?>')"><?= htmlspecialchars($nom) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3 sugerencias-container">
                        <label class="form-label fw-bold small text-secondary">Ubicación</label>
                        <input type="text" id="ubicacion_input" class="form-control form-control-sm" placeholder="Aula 10..." required oninput="validarTexto(this, 'numeros')">
                        <input type="hidden" name="ubicacion" id="ubicacion_real">
                        <div id="lista_ubi" class="lista-desplegable">
                            <?php foreach($ubicaciones_unicas as $ubi): ?>
                                <div class="opcion-item" onclick="seleccionar('ubicacion', '<?= addslashes(htmlspecialchars($ubi)) ?>')"><?= htmlspecialchars($ubi) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Código</label>
                        <input type="text" name="codigo_activo" id="codigo_preview" class="form-control form-control-sm bg-light fw-bold text-danger" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Precio (Bs.)</label>
                        <input type="text" id="precio_visual" class="form-control form-control-sm" placeholder="0" oninput="formatearMiles(this)">
                        <input type="hidden" name="precio_compra" id="precio_real">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Estado</label>
                        <select name="estado_id" class="form-select form-select-sm">
                            <option value="1">Bueno</option>
                            <option value="2">Regular</option>
                            <option value="3">Malo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Responsable</label>
                        <input type="text" name="responsable" class="form-control form-control-sm" oninput="validarTexto(this, 'letras')">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Observaciones</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2" oninput="validarTexto(this, 'completo')"></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-dark py-2">Guardar Activo</button>
                        <a href="activos.php" class="btn btn-outline-secondary py-2">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const codigosDB = <?= json_encode($codigos_existentes) ?>;

function validarTexto(input, tipo) {
    let regex;
    if (tipo === 'completo') {
        regex = /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ .,-]/g;
    } else if (tipo === 'numeros') {
        regex = /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]/g;
    } else {
        regex = /[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g;
    }
    input.value = input.value.replace(regex, '');
    input.value = input.value.replace(/  +/g, ' ');
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
        hidden.value = this.value;
        generarCodigo();
    });
    input.addEventListener('click', () => { if(input.value.length > 0) lista.style.display = 'block'; });
}

configurarBuscador('nombre_input', 'lista_nom', 'nombre_real');
configurarBuscador('ubicacion_input', 'lista_ubi', 'ubicacion_real');

function seleccionar(tipo, valor) {
    document.getElementById(tipo + '_input').value = valor;
    document.getElementById(tipo + '_real').value = valor;
    document.getElementById('lista_' + (tipo === 'nombre' ? 'nom' : 'ubi')).style.display = 'none';
    generarCodigo();
}

function generarCodigo() {
    const nomFull = document.getElementById('nombre_real').value.trim().toUpperCase();
    const ubiFull = document.getElementById('ubicacion_real').value.trim().toUpperCase();

    if(nomFull !== "" && ubiFull !== "") {
        let nomCod = "";
        const palabras = nomFull.split(' ').filter(p => p.length > 2);
        if (palabras.length >= 2) {
            nomCod = palabras[0].substring(0, 2) + palabras[1].charAt(0);
        } else {
            if(nomFull.length >= 3) {
                nomCod = nomFull.substring(0, 2) + nomFull.slice(-1);
            } else {
                nomCod = nomFull.padEnd(3, 'X');
            }
        }
        let ubiLetra = ubiFull.charAt(0);
        let ubiNum = ubiFull.replace(/[^0-9]/g, ''); 
        let ubiCod = ubiLetra + ubiNum;
        const prefijo = ubiCod + "-" + nomCod;
        const correlativo = codigosDB.filter(c => c.startsWith(prefijo)).length + 1;
        document.getElementById('codigo_preview').value = prefijo + "-" + correlativo.toString().padStart(2, '0');
    } else {
        document.getElementById('codigo_preview').value = '';
    }
}

function formatearMiles(input) {
    let valor = input.value.replace(/\D/g, "");
    if (valor.length > 8) valor = valor.slice(0, 8);
    document.getElementById('precio_real').value = valor;
    input.value = valor ? new Intl.NumberFormat('de-DE').format(valor) : "";
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.sugerencias-container')) {
        document.getElementById('lista_nom').style.display = 'none';
        document.getElementById('lista_ubi').style.display = 'none';
    }
});
</script>

<?php include 'footer.php'; ?>