<?php
/**
 * Header unificado del módulo de Inventario.
 * Usa el mismo navbar superior + sidebar que el módulo académico
 * (misma paleta, misma tipografía: css/style.css es una copia del
 * académico). Este archivo asume que la página que lo incluye ya
 * hizo session_start() y ya validó el rol (Admin o Secretaria).
 */
$nombre = htmlspecialchars($_SESSION['name'] ?? 'Usuario');
$role_id = $_SESSION['role_id'] ?? 0;
$rol_label = match($role_id) { 1 => 'Administrador', 2 => 'Secretaria', 3 => 'Docente', default => 'Usuario' };
$home_academico = match($role_id) { 2 => '../academico/dashboard_secretaria.php', default => '../academico/dashboard_admin.php' };
$current_page = basename($_SERVER['PHP_SELF']);
$activos_activo = in_array($current_page, ['activos.php', 'crear.php', 'editar.php'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — Instituto Tecnológico CEETII</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="css/style.css" rel="stylesheet">

    <script>
        // URL base de la API, generada automáticamente desde config_api.php
        const API_BASE_URL = "<?= API_BASE_URL; ?>";
    </script>
</head>
<body>
<div class="navbar-top">
    <a class="brand" href="<?= $home_academico; ?>"><span class="material-icons me-2">school</span> Instituto Tecnológico CEETII</a>
    <div class="user-info">
        <span class="material-icons" style="font-size: 1.2rem;">account_circle</span>
        <span><?= $rol_label; ?>: <b><?= $nombre; ?></b></span>
        <a href="../logout.php" class="btn-exit-system ms-2"><span class="material-icons me-1" style="font-size: 1rem;">logout</span> Salir</a>
    </div>
</div>
<div class="sidebar">
    <div class="nav-label">Sistema</div>
    <a href="<?= $home_academico; ?>"><span class="material-icons">dashboard</span> Panel Académico</a>
    <div class="nav-divider"></div>
    <div class="nav-label">Inventario</div>
    <a href="activos.php" class="<?= $activos_activo ? 'active' : ''; ?>"><span class="material-icons">inventory_2</span> Lista de Activos</a>
    <a href="prestamos.php" class="<?= ($current_page == 'prestamos.php') ? 'active' : ''; ?>"><span class="material-icons">calendar_month</span> Préstamos</a>
    <a href="papelera.php" class="<?= ($current_page == 'papelera.php') ? 'active' : ''; ?>"><span class="material-icons">delete_outline</span> Papelera</a>
</div>
<div class="main-content">
