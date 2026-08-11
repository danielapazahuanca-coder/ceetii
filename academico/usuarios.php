<?php
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header("Location: ../login.php"); exit();
}
require_once 'functions.php';
require_once '_layout.php';

$msg = '';
$api = API_BASE_URL . "/auth/users";

if (isset($_POST['create_user'])) {
    $ci = trim($_POST['carnet_ci']);
    $email = trim($_POST['email_completo']);
    
    $role_id = (!empty($_POST['role_id'])) ? (int)$_POST['role_id'] : null;
    
    $payload = [
        'username' => $ci, 
        'password' => $ci,
        'name'     => trim($_POST['name']),
        'emailid'  => strtolower($email),
        'sucursal' => $_POST['sucursal'],
        'role_id'  => $role_id
    ];
    
    $res = api_post($api, $payload);
    if ($res && $res['status'] === 'success') {
        $msg = ok('Personal registrado con éxito. Su contraseña inicial es su C.I.: <b>' . htmlspecialchars($ci) . '</b>');
    } else {
        $msg = err($res['message'] ?? 'No se pudo crear el usuario.');
    }
}

if (isset($_POST['update_user'])) {
    $id = (int)$_POST['id_user'];
    
    $role_id = (!empty($_POST['role_id'])) ? (int)$_POST['role_id'] : null;

    $payload = [
        'username' => trim($_POST['username']),
        'name'     => trim($_POST['name']),
        'emailid'  => trim($_POST['emailid']),
        'sucursal' => $_POST['sucursal'],
        'role_id'  => $role_id
    ];
    api_put("{$api}/{$id}", $payload);
    $msg = ok('Información de usuario actualizada correctamente.');
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $res = api_delete("{$api}/" . (int)$_GET['id']);
    $msg = ($res && $res['status'] === 'success') ? ok($res['message']) : err($res['message'] ?? 'No se pudo eliminar al usuario.');
}

$data     = api_get($api);
$usuarios = ($data && $data['status'] === 'success') ? $data['data'] : [];

$total_todos = count($usuarios);
$total_admin = count(array_filter($usuarios, fn($u) => (int)($u['role_id'] ?? 0) === 1));
$total_secre = count(array_filter($usuarios, fn($u) => (int)($u['role_id'] ?? 0) === 2));
$total_doce  = count(array_filter($usuarios, fn($u) => (int)($u['role_id'] ?? 0) === 3));
$total_sin_rol = count(array_filter($usuarios, fn($u) => empty($u['role_id'])));

$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Usuarios y Roles', $current_page, 1);
?>
<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-pantalla); color: var(--rojo-elegante);">
                <span class="material-icons fs-1 d-block">manage_accounts</span>
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro);">Control de Usuarios y Roles</h4>
                <p class="text-muted small mb-0">El número de C.I. corresponde al usuario y clave inicial.</p>
            </div>
        </div>
        <div>
            <button class="btn btn-success px-4 py-2 d-flex align-items-center fw-medium" onclick="abrirCrear()" style="border-radius: 8px;">
                <span class="material-icons me-2 fs-5">person_add</span> Registrar Personal
            </button>
        </div>
    </div>
</div>

<?php echo $msg; ?>

<style>
.nav-tabs-roles { border-bottom: 2px solid var(--rojo-elegante, #8d191d); }
.nav-tabs-roles .nav-link {
    color: var(--texto-oscuro, #2d3436); font-weight: 600;
    border: 1px solid #e3e6f0; border-bottom: none;
    background-color: #fff; margin-right: 4px;
    border-radius: 6px 6px 0 0; transition: all 0.15s ease;
    cursor: pointer;
}
.nav-tabs-roles .nav-link:hover { background-color: #f8f9fc; }
.nav-tabs-roles .nav-link.active {
    background-color: var(--rojo-elegante, #8d191d) !important;
    color: #fff !important; border-color: var(--rojo-elegante, #8d191d) !important;
}
.nav-tabs-roles .nav-link.active .badge { background-color: #fff !important; color: var(--rojo-elegante, #8d191d) !important; }
.fila-usuario.d-none-row { display: none !important; }

/* El contenedor simula ser el input de Bootstrap */
.fake-input-container {
    display: flex;
    align-items: center;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 0.375rem 0.75rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    cursor: text;
}
.fake-input-container:focus-within {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.fake-input-container input {
    border: none !important;
    outline: none !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    width: 60px;
    min-width: 10px;
    color: #212529;
}
.fake-input-sufijo {
    color: #212529;
    font-weight: 500;
    margin-left: 2px;
    user-select: none;
    white-space: nowrap;
}
</style>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0">
        <ul class="nav nav-tabs-roles" id="tabs-roles">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-rol="todos"><span class="material-icons fs-6 me-1" style="vertical-align:middle;">groups</span>Todos <span class="badge bg-light text-dark border ms-1"><?= $total_todos; ?></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-rol="1"><span class="material-icons fs-6 me-1" style="vertical-align:middle;">admin_panel_settings</span>Administradores <span class="badge bg-light text-dark border ms-1"><?= $total_admin; ?></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-rol="2"><span class="material-icons fs-6 me-1" style="vertical-align:middle;">support_agent</span>Secretarias <span class="badge bg-light text-dark border ms-1"><?= $total_secre; ?></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-rol="3"><span class="material-icons fs-6 me-1" style="vertical-align:middle;">school</span>Docentes <span class="badge bg-light text-dark border ms-1"><?= $total_doce; ?></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-rol="sin_rol"><span class="material-icons fs-6 me-1" style="vertical-align:middle;">person_off</span>Sin Rol <span class="badge bg-light text-dark border ms-1"><?= $total_sin_rol; ?></span></a>
            </li>
        </ul>
    </div>
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead class="table-light text-muted small uppercase">
                <tr>
                    <th class="ps-4">Nombre Completo</th>
                    <th>Usuario (C.I.)</th>
                    <th>Correo Electrónico</th>
                    <th>Sucursal</th>
                    <th>Rol Asignado</th>
                    <th>Último Acceso</th>
                    <th class="text-end pe-4" style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpo-usuarios">
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay registros de personal disponibles.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): 
                        $role_val = !empty($u['role_id']) ? (int)$u['role_id'] : 'sin_rol';
                    ?>
                    <tr class="fila-usuario" data-rol="<?= $role_val; ?>">
                        <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($u['name']); ?></td>
                        <td><code class="px-2 py-1 bg-light rounded text-danger small fw-semibold"><?= htmlspecialchars($u['username']); ?></code></td>
                        <td class="text-muted small"><?= htmlspecialchars($u['emailid'] ?? '—'); ?></td>
                        <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($u['sucursal']); ?></span></td>
                        <td>
                            <?php 
                            if (!empty($u['role_id'])) {
                                echo rol_badge((int)$u['role_id']);
                            } else {
                                echo '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Sin Rol Asignado</span>';
                            }
                            ?>
                        </td>
                        <td class="text-muted small">
                            <?php
                            $ll = $u['lastlogin'] ?? '';
                            echo ($ll && $ll !== '0000-00-00 00:00:00') ? date('d/m/Y H:i', strtotime($ll)) : '<span class="text-black-50 fst-italic">Nunca ingresó</span>';
                            ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm d-inline-flex p-2 border-0" onclick='abrirEditar(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><span class="material-icons style-icon" style="font-size: 1.1rem;">edit</span></button>
                                <button class="btn btn-outline-danger btn-sm d-inline-flex p-2 border-0" onclick="confirmarEliminar(<?= (int)$u['id']; ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES); ?>')"><span class="material-icons style-icon" style="font-size: 1.1rem;">delete</span></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div id="aviso-sin-resultados" class="text-center py-4 text-muted d-none">
            <span class="material-icons fs-2 d-block mb-2 text-black-50">person_search</span>No hay personal registrado con este rol.
        </div>
    </div>
</div>

<!-- Modal Crear -->
<div id="modalCrear" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" id="formCrear">
                <div class="modal-header text-white border-0" style="background-color: var(--rojo-elegante);">
                    <h5 class="modal-title fw-bold d-flex align-items-center"><span class="material-icons me-2">person_add</span> Registrar Nuevo Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control p-2.5" style="border-radius: 8px;" placeholder="Ej: Juan Pérez Mamani" required maxlength="45">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Número de C.I. <span class="text-danger">*</span></label>
                        <input type="text" name="carnet_ci" class="form-control p-2.5" style="border-radius: 8px;" placeholder="Ej: 8439201" required maxlength="12">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Correo electrónico <span class="text-danger">*</span></label>
                        <div class="fake-input-container" onclick="document.getElementById('email_prefix').focus()">
                            <input type="text" id="email_prefix" placeholder="ejemplo" required maxlength="30">
                            <span class="fake-input-sufijo" id="sufijo_gmail">@gmail.com</span>
                            <input type="hidden" name="email_completo" id="email_completo">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted small">Sucursal <span class="text-danger">*</span></label>
                            <select name="sucursal" class="form-select p-2.5" style="border-radius: 8px;" required>
                                <option value="EA">El Alto (EA)</option>
                                <option value="LP">La Paz (LP)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted small">Rol asignado</label>
                            <select name="role_id" class="form-select p-2.5" style="border-radius: 8px;">
                                <option value="" selected>— Ninguno (Sin Rol) —</option>
                                <option value="1">Administrador</option>
                                <option value="2">Secretaria</option>
                                <option value="3">Docente</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" style="border-radius: 8px;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="create_user" class="btn btn-dark px-4" style="background-color: var(--rojo-elegante); border-color: var(--rojo-elegante); border-radius: 8px;">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center"><span class="material-icons me-2">edit</span> Actualizar Datos del Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_user" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nombre completo</label>
                        <input type="text" name="name" id="edit_name" class="form-control p-2.5" style="border-radius: 8px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Usuario de ingreso (Login)</label>
                        <input type="text" name="username" id="edit_username" class="form-control p-2.5" style="border-radius: 8px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Correo electrónico</label>
                        <input type="text" name="emailid" id="edit_emailid" class="form-control p-2.5" style="border-radius: 8px;">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted small">Sucursal</label>
                            <select name="sucursal" id="edit_sucursal" class="form-select p-2.5" style="border-radius: 8px;" required>
                                <option value="EA">El Alto (EA)</option>
                                <option value="LP">La Paz (LP)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted small">Rol</label>
                            <select name="role_id" id="edit_role" class="form-select p-2.5" style="border-radius: 8px;">
                                <option value="">— Ninguno (Sin Rol) —</option>
                                <option value="1">Administrador</option>
                                <option value="2">Secretaria</option>
                                <option value="3">Docente</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" style="border-radius: 8px;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="update_user" class="btn btn-primary px-4" style="border-radius: 8px;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let mCrear, mEditar;
document.addEventListener("DOMContentLoaded", function() {
    mCrear = new bootstrap.Modal(document.getElementById('modalCrear'));
    mEditar = new bootstrap.Modal(document.getElementById('modalEditar'));

    // Filtros de pestañas
    document.getElementById('tabs-roles').addEventListener('click', function(e) {
        const link = e.target.closest('.nav-link');
        if (!link) return; e.preventDefault();
        document.querySelectorAll('#tabs-roles .nav-link').forEach(a => a.classList.remove('active'));
        link.classList.add('active');
        const rolSeleccionado = link.dataset.rol;
        const filas = document.querySelectorAll('#cuerpo-usuarios .fila-usuario');
        let visibles = 0;
        filas.forEach(fila => {
            const coincide = (rolSeleccionado === 'todos') || (fila.dataset.rol === rolSeleccionado);
            fila.classList.toggle('d-none-row', !coincide);
            if (coincide) visibles++;
        });
        document.getElementById('aviso-sin-resultados').classList.toggle('d-none', visibles > 0 || filas.length === 0);
    });

    // LÓGICA DE AUTO-AJUSTE PARA EL SUFIJO PEGADO
    const emailInput = document.getElementById('email_prefix');
    
    function ajustarAnchoInput() {
        const tempSpan = document.createElement('span');
        tempSpan.style.visibility = 'hidden';
        tempSpan.style.position = 'absolute';
        tempSpan.style.whiteSpace = 'pre';
        tempSpan.style.font = window.getComputedStyle(emailInput).font;
        tempSpan.textContent = emailInput.value || emailInput.placeholder;
        document.body.appendChild(tempSpan);
        
        emailInput.style.width = (tempSpan.getBoundingClientRect().width + 4) + 'px';
        document.body.removeChild(tempSpan);
    }

    emailInput.addEventListener('input', ajustarAnchoInput);
    ajustarAnchoInput();
});

function abrirCrear() { mCrear.show(); }
function abrirEditar(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_name').value = u.name;
    document.getElementById('edit_username').value = u.username;
    document.getElementById('edit_emailid').value = u.emailid || '';
    document.getElementById('edit_sucursal').value = u.sucursal;
    document.getElementById('edit_role').value = u.role_id || ''; // Se asignará cadena vacía si es null/undefined
    mEditar.show();
}
function confirmarEliminar(id, nombre) {
    Swal.fire({
        title: '¿Retirar Personal?',
        text: `¿De verdad deseas retirar a "${nombre}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8d191d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => { if (result.isConfirmed) window.location.href = `?action=delete&id=${id}`; });
}

// Validaciones en tiempo real
document.addEventListener('input', function(e) {
    const el = e.target;
    if (el.name === 'name') {
        el.value = el.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '');
        el.value = el.value.replace(/\b\w/g, l => l.toUpperCase());
    }
    if (el.name === 'carnet_ci' || el.name === 'username') {
        el.value = el.value.replace(/[^0-9]/g, '');
    }
    if (el.id === 'email_prefix') {
        el.value = el.value.toLowerCase().replace(/[^a-z0-9]/g, '');
    }
    if (el.id === 'edit_emailid') {
        el.value = el.value.toLowerCase().replace(/[^a-z0-9@._-]/g, '');
    }
});

document.getElementById('formCrear').addEventListener('submit', function(e) {
    const prefix = document.getElementById('email_prefix');
    if (prefix.value.trim().length < 1) {
        e.preventDefault();
        Swal.fire('Error', 'El campo de correo electrónico está vacío.', 'error');
    } else {
        document.getElementById('email_completo').value = prefix.value.trim() + '@gmail.com';
    }
});
</script>
<?php layout_foot(); ?>