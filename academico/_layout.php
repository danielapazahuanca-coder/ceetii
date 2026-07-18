<?php
require_once __DIR__ . '/config_api.php';

function layout_head(string $page_title, string $current_page, int $role_id): void {
    $nombre = htmlspecialchars($_SESSION['name'] ?? 'Usuario');
    $rol_label = match($role_id) { 1 => 'Administrador', 2 => 'Secretaria', 3 => 'Docente', default => 'Usuario' };
    $home = match($role_id) { 3 => 'dashboard_docente.php', 2 => 'dashboard_secretaria.php', default => 'dashboard_admin.php' };
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title); ?> — Instituto Tecnológico CEETII</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="css/style.css" rel="stylesheet">

    <script>
        // URL base de la API, generada automáticamente desde config_api.php
        // No escribir la URL de la API a mano en ningún <script> nuevo: usar esta variable.
        const API_BASE_URL = "<?= API_BASE_URL; ?>";
    </script>
</head>
<body>
<div class="navbar-top">
    <button type="button" class="btn-sidebar-toggle" id="sidebarToggleBtn" aria-label="Abrir menú">
        <span class="material-icons">menu</span>
    </button>
    <a class="brand" href="<?= $home; ?>"><span class="material-icons me-2">school</span> <span class="brand-text">Instituto Tecnológico CEETII</span></a>
    <?php if ($role_id === 1 || $role_id === 2):
        $sucursal_actual = resolver_sucursal($role_id);
    ?>
    <div class="custom-sucursal-switcher mx-3" title="Cambiar sucursal de trabajo">
        <a href="?sucursal=EA" class="btn-sucursal <?= $sucursal_actual === 'EA' ? 'active' : ''; ?>">
            <span class="material-icons">apartment</span> <span class="sucursal-txt">EL ALTO (EA)</span>
        </a>
        <a href="?sucursal=LP" class="btn-sucursal <?= $sucursal_actual === 'LP' ? 'active' : ''; ?>">
            <span class="material-icons">business</span> <span class="sucursal-txt">LA PAZ (LP)</span>
        </a>
    </div>
    <?php endif; ?>
    <div class="user-info">
        <span class="material-icons" style="font-size: 1.2rem;">account_circle</span>
        <span><?= $rol_label; ?>: <b><?= $nombre; ?></b></span>
        <a href="../logout.php" class="btn-exit-system ms-2"><span class="material-icons me-1" style="font-size: 1rem;">logout</span> <span class="btn-exit-text">Salir</span></a>
    </div>
</div>
<div class="sidebar">
    <div class="nav-label">Panel de Control</div>
    <a href="<?= $home; ?>" class="<?= ($current_page == $home) ? 'active' : ''; ?>"><span class="material-icons">dashboard</span> Inicio</a>
    <div class="nav-divider"></div>
    <?php if ($role_id === 1): ?>
        <div class="nav-label">Administración</div>
        <a href="usuarios.php" class="<?= ($current_page == 'usuarios.php') ? 'active' : ''; ?>"><span class="material-icons">people</span> Usuarios</a>
        <a href="gestiones.php" class="<?= ($current_page == 'gestiones.php') ? 'active' : ''; ?>"><span class="material-icons">calendar_today</span> Gestiones</a>
    <?php endif; ?>
    <?php if ($role_id === 1 || $role_id === 2): ?>
        <div class="nav-label">Estructura</div>
        <a href="carreras.php" class="<?= in_array($current_page, ['carreras.php', 'materias.php']) ? 'active' : ''; ?>">
            <span class="material-icons">business_center</span> Carreras/Materias
        </a>
        <a href="cursos.php" class="<?= ($current_page == 'cursos.php') ? 'active' : ''; ?>"><span class="material-icons">room</span> Cursos</a>
    <?php endif; ?>
    <?php if ($role_id === 1 || $role_id === 2): ?>
        <div class="nav-label">Gestión Académica</div>
        <a href="asignaciones.php" class="<?= ($current_page == 'asignaciones.php') ? 'active' : ''; ?>"><span class="material-icons">assignment_ind</span> Asignar Docentes</a>
        <a href="estudiantes.php" class="<?= ($current_page == 'estudiantes.php') ? 'active' : ''; ?>"><span class="material-icons">person_add</span> Inscribir Estudiantes</a>
        <a href="vista_cursos.php" class="<?= ($current_page == 'vista_cursos.php') ? 'active' : ''; ?>"><span class="material-icons">preview</span> Vista General</a>
    <?php endif; ?>
    <?php if ($role_id === 1 || $role_id === 2): ?>
        <div class="nav-label">Reportes</div>
        <a href="reportes.php" class="<?= ($current_page == 'reportes.php') ? 'active' : ''; ?>"><span class="material-icons">summarize</span> Reportes</a>
    <?php endif; ?>
    <?php if ($role_id === 1 || $role_id === 2): ?>
        <div class="nav-divider"></div>
        <div class="nav-label">Otros Módulos</div>
        <a href="../inventario/activos.php"><span class="material-icons">inventory_2</span> Inventario</a>
    <?php endif; ?>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
<?php
}
function layout_foot(): void {
?>
</div>
<footer class="text-center pb-4 text-muted small" style="margin-left: 260px;">
    2026 - Instituto Tecnológico CEETII El Alto | Sistema Académico
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    (function () {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!sidebar || !toggleBtn || !backdrop) return;

        function openSidebar() {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
        }
        function closeSidebar() {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        }

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
        });
        backdrop.addEventListener('click', closeSidebar);


        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
    })();
</script>
</body>
</html>
<?php
}