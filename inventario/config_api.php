<?php
/**
 * ============================================================
 *  CONFIGURACIÓN CENTRAL DE LA API (api_ceti) - INVENTARIO
 * ============================================================
 * Igual que en el módulo académico: UNA sola dirección base de
 * la API, definida en un solo lugar, para no tener que editar
 * decenas de archivos al subir al hosting.
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
