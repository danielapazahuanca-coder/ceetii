<?php
include 'header.php'; 

$url_activos = "http://localhost/api_ceti/public/index.php/activos";
$url_prestamos = "http://localhost/api_ceti/public/index.php/prestamos";

$res_activos = @file_get_contents($url_activos);
$activos = json_decode($res_activos, true)['data'] ?? [];

$res_prestamos = @file_get_contents($url_prestamos);
$lista_prestamos = json_decode($res_prestamos, true)['data'] ?? [];

if (!empty($lista_prestamos)) {
    usort($lista_prestamos, function($a, $b) {
        return $b['id'] <=> $a['id'];
    });
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="card shadow border-0 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-dark">GESTIÓN DE PRÉSTAMOS</h4>
            <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
                <span class="material-icons align-middle me-1">add_circle</span> Nuevo Préstamo
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Activos Prestados</th>
                        <th>Solicitante</th>
                        <th>CI / Doc.</th>
                        <th>Fecha Préstamo</th>
                        <th>Estado / Retorno</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_prestamos as $p): ?>
                    <tr>
                        <td>
                            <?php 
                            $activos_lista = explode(', ', $p['nombre_activo'] ?? ''); 
                            foreach($activos_lista as $item): 
                                if(empty($item)) continue;
                                $partes = explode('x ', $item);
                                $cant = $partes[0];
                                $nom = $partes[1] ?? $item;
                            ?>
                                <div class="mb-1">
                                    <span class="badge bg-white text-dark border shadow-sm p-2" style="font-size: 0.85rem;">
                                        <b class="text-danger"><?= htmlspecialchars($cant) ?></b> <span class="text-muted">|</span> <?= htmlspecialchars($nom) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($p['solicitante']) ?></td>
                        <td><?= htmlspecialchars($p['documento_identidad']) ?></td>
                        <td class="small text-muted"><?= date('d/m/Y', strtotime($p['fecha_prestamo'])) ?></td>
                        <td>
                            <?php if(empty($p['fecha_devolucion'])): ?>
                                <span class="badge bg-warning text-dark w-100 py-2">PENDIENTE</span>
                            <?php else: ?>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-success w-100 py-2 mb-1">DEVUELTO</span>
                                    <small class="text-muted fw-bold text-center" style="font-size: 0.72rem;">
                                        <?= date('d/m/Y', strtotime($p['fecha_devolucion'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center" style="width: 180px;">
                            <div class="d-flex flex-column gap-1">
                                <a href="generar_comprobante.php?id=<?= $p['id'] ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center py-2" 
                                   title="PDF">
                                    <span class="material-icons me-1" style="font-size: 1rem;">picture_as_pdf</span> PDF Comprobante
                                </a>

                                <?php if(empty($p['fecha_devolucion'])): ?>
                                    <button onclick="devolverActivo(<?= $p['id'] ?>)" 
                                            class="btn btn-sm btn-success d-flex align-items-center justify-content-center py-2 shadow-sm" 
                                            title="Devolver">
                                        <span class="material-icons me-1" style="font-size: 1rem;">assignment_return</span> Recibir Devolución
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light border text-muted py-2" disabled>
                                        <span class="material-icons align-middle" style="font-size: 1rem;">done_all</span> Finalizado
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

<div class="modal fade" id="modalPrestamo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form action="guardar_prestamo.php" method="POST" id="formPrestamo">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title d-flex align-items-center">
                        <span class="material-icons me-2">history_edu</span> Registrar Nuevo Préstamo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">NOMBRE DEL SOLICITANTE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><span class="material-icons text-muted" style="font-size: 1.2rem;">person</span></span>
                                <input type="text" name="solicitante" id="solicitante_input" class="form-control border-start-0" placeholder="Ej. Juan Perez" maxlength="60" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''); capitalizarPalabras(this);">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">CI / DOCUMENTO</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><span class="material-icons text-muted" style="font-size: 1.2rem;">badge</span></span>
                                <input type="text" name="documento_identidad" class="form-control border-start-0" placeholder="Solo números" maxlength="12" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 d-flex align-items-center">
                                <span class="material-icons me-1 text-primary">inventory_2</span> Selección de Activos
                            </h6>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-7">
                                    <label class="form-label small text-muted">Buscar Activo</label>
                                    <select id="sel_activo" class="form-select border-primary shadow-sm">
                                        <option value="">-- Seleccione un activo disponible --</option>
                                        <?php foreach($activos as $a): ?>
                                            <option value="<?= $a['id'] ?>" data-nombre="<?= htmlspecialchars($a['nombre']) ?>">
                                                <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['codigo_activo']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Cantidad</label>
                                    <input type="number" id="sel_cantidad" class="form-control border-primary shadow-sm" value="1" min="1">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" onclick="agregarALista()" class="btn btn-primary w-100 shadow-sm">
                                        <span class="material-icons">add</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold small text-secondary">RESUMEN DEL PRÉSTAMO</label>
                        <div class="table-responsive border rounded bg-white">
                            <table class="table table-sm table-borderless align-middle mb-0 text-center">
                                <thead class="bg-dark text-white small">
                                    <tr>
                                        <th class="py-2">Activo</th>
                                        <th class="py-2" width="100">Cantidad</th>
                                        <th class="py-2" width="50"></th>
                                    </tr>
                                </thead>
                                <tbody id="tabla_items">
                                    <tr><td colspan="3" class="text-center text-muted small py-4" id="lista_vacia">No hay activos añadidos al carrito.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4 shadow">
                        <span class="material-icons align-middle me-1">save</span> Confirmar Préstamo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let items = [];

function capitalizarPalabras(input) {
    let palabras = input.value.split(" ");
    for (let i = 0; i < palabras.length; i++) {
        if (palabras[i].length > 0) {
            palabras[i] = palabras[i].charAt(0).toUpperCase() + palabras[i].slice(1).toLowerCase();
        }
    }
    input.value = palabras.join(" ");
}

function agregarALista() {
    const select = document.getElementById('sel_activo');
    const activoId = select.value;
    const nombre = select.options[select.selectedIndex].getAttribute('data-nombre');
    const cantidad = document.getElementById('sel_cantidad').value;

    if(!activoId) return alert("Por favor, seleccione un activo de la lista.");

    let existe = items.find(i => i.activo_id === activoId);
    if(existe) {
        existe.cantidad = parseInt(existe.cantidad) + parseInt(cantidad);
    } else {
        items.push({ activo_id: activoId, nombre: nombre, cantidad: cantidad });
    }

    renderLista();
    select.value = "";
    document.getElementById('sel_cantidad').value = 1;
}

function eliminarDeLista(index) {
    items.splice(index, 1);
    renderLista();
}

function renderLista() {
    const tbody = document.getElementById('tabla_items');
    tbody.innerHTML = "";
    
    if(items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted small py-4">No hay activos añadidos al carrito.</td></tr>';
        return;
    }

    items.forEach((item, index) => {
        tbody.innerHTML += `
            <tr class="border-bottom">
                <td class="text-start ps-3 fw-bold text-dark">${item.nombre} <input type="hidden" name="items[${index}][activo_id]" value="${item.activo_id}"></td>
                <td><span class="badge bg-danger fs-6">${item.cantidad}</span> <input type="hidden" name="items[${index}][cantidad]" value="${item.cantidad}"></td>
                <td><button type="button" onclick="eliminarDeLista(${index})" class="btn btn-sm text-danger"><span class="material-icons">delete_outline</span></button></td>
            </tr>
        `;
    });
}

document.getElementById('formPrestamo').addEventListener('submit', function(e) {
    e.preventDefault();

    if(items.length === 0) {
        return Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: 'Debe agregar al menos un activo al resumen.',
            confirmButtonColor: '#d33'
        });
    }

    const solicitante = document.getElementById('solicitante_input').value;
    let listaHtml = '<div class="text-start small mt-2"><ul>';
    items.forEach(item => {
        listaHtml += `<li><b>${item.cantidad}x</b> ${item.nombre}</li>`;
    });
    listaHtml += '</ul></div>';

    Swal.fire({
        title: '¿Confirmar Préstamo?',
        html: `<div class="text-start small">
                <b>Solicitante:</b> ${solicitante}<br>
                <b>Activos a entregar:</b>
               </div> ${listaHtml}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8d191d',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, registrar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

function devolverActivo(id) {
    Swal.fire({
        title: '¿Confirmar Devolución?',
        text: "Se marcarán todos los activos de este préstamo como devueltos.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, recibir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `devolver_prestamo.php?id=${id}`;
        }
    });
}
</script>
<?php include 'footer.php'; ?>