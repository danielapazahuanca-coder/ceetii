<?php
/**
 * ============================================================
 *  CONFIGURACIÓN CENTRAL DE LA API (api_ceti)
 * ============================================================
 * Este archivo define UNA sola vez la dirección base de la API
 * (api_ceti) que usa todo el módulo académico (PHP y JavaScript).
 *
 * Antes, esa dirección estaba escrita a mano en cada archivo
 * (asignaciones.php, cursos.php, estudiantes.php, etc.), así que
 * al subir al hosting había que editar decenas de líneas.
 * Ahora solo hay que editar UNA línea, en ESTE archivo.
 *
 * NO TOCAR nada más de este archivo salvo lo indicado abajo.
 * ============================================================
 */

if (!defined('API_BASE_URL')) {

    // ---- LOCAL (XAMPP) ----
    // Activo por defecto para que sigan trabajando igual que ahora.
    define('API_BASE_URL', 'http://localhost/api_ceti/public');

    // ---- HOSTING ----
    // Cuando el inge suba el proyecto al hosting, tiene que:
    //   1) Comentar (agregar // al inicio) la línea de arriba (LOCAL).
    //   2) Descomentar la línea de abajo (quitarle el //).
    //   3) Reemplazar "tudominio.com" por el dominio real donde
    //      quedó publicada la carpeta api_ceti/public.
    //
    // define('API_BASE_URL', 'https://tudominio.com/api_ceti/public');

}

/**
 * ============================================================
 *  CONTROL CENTRAL DE SUCURSAL (EA / LP)
 * ============================================================
 * Antes cada página (vista_cursos, cursos, estudiantes, etc.)
 * repetía su propia lógica para leer/guardar la sucursal, y solo
 * el Administrador (rol 1) podía cambiarla desde la URL.
 *
 * Ahora esta función es la ÚNICA fuente de verdad:
 *   - Administrador (1) y Secretaria (2) pueden cambiarla via ?sucursal=EA|LP
 *   - Esa elección queda guardada en sesión y aplica en TODAS las páginas
 *   - Si no hay nada aún, por defecto es 'EA' (El Alto)
 *
 * Debe llamarse en cada página DESPUÉS de session_start(), así:
 *   $sucursal_sesion = resolver_sucursal($_SESSION['role_id']);
 * ============================================================
 */
function resolver_sucursal(int $role_id): string {
    if (($role_id === 1 || $role_id === 2) && isset($_GET['sucursal'])) {
        $nueva = strtoupper(trim($_GET['sucursal']));
        if (in_array($nueva, ['EA', 'LP'], true)) {
            $_SESSION['sucursal_varchar'] = $nueva;
        }
    }
    return $_SESSION['sucursal_varchar'] ?? ($_SESSION['sucursal'] ?? 'EA');
}