<?php
/**
 * SISTEMA ACADÉMICO CEETII
 * UBICACIÓN: ceetii/academico/generar_reporte.php
 *
 * ACCIONES DISPONIBLES:
 *   ?action=historial&id_estudiante=X   → Historial académico individual (todos los bimestres)
 *   ?action=acta&id_curso=X&id_materia=X → Acta de una materia (4 bimestres)
 *   ?action=centralizador&id_curso=X    → Centralizador (nota final de todas las materias)
 *   ?action=boletines&id_curso=X        → Boletín individual por alumno (4 bimestres x materia)
 *   ?action=general&tipo=X&sucursal=Y   → Listados generales (estudiantes|usuarios|materias|cursos)
 *
 * Agrega &format=excel para exportar en lugar de PDF.
 * La nota de aprobación es 61 (promedio de 100).
 */

date_default_timezone_set('America/La_Paz');

// ── Cargar Dompdf ────────────────────────────────────────────────────────────
$autoload_path = __DIR__ . '/../inventario/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("<b>Error crítico:</b> No se encontró autoload.php de Dompdf en: <code>$autoload_path</code>");
}
require_once $autoload_path;
require_once __DIR__ . '/config_api.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Constantes ───────────────────────────────────────────────────────────────
// API_BASE ahora viene de config_api.php (antes estaba escrita a mano aquí)
define('API_BASE', API_BASE_URL);
const NOTA_APROBACION = 61;

// ── Parámetros de entrada ────────────────────────────────────────────────────
$action   = trim($_GET['action']   ?? '');
$format   = trim($_GET['format']   ?? 'pdf');   // pdf | excel
$sucursal = strtoupper(trim($_GET['sucursal'] ?? 'LP'));

// ── Helper: llamada a la API ─────────────────────────────────────────────────
function apiGet(string $url): ?array {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function condicionTexto(int $nota): string {
    return $nota >= NOTA_APROBACION ? 'APROBADO' : 'REPROBADO';
}

// ── Variables de reporte ─────────────────────────────────────────────────────
$filename           = 'Reporte_' . date('Ymd_His');
$data               = [];
$meta               = [];
$estudiantes_matriz = [];   // Para centralizador y boletines
$materias_lista     = [];   // Para centralizador

// ============================================================================
// 1. HISTORIAL ACADÉMICO INDIVIDUAL
//    Fuente: GET /notas?action=historial&id_estudiante=X  → todos los bimestres
//    Encabezado del alumno: GET /academico/estudiante/{id}
// ============================================================================
if ($action === 'historial') {

    $id_estudiante = (int)($_GET['id_estudiante'] ?? 0);
    if ($id_estudiante <= 0) die("Error: se requiere <b>id_estudiante</b>.");

    // Notas con bimestres
    $resNotas = apiGet(API_BASE . "/notas?action=historial&id_estudiante={$id_estudiante}");
    if (!$resNotas || ($resNotas['status'] ?? '') !== 'success') {
        die("Error al obtener historial: " . ($resNotas['message'] ?? 'sin respuesta de la API.'));
    }
    $data = $resNotas['data'] ?? [];

    // Datos del alumno
    $resFicha  = apiGet(API_BASE . "/academico/estudiante/{$id_estudiante}");
    $estudiante = $resFicha['data']['estudiante'] ?? null;
    if (!$estudiante) die("Error: no se encontró el estudiante #{$id_estudiante}.");

    $meta = [
        'estudiante' => trim(($estudiante['apellidos'] ?? '') . ' ' . ($estudiante['nombres'] ?? '')),
        'ci'         => trim(($estudiante['ci'] ?? '') . ' ' . ($estudiante['expedido'] ?? '')),
        'carrera'    => $estudiante['carrera']  ?? '—',
        'nivel'      => $estudiante['nivel']    ?? '—',
        'paralelo'   => $estudiante['paralelo'] ?? '—',
        'gestion'    => $estudiante['gestion']  ?? '—',
    ];
    $filename = "Historial_{$id_estudiante}";

// ============================================================================
// 2. ACTA DE CALIFICACIONES (una materia, 4 bimestres)
//    Fuente: GET /notas?action=acta&id_curso=X&id_materia=X
// ============================================================================
} elseif ($action === 'acta') {

    $id_curso   = (int)($_GET['id_curso']   ?? 0);
    $id_materia = (int)($_GET['id_materia'] ?? 0);
    if ($id_curso <= 0 || $id_materia <= 0) die("Error: se requieren <b>id_curso</b> e <b>id_materia</b>.");

    $res = apiGet(API_BASE . "/notas?action=acta&id_curso={$id_curso}&id_materia={$id_materia}");
    if (!$res || ($res['status'] ?? '') !== 'success') {
        die("Error en acta: " . ($res['message'] ?? 'sin respuesta.'));
    }
    $data = $res['data'] ?? [];
    if (empty($data)) die("No hay alumnos/notas para este curso y materia.");

    $p = $data[0];
    $meta = [
        'curso_label' => ($p['año_academico'] ?? '') . ' "' . ($p['paralelo'] ?? '') . '"',
        'carrera'     => $p['carrera']              ?? '—',
        'materia'     => $p['materia']              ?? '—',
        'sigla'       => $p['sigla']                ?? '—',
        'docente'     => $p['docente_responsable']  ?? 'Sin Asignar',
        'gestion'     => $p['gestion']              ?? '—',
    ];
    $filename = "Acta_Curso{$id_curso}_Mat{$id_materia}";

// ============================================================================
// 3. CENTRALIZADOR (todas las materias, solo nota final)
//    Fuente: GET /notas?action=centralizador&id_curso=X
// ============================================================================
} elseif ($action === 'centralizador') {

    $id_curso = (int)($_GET['id_curso'] ?? 0);
    if ($id_curso <= 0) die("Error: se requiere <b>id_curso</b>.");

    $res = apiGet(API_BASE . "/notas?action=centralizador&id_curso={$id_curso}");
    if (!$res || ($res['status'] ?? '') !== 'success') {
        die("Error en centralizador: " . ($res['message'] ?? 'sin respuesta.'));
    }
    $filas = $res['data'] ?? [];
    if (empty($filas)) die("No hay inscripciones/materias para este curso.");

    foreach ($filas as $row) {
        $eid = $row['id_estudiante'];
        $mid = $row['id_materia'];
        if (!isset($estudiantes_matriz[$eid])) {
            $estudiantes_matriz[$eid] = [
                'ci'         => $row['ci']         ?? '',
                'estudiante' => $row['estudiante'] ?? '',
                'notas'      => [],
            ];
        }
        $estudiantes_matriz[$eid]['notas'][$mid] = (int)($row['nota_final'] ?? 0);
        if (!isset($materias_lista[$mid])) {
            $materias_lista[$mid] = ['sigla' => $row['sigla'] ?? '', 'materia' => $row['materia'] ?? ''];
        }
    }
    uasort($estudiantes_matriz, fn($a, $b) => strcasecmp($a['estudiante'], $b['estudiante']));

    $p = $filas[0];
    $meta = [
        'curso_label' => ($p['año_academico'] ?? '') . ' "' . ($p['paralelo'] ?? '') . '"',
        'carrera'     => $p['carrera']  ?? '—',
        'gestion'     => $p['gestion']  ?? '—',
    ];
    $filename = "Centralizador_Curso{$id_curso}";

// ============================================================================
// 4. BOLETINES EN MASA (una página por alumno, 4 bimestres x materia)
//    Fuente: GET /notas?action=boletines&id_curso=X
// ============================================================================
} elseif ($action === 'boletines') {

    $id_curso = (int)($_GET['id_curso'] ?? 0);
    if ($id_curso <= 0) die("Error: se requiere <b>id_curso</b>.");

    $res = apiGet(API_BASE . "/notas?action=boletines&id_curso={$id_curso}");
    if (!$res || ($res['status'] ?? '') !== 'success') {
        die("Error en boletines: " . ($res['message'] ?? 'sin respuesta.'));
    }
    $filas = $res['data'] ?? [];
    if (empty($filas)) die("No hay inscripciones/materias para este curso.");

    foreach ($filas as $row) {
        $eid   = $row['id_estudiante'];
        $clave = $row['sigla'] ?? uniqid();
        if (!isset($estudiantes_matriz[$eid])) {
            $estudiantes_matriz[$eid] = [
                'ci'         => $row['ci']         ?? '',
                'estudiante' => $row['estudiante'] ?? '',
                'materias'   => [],
            ];
        }
        $fin = (int)($row['final'] ?? 0);
        $estudiantes_matriz[$eid]['materias'][$clave] = [
            'sigla'     => $row['sigla']   ?? '',
            'materia'   => $row['materia'] ?? '',
            'docente'   => $row['docente'] ?? 'Sin Asignar',
            'b1'        => (int)($row['b1']    ?? 0),
            'b2'        => (int)($row['b2']    ?? 0),
            'b3'        => (int)($row['b3']    ?? 0),
            'b4'        => (int)($row['b4']    ?? 0),
            'final'     => $fin,
            'condicion' => $row['condicion'] ?? condicionTexto($fin),
        ];
    }
    uasort($estudiantes_matriz, fn($a, $b) => strcasecmp($a['estudiante'], $b['estudiante']));

    $p = $filas[0];
    $meta = [
        'curso_label' => ($p['año_academico'] ?? '') . ' "' . ($p['paralelo'] ?? '') . '"',
        'carrera'     => $p['carrera']  ?? '—',
        'gestion'     => $p['gestion']  ?? '—',
    ];
    $filename = "Boletines_Curso{$id_curso}";

// ============================================================================
// 5. REPORTES GENERALES (estudiantes | usuarios | materias | cursos)
// ============================================================================
} elseif ($action === 'general') {

    $tipo = trim($_GET['tipo'] ?? '');
    if (empty($tipo)) die("Error: se requiere <b>tipo</b>.");

    $estudiantes_agrupados = []; // Solo se usa cuando $tipo === 'estudiantes'

    if ($tipo === 'cursos') {
        $res  = apiGet(API_BASE . "/cursos");
        $todos = $res['data'] ?? [];
        $data = array_values(array_filter($todos, fn($c) =>
            strtoupper(trim($c['sucursal_varchar'] ?? '')) === $sucursal
        ));

    } elseif ($tipo === 'estudiantes') {
        // Gestión activa de la sucursal, para saber el curso vigente de cada alumno
        $resGestion    = apiGet(API_BASE . "/gestion");
        $gestiones_rep = $resGestion['data'] ?? [];
        $id_gestion_rep = null;
        foreach ($gestiones_rep as $g) {
            if ((int)($g['estado_bt'] ?? 0) === 1 && strtoupper(trim($g['sucursal_varchar'] ?? '')) === $sucursal) {
                $id_gestion_rep = (int)$g['id_gestion'];
                break;
            }
        }
        $gestionParam = $id_gestion_rep !== null ? "&id_gestion={$id_gestion_rep}" : '';

        $res = apiGet(API_BASE . "/notas?action=general&tipo=estudiantes&sucursal=" . urlencode($sucursal) . $gestionParam);
        if (!$res || ($res['status'] ?? '') !== 'success') {
            die("Error al obtener listado de estudiantes: " . ($res['message'] ?? 'sin respuesta.'));
        }
        $data = $res['data'] ?? [];

        // Agrupar Carrera → Curso, preservando el orden que ya entrega la API
        foreach ($data as $r) {
            $carreraKey   = $r['nombre_carrera'] ?? '__SIN_CARRERA__';
            $carreraLabel = $r['nombre_carrera'] ?? 'Sin Carrera / Sin Inscripción Vigente';
            $cursoLabel   = (!empty($r['nombre_nivel']) && !empty($r['paralelo']))
                ? $r['nombre_nivel'] . ' "' . $r['paralelo'] . '"'
                : 'Sin Curso Asignado';

            if (!isset($estudiantes_agrupados[$carreraKey])) {
                $estudiantes_agrupados[$carreraKey] = ['label' => $carreraLabel, 'cursos' => []];
            }
            if (!isset($estudiantes_agrupados[$carreraKey]['cursos'][$cursoLabel])) {
                $estudiantes_agrupados[$carreraKey]['cursos'][$cursoLabel] = [];
            }
            $estudiantes_agrupados[$carreraKey]['cursos'][$cursoLabel][] = $r;
        }

    } else {
        $res = apiGet(API_BASE . "/notas?action=general&tipo=" . urlencode($tipo) . "&sucursal=" . urlencode($sucursal));
        if (!$res || ($res['status'] ?? '') !== 'success') {
            die("Error al obtener listado de {$tipo}: " . ($res['message'] ?? 'sin respuesta.'));
        }
        $data = $res['data'] ?? [];
    }

    $meta     = ['tipo' => $tipo, 'sucursal' => $sucursal];
    $filename = "Listado_" . ucfirst($tipo) . "_{$sucursal}";

} else {
    die("Acción no válida: <b>" . htmlspecialchars($action) . "</b>");
}

// ── Logo en base64 ───────────────────────────────────────────────────────────
$logo_path    = __DIR__ . '/../inventario/img/logo.png';
$base64_logo  = '';
if (file_exists($logo_path)) {
    $ext         = pathinfo($logo_path, PATHINFO_EXTENSION);
    $base64_logo = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($logo_path));
}

// ============================================================================
// EXPORTACIÓN EXCEL
// ============================================================================
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.xls');
    header('Pragma: no-cache');
    echo '<table border="1" style="font-family:Arial;font-size:11px;">';

    if ($action === 'historial') {
        echo '<tr><th colspan="9" style="background:#0c2340;color:#fff;font-size:13px;padding:8px;">HISTORIAL ACADÉMICO — ' . htmlspecialchars($meta['estudiante']) . '</th></tr>';
        echo '<tr><th colspan="9">C.I.: ' . htmlspecialchars($meta['ci']) . ' | Carrera: ' . htmlspecialchars($meta['carrera']) . ' | Gestión: ' . htmlspecialchars($meta['gestion']) . '</th></tr>';
        echo '<tr style="background:#dde3ea;"><th>Sigla</th><th>Asignatura</th><th>Gestión</th><th>Docente</th><th>1° Bim</th><th>2° Bim</th><th>3° Bim</th><th>4° Bim</th><th>Promedio</th></tr>';
        foreach ($data as $r) {
            $p = (int)($r['total'] ?? 0);
            echo '<tr><td><b>' . htmlspecialchars($r['sigla'] ?? '') . '</b></td>'
               . '<td>' . htmlspecialchars($r['materia'] ?? '') . '</td>'
               . '<td>' . htmlspecialchars($r['gestion'] ?? '') . '</td>'
               . '<td>' . htmlspecialchars($r['docente'] ?? 'Sin Asignar') . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b1'] ?? 0) . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b2'] ?? 0) . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b3'] ?? 0) . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b4'] ?? 0) . '</td>'
               . '<td style="text-align:center;font-weight:bold;color:' . ($p >= NOTA_APROBACION ? '#1a7431' : '#b00020') . ';">' . $p . '</td></tr>';
        }

    } elseif ($action === 'acta') {
        echo '<tr><th colspan="7" style="background:#0c2340;color:#fff;font-size:13px;padding:8px;">ACTA — ' . htmlspecialchars($meta['sigla'] . ' · ' . $meta['materia']) . '</th></tr>';
        echo '<tr><th colspan="7">Curso: ' . htmlspecialchars($meta['curso_label']) . ' | Docente: ' . htmlspecialchars($meta['docente']) . ' | Gestión: ' . htmlspecialchars($meta['gestion']) . '</th></tr>';
        echo '<tr style="background:#dde3ea;"><th>C.I.</th><th>Apellidos y Nombres</th><th>1° Bim</th><th>2° Bim</th><th>3° Bim</th><th>4° Bim</th><th>Promedio</th></tr>';
        foreach ($data as $r) {
            $p = (int)($r['final'] ?? 0);
            echo '<tr><td>' . htmlspecialchars($r['ci'] ?? '') . '</td>'
               . '<td><b>' . htmlspecialchars($r['estudiante'] ?? '') . '</b></td>'
               . '<td style="text-align:center;">' . (int)($r['b1'] ?? 0) . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b2'] ?? 0) . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b3'] ?? 0) . '</td>'
               . '<td style="text-align:center;">' . (int)($r['b4'] ?? 0) . '</td>'
               . '<td style="text-align:center;font-weight:bold;color:' . ($p >= NOTA_APROBACION ? '#1a7431' : '#b00020') . ';">' . $p . '</td></tr>';
        }

    } elseif ($action === 'centralizador') {
        $cols = 2 + count($materias_lista);
        echo '<tr><th colspan="' . $cols . '" style="background:#0c2340;color:#fff;font-size:13px;padding:8px;">CENTRALIZADOR — ' . htmlspecialchars($meta['curso_label']) . ' | ' . htmlspecialchars($meta['gestion']) . '</th></tr>';
        echo '<tr style="background:#dde3ea;"><th>C.I.</th><th>Apellidos y Nombres</th>';
        foreach ($materias_lista as $m) echo '<th>' . htmlspecialchars($m['sigla']) . '</th>';
        echo '</tr>';
        foreach ($estudiantes_matriz as $e) {
            echo '<tr><td>' . htmlspecialchars($e['ci']) . '</td><td><b>' . htmlspecialchars($e['estudiante']) . '</b></td>';
            foreach ($materias_lista as $mid => $m) {
                $nota = $e['notas'][$mid] ?? null;
                $color = $nota === null ? '#999' : ($nota >= NOTA_APROBACION ? '#1a7431' : '#b00020');
                echo '<td style="text-align:center;font-weight:bold;color:' . $color . ';">' . ($nota ?? '—') . '</td>';
            }
            echo '</tr>';
        }

    } elseif ($action === 'boletines') {
        echo '<tr><th colspan="9" style="background:#0c2340;color:#fff;font-size:13px;padding:8px;">BOLETINES — ' . htmlspecialchars($meta['curso_label']) . ' | ' . htmlspecialchars($meta['gestion']) . '</th></tr>';
        foreach ($estudiantes_matriz as $e) {
            echo '<tr style="background:#eef2ff;"><th colspan="9" style="text-align:left;padding:5px;"><b>' . htmlspecialchars($e['estudiante']) . '</b> — C.I. ' . htmlspecialchars($e['ci']) . '</th></tr>';
            echo '<tr style="background:#dde3ea;font-size:10px;"><th>Sigla</th><th>Asignatura</th><th>Docente</th><th>1° Bim</th><th>2° Bim</th><th>3° Bim</th><th>4° Bim</th><th>Promedio</th><th>Condición</th></tr>';
            foreach ($e['materias'] as $m) {
                $p = $m['final'];
                echo '<tr><td><b>' . htmlspecialchars($m['sigla']) . '</b></td>'
                   . '<td>' . htmlspecialchars($m['materia']) . '</td>'
                   . '<td>' . htmlspecialchars($m['docente']) . '</td>'
                   . '<td style="text-align:center;">' . $m['b1'] . '</td>'
                   . '<td style="text-align:center;">' . $m['b2'] . '</td>'
                   . '<td style="text-align:center;">' . $m['b3'] . '</td>'
                   . '<td style="text-align:center;">' . $m['b4'] . '</td>'
                   . '<td style="text-align:center;font-weight:bold;">' . $p . '</td>'
                   . '<td style="font-weight:bold;color:' . ($p >= NOTA_APROBACION ? '#1a7431' : '#b00020') . ';">' . htmlspecialchars($m['condicion']) . '</td></tr>';
            }
            echo '<tr><td colspan="9" style="height:10px;"></td></tr>';
        }

    } elseif ($action === 'general') {
        $tipo = $meta['tipo'];
        echo '<tr><th colspan="5" style="background:#0c2340;color:#fff;font-size:13px;padding:8px;">LISTADO — ' . strtoupper(htmlspecialchars($tipo)) . ' | Sucursal ' . htmlspecialchars($meta['sucursal']) . '</th></tr>';
        if ($tipo === 'estudiantes') {
            foreach ($estudiantes_agrupados as $grupoCarrera) {
                echo '<tr><th colspan="5" style="background:#0c2340;color:#fff;font-size:12px;padding:6px;text-align:left;">' . htmlspecialchars($grupoCarrera['label']) . '</th></tr>';
                foreach ($grupoCarrera['cursos'] as $cursoLabel => $alumnos) {
                    echo '<tr><th colspan="5" style="background:#dde3ea;color:#0c2340;font-size:10px;padding:4px;text-align:left;">' . htmlspecialchars($cursoLabel) . ' (' . count($alumnos) . ')</th></tr>';
                    echo '<tr style="background:#eef1f5;"><th>C.I.</th><th>Apellidos</th><th>Nombres</th><th>Teléfono</th><th>Estado</th></tr>';
                    foreach ($alumnos as $r) {
                        echo '<tr><td>' . htmlspecialchars($r['ci'] ?? '') . '</td><td>' . htmlspecialchars($r['apellidos'] ?? '') . '</td><td>' . htmlspecialchars($r['nombres'] ?? '') . '</td><td>' . htmlspecialchars($r['telefono'] ?? '') . '</td><td>' . htmlspecialchars($r['estado'] ?? '') . '</td></tr>';
                    }
                }
            }
        } elseif ($tipo === 'usuarios') {
            echo '<tr style="background:#dde3ea;"><th>Usuario</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Sucursal</th></tr>';
            foreach ($data as $r) echo '<tr><td>' . htmlspecialchars($r['username'] ?? '') . '</td><td>' . htmlspecialchars($r['name'] ?? '') . '</td><td>' . htmlspecialchars($r['emailid'] ?? '') . '</td><td>' . htmlspecialchars($r['nombre_rol'] ?? '') . '</td><td>' . htmlspecialchars($r['sucursal_varchar'] ?? '') . '</td></tr>';
        } elseif ($tipo === 'materias') {
            echo '<tr style="background:#dde3ea;"><th>Sigla</th><th>Materia</th><th>Carrera</th><th>Año</th><th>-</th></tr>';
            foreach ($data as $r) echo '<tr><td><b>' . htmlspecialchars($r['sigla'] ?? '') . '</b></td><td>' . htmlspecialchars($r['nombre'] ?? '') . '</td><td>' . htmlspecialchars($r['carrera'] ?? '') . '</td><td>' . htmlspecialchars($r['año'] ?? '') . '</td><td></td></tr>';
        } elseif ($tipo === 'cursos') {
            echo '<tr style="background:#dde3ea;"><th>Curso</th><th>Carrera</th><th>Gestión</th><th>Estado</th><th>-</th></tr>';
            foreach ($data as $r) echo '<tr><td><b>' . htmlspecialchars($r['id_nivel'] . $r['paralelo']) . '</b></td><td>' . htmlspecialchars($r['carrera_nombre'] ?? '') . '</td><td>' . htmlspecialchars($r['gestion_varchar'] ?? '') . '</td><td>' . ((int)($r['estado'] ?? 0) ? 'Activo' : 'Inactivo') . '</td><td></td></tr>';
        }
    }

    echo '</table>';
    exit();
}

// ============================================================================
// GENERACIÓN PDF con Dompdf
// ============================================================================
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 35px 28px 40px 28px; }
    body   { font-family: Arial, sans-serif; font-size: 9.5px; color: #111; line-height: 1.35; }

    /* Encabezado institucional */
    .header-inst { width:100%; border-collapse:collapse; margin-bottom:14px; border-bottom: 2px solid #0c2340; padding-bottom:6px; }
    .header-inst td { border:none; vertical-align:middle; }
    .logo { width:60px; height:auto; }
    .titulo-bloque { text-align:right; }
    .titulo-bloque h1 { margin:0; font-size:13px; color:#0c2340; text-transform:uppercase; letter-spacing:.5px; }
    .titulo-bloque .subtitulo { margin:2px 0 0; font-size:8px; color:#666; }

    /* Caja de metadatos */
    .meta-caja { width:100%; border-collapse:collapse; background:#f4f6f9; border:1px solid #d0d5dd; margin-bottom:12px; }
    .meta-caja td { border:none; padding:3px 7px; font-size:9px; }
    .meta-caja .lbl { font-weight:bold; color:#444; width:110px; }

    /* Tablas de datos */
    table.dt { width:100%; border-collapse:collapse; margin-top:4px; }
    table.dt th {
        border:1px solid #8fa0b8;
        padding:5px 4px;
        text-align:center;
        background:#0c2340;
        color:#fff;
        font-size:8.5px;
        text-transform:uppercase;
        letter-spacing:.3px;
    }
    table.dt th.txt-left { text-align:left; }
    table.dt td { border:1px solid #d8dde6; padding:4px 4px; font-size:9px; vertical-align:middle; }
    table.dt tr:nth-child(even) td { background:#f8f9fb; }
    .tc { text-align:center; }
    .tr { text-align:right; }
    .bold { font-weight:bold; }
    .green { color:#1a7431; font-weight:bold; }
    .red   { color:#b00020; font-weight:bold; }
    .gray  { color:#888; }

    /* Separador de bimestres */
    .bim-group th { background:#1e4d8c; font-size:8px; }

    /* Página nueva */
    .page-break { page-break-after:always; }

    /* Pie de página */
    .footer { position:fixed; bottom:-20px; left:0; right:0; text-align:center; font-size:7px; color:#aaa; border-top:0.5px solid #ddd; padding-top:3px; }

    /* Barra de progreso visual en promedio */
    .nota-badge { display:inline-block; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
    .nota-ap { background:#d4edda; color:#155724; }
    .nota-re { background:#f8d7da; color:#721c24; }
</style>
</head>
<body>

<?php
// ── Helper local: encabezado institucional ───────────────────────────────────
function pdfHeader(string $titulo, string $subtitulo, string $logo): void { ?>
<table class="header-inst">
    <tr>
        <td style="width:70px;">
            <?php if ($logo): ?><img src="<?= $logo ?>" class="logo" alt="Logo"><?php endif; ?>
        </td>
        <td class="titulo-bloque">
            <h1><?= htmlspecialchars($titulo) ?></h1>
            <p class="subtitulo">Instituto Tecnológico Superior CEETII &nbsp;|&nbsp; <?= htmlspecialchars($subtitulo) ?> &nbsp;|&nbsp; Emitido: <?= date('d/m/Y H:i') ?></p>
        </td>
    </tr>
</table>
<?php }

// ── Helper: caja meta ────────────────────────────────────────────────────────
function pdfMeta(array $pares): void {
    echo '<table class="meta-caja"><tbody>';
    $i = 0;
    foreach ($pares as $lbl => $val) {
        if ($i % 2 === 0) echo '<tr>';
        echo '<td class="lbl">' . htmlspecialchars($lbl) . ':</td><td class="bold">' . htmlspecialchars($val) . '</td>';
        if ($i % 2 === 1) echo '</tr>';
        $i++;
    }
    if ($i % 2 === 1) echo '<td></td><td></td></tr>';
    echo '</tbody></table>';
}

// ────────────────────────────────────────────────────────────────────────────
// PDF: HISTORIAL ACADÉMICO  (con los 4 bimestres)
// ────────────────────────────────────────────────────────────────────────────
if ($action === 'historial'):
    pdfHeader('Historial Académico Individual', 'Todos los períodos registrados', $base64_logo);
    pdfMeta([
        'Estudiante'   => $meta['estudiante'],
        'C.I.'         => $meta['ci'],
        'Carrera'      => $meta['carrera'],
        'Curso Actual' => $meta['nivel'] . ' "' . $meta['paralelo'] . '"',
    ]);
?>
<table class="dt">
    <thead>
        <tr>
            <th class="txt-left" style="width:60px;">Sigla</th>
            <th class="txt-left">Asignatura</th>
            <th style="width:50px;">Gestión</th>
            <th style="width:120px;">Docente</th>
            <th style="width:35px;" class="bim-group">1° Bim</th>
            <th style="width:35px;" class="bim-group">2° Bim</th>
            <th style="width:35px;" class="bim-group">3° Bim</th>
            <th style="width:35px;" class="bim-group">4° Bim</th>
            <th style="width:45px;">Promedio</th>
            <th style="width:65px;">Condición</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($data)): ?>
        <tr><td colspan="10" class="tc gray">Sin calificaciones registradas.</td></tr>
    <?php else:
        $gestion_actual = null;
        foreach ($data as $r):
            $prom = (int)($r['total'] ?? 0);
            // Separador de gestión
            if ($r['gestion'] !== $gestion_actual):
                $gestion_actual = $r['gestion'];
    ?>
        <tr>
            <td colspan="10" style="background:#e8edf5; font-weight:bold; padding:4px 6px; font-size:8.5px; color:#0c2340;">
                ▸ Gestión Académica: <?= htmlspecialchars($gestion_actual) ?>
            </td>
        </tr>
    <?php endif; ?>
        <tr>
            <td class="bold"><?= htmlspecialchars($r['sigla'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['materia'] ?? '') ?></td>
            <td class="tc"><?= htmlspecialchars($r['gestion'] ?? '') ?></td>
            <td style="font-size:8px; color:#555;"><?= htmlspecialchars($r['docente'] ?? 'Sin Asignar') ?></td>
            <td class="tc"><?= (int)($r['b1'] ?? 0) ?></td>
            <td class="tc"><?= (int)($r['b2'] ?? 0) ?></td>
            <td class="tc"><?= (int)($r['b3'] ?? 0) ?></td>
            <td class="tc"><?= (int)($r['b4'] ?? 0) ?></td>
            <td class="tc bold"><?= $prom ?></td>
            <td class="tc">
                <span class="nota-badge <?= $prom >= NOTA_APROBACION ? 'nota-ap' : 'nota-re' ?>">
                    <?= htmlspecialchars($r['condicion'] ?? condicionTexto($prom)) ?>
                </span>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<?php
// ────────────────────────────────────────────────────────────────────────────
// PDF: ACTA DE CALIFICACIONES (4 bimestres)
// ────────────────────────────────────────────────────────────────────────────
elseif ($action === 'acta'):
    pdfHeader('Acta de Calificaciones', $meta['curso_label'] . ' · ' . $meta['gestion'], $base64_logo);
    pdfMeta([
        'Curso'         => $meta['curso_label'],
        'Gestión'       => $meta['gestion'],
        'Carrera'       => $meta['carrera'],
        'Materia'       => $meta['sigla'] . ' — ' . $meta['materia'],
        'Docente'       => $meta['docente'],
        'Total Alumnos' => count($data) . ' estudiantes',
    ]);
?>
<table class="dt">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="txt-left" style="width:100px;">C.I.</th>
            <th class="txt-left">Apellidos y Nombres</th>
            <th style="width:38px;" class="bim-group">1° Bim</th>
            <th style="width:38px;" class="bim-group">2° Bim</th>
            <th style="width:38px;" class="bim-group">3° Bim</th>
            <th style="width:38px;" class="bim-group">4° Bim</th>
            <th style="width:52px;">Promedio</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($data as $idx => $r):
        $prom = (int)($r['final'] ?? 0); ?>
        <tr>
            <td class="tc gray"><?= $idx + 1 ?></td>
            <td><?= htmlspecialchars($r['ci'] ?? '') ?></td>
            <td class="bold"><?= htmlspecialchars($r['estudiante'] ?? '') ?></td>
            <td class="tc"><?= (int)($r['b1'] ?? 0) ?></td>
            <td class="tc"><?= (int)($r['b2'] ?? 0) ?></td>
            <td class="tc"><?= (int)($r['b3'] ?? 0) ?></td>
            <td class="tc"><?= (int)($r['b4'] ?? 0) ?></td>
            <td class="tc">
                <span class="nota-badge <?= $prom >= NOTA_APROBACION ? 'nota-ap' : 'nota-re' ?>"><?= $prom ?></span>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<!-- Firma -->
<table style="width:100%; margin-top:40px; border-collapse:collapse;">
    <tr>
        <td style="width:40%; text-align:center; padding-top:30px; border-top:1px solid #555; font-size:9px;">
            Docente Responsable<br><b><?= htmlspecialchars($meta['docente']) ?></b>
        </td>
        <td style="width:20%;"></td>
        <td style="width:40%; text-align:center; padding-top:30px; border-top:1px solid #555; font-size:9px;">
            Director / Autoridad Académica
        </td>
    </tr>
</table>

<?php
// ────────────────────────────────────────────────────────────────────────────
// PDF: CENTRALIZADOR (nota final de todas las materias, landscape)
// ────────────────────────────────────────────────────────────────────────────
elseif ($action === 'centralizador'):
    pdfHeader('Centralizador General de Calificaciones', $meta['curso_label'] . ' · ' . $meta['gestion'], $base64_logo);
    pdfMeta([
        'Carrera' => $meta['carrera'],
        'Curso'   => $meta['curso_label'],
        'Gestión' => $meta['gestion'],
        'Alumnos' => count($estudiantes_matriz) . ' estudiantes',
    ]);
?>
<table class="dt">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="txt-left" style="width:75px;">C.I.</th>
            <th class="txt-left" style="width:130px;">Apellidos y Nombres</th>
            <?php foreach ($materias_lista as $m): ?>
                <th style="font-size:7.5px; max-width:45px;"><?= htmlspecialchars($m['sigla']) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php $idx = 0; foreach ($estudiantes_matriz as $e): $idx++; ?>
        <tr>
            <td class="tc gray"><?= $idx ?></td>
            <td style="font-size:8.5px;"><?= htmlspecialchars($e['ci']) ?></td>
            <td class="bold" style="font-size:8.5px;"><?= htmlspecialchars($e['estudiante']) ?></td>
            <?php foreach ($materias_lista as $mid => $m):
                $nota = $e['notas'][$mid] ?? null; ?>
                <td class="tc bold" style="font-size:9px; color:<?= $nota === null ? '#aaa' : ($nota >= NOTA_APROBACION ? '#1a7431' : '#b00020') ?>;">
                    <?= $nota ?? '—' ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
// ────────────────────────────────────────────────────────────────────────────
// PDF: BOLETINES EN MASA (una página por alumno, 4 bimestres)
// ────────────────────────────────────────────────────────────────────────────
elseif ($action === 'boletines'):
    $total_est = count($estudiantes_matriz);
    $cnt = 0;
    foreach ($estudiantes_matriz as $est):
        $cnt++;
        pdfHeader('Boletín de Calificaciones', $meta['curso_label'] . ' · ' . $meta['gestion'], $base64_logo);
        pdfMeta([
            'Estudiante' => $est['estudiante'],
            'C.I.'       => $est['ci'],
            'Carrera'    => $meta['carrera'],
            'Curso'      => $meta['curso_label'],
            'Gestión'    => $meta['gestion'],
        ]);

        // Estadísticas del alumno
        $total_m = count($est['materias']);
        $aprobadas = count(array_filter($est['materias'], fn($m) => $m['final'] >= NOTA_APROBACION));
        $reprobadas = $total_m - $aprobadas;
        $sum = array_sum(array_column($est['materias'], 'final'));
        $prom_general = $total_m > 0 ? round($sum / $total_m) : 0;
?>
<table style="width:100%; border-collapse:collapse; margin-bottom:10px; background:#f4f6f9; border:1px solid #d0d5dd;">
    <tr>
        <td class="tc" style="padding:6px; width:25%;">
            <div style="font-size:8px; color:#666; font-weight:bold; text-transform:uppercase;">Total Materias</div>
            <div style="font-size:16px; font-weight:bold; color:#0c2340;"><?= $total_m ?></div>
        </td>
        <td class="tc" style="padding:6px; width:25%; border-left:1px solid #d0d5dd;">
            <div style="font-size:8px; color:#666; font-weight:bold; text-transform:uppercase;">Aprobadas</div>
            <div style="font-size:16px; font-weight:bold; color:#1a7431;"><?= $aprobadas ?></div>
        </td>
        <td class="tc" style="padding:6px; width:25%; border-left:1px solid #d0d5dd;">
            <div style="font-size:8px; color:#666; font-weight:bold; text-transform:uppercase;">Reprobadas</div>
            <div style="font-size:16px; font-weight:bold; color:#b00020;"><?= $reprobadas ?></div>
        </td>
        <td class="tc" style="padding:6px; width:25%; border-left:1px solid #d0d5dd;">
            <div style="font-size:8px; color:#666; font-weight:bold; text-transform:uppercase;">Promedio General</div>
            <div style="font-size:16px; font-weight:bold; color:<?= $prom_general >= NOTA_APROBACION ? '#1a7431' : '#b00020' ?>;"><?= $prom_general ?></div>
        </td>
    </tr>
</table>
<table class="dt">
    <thead>
        <tr>
            <th class="txt-left" style="width:55px;">Sigla</th>
            <th class="txt-left">Asignatura</th>
            <th style="width:100px;">Docente</th>
            <th style="width:35px;" class="bim-group">1° Bim</th>
            <th style="width:35px;" class="bim-group">2° Bim</th>
            <th style="width:35px;" class="bim-group">3° Bim</th>
            <th style="width:35px;" class="bim-group">4° Bim</th>
            <th style="width:45px;">Prom.</th>
            <th style="width:65px;">Condición</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($est['materias'] as $m_row):
        $p = $m_row['final']; ?>
        <tr>
            <td class="bold"><?= htmlspecialchars($m_row['sigla']) ?></td>
            <td><?= htmlspecialchars($m_row['materia']) ?></td>
            <td style="font-size:7.5px; color:#555;"><?= htmlspecialchars($m_row['docente']) ?></td>
            <td class="tc"><?= $m_row['b1'] ?></td>
            <td class="tc"><?= $m_row['b2'] ?></td>
            <td class="tc"><?= $m_row['b3'] ?></td>
            <td class="tc"><?= $m_row['b4'] ?></td>
            <td class="tc bold"><?= $p ?></td>
            <td class="tc">
                <span class="nota-badge <?= $p >= NOTA_APROBACION ? 'nota-ap' : 'nota-re' ?>">
                    <?= htmlspecialchars($m_row['condicion']) ?>
                </span>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<!-- Firma en boletín -->
<table style="width:100%; margin-top:30px; border-collapse:collapse;">
    <tr>
        <td style="width:40%; text-align:center; padding-top:25px; border-top:1px solid #555; font-size:8px;">
            Director Académico<br><br>
        </td>
        <td style="width:20%;"></td>
        <td style="width:40%; text-align:center; padding-top:25px; border-top:1px solid #555; font-size:8px;">
            Sello Institucional
        </td>
    </tr>
</table>
<?php if ($cnt < $total_est): ?><div class="page-break"></div><?php endif; ?>
<?php endforeach; ?>

<?php
// ────────────────────────────────────────────────────────────────────────────
// PDF: REPORTES GENERALES
// ────────────────────────────────────────────────────────────────────────────
elseif ($action === 'general'):
    $tipo = $meta['tipo'];
    pdfHeader('Listado General — ' . strtoupper($tipo), 'Sucursal ' . $meta['sucursal'], $base64_logo);

    if ($tipo === 'estudiantes'):
        $numero = 0;
        foreach ($estudiantes_agrupados as $grupoCarrera):
?>
<table style="width:100%; border-collapse:collapse; margin-top:14px; margin-bottom:2px;">
    <tr><td style="background:#0c2340; color:#fff; font-size:11px; font-weight:bold; padding:6px 8px; text-transform:uppercase;">
        <?= htmlspecialchars($grupoCarrera['label']) ?>
    </td></tr>
</table>
<?php foreach ($grupoCarrera['cursos'] as $cursoLabel => $alumnos): ?>
<table style="width:100%; border-collapse:collapse; margin-bottom:2px;">
    <tr><td style="background:#dde3ea; color:#0c2340; font-size:9.5px; font-weight:bold; padding:4px 8px;">
        <?= htmlspecialchars($cursoLabel) ?> &nbsp;·&nbsp; <?= count($alumnos) ?> estudiante<?= count($alumnos) === 1 ? '' : 's' ?>
    </td></tr>
</table>
<table class="dt">
    <thead><tr><th style="width:20px;">#</th><th class="txt-left" style="width:80px;">C.I.</th><th class="txt-left">Apellidos</th><th class="txt-left">Nombres</th><th style="width:80px;">Teléfono</th><th style="width:55px;">Estado</th></tr></thead>
    <tbody><?php foreach ($alumnos as $r): $numero++; ?>
        <tr><td class="tc gray"><?= $numero ?></td><td><?= htmlspecialchars($r['ci']??'') ?></td><td class="bold"><?= htmlspecialchars($r['apellidos']??'') ?></td><td><?= htmlspecialchars($r['nombres']??'') ?></td><td class="tc"><?= htmlspecialchars($r['telefono']??'—') ?></td><td class="tc"><?= htmlspecialchars($r['estado']??'') ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
<?php endforeach; ?>
<?php endforeach; ?>
<?php elseif ($tipo === 'usuarios'): ?>
<table class="dt">
    <thead><tr><th style="width:20px;">#</th><th class="txt-left">Usuario</th><th class="txt-left">Nombre Completo</th><th class="txt-left">Email</th><th style="width:80px;">Rol</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
        <tr><td class="tc gray"><?= $i+1 ?></td><td><?= htmlspecialchars($r['username']??'') ?></td><td class="bold"><?= htmlspecialchars($r['name']??'') ?></td><td><?= htmlspecialchars($r['emailid']??'') ?></td><td class="tc"><?= htmlspecialchars($r['nombre_rol']??'—') ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
<?php elseif ($tipo === 'materias'): ?>
<table class="dt">
    <thead><tr><th style="width:20px;">#</th><th class="txt-left" style="width:65px;">Sigla</th><th class="txt-left">Materia</th><th class="txt-left">Carrera</th><th style="width:80px;">Año</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
        <tr><td class="tc gray"><?= $i+1 ?></td><td class="bold"><?= htmlspecialchars($r['sigla']??'') ?></td><td><?= htmlspecialchars($r['nombre']??'') ?></td><td><?= htmlspecialchars($r['carrera']??'') ?></td><td class="tc"><?= htmlspecialchars($r['año']??'') ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
<?php elseif ($tipo === 'cursos'): ?>
<table class="dt">
    <thead><tr><th style="width:20px;">#</th><th class="txt-left" style="width:50px;">Curso</th><th class="txt-left">Carrera</th><th style="width:65px;">Gestión</th><th style="width:55px;">Estado</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
        <tr><td class="tc gray"><?= $i+1 ?></td><td class="bold tc"><?= htmlspecialchars($r['id_nivel'].$r['paralelo']) ?></td><td><?= htmlspecialchars($r['carrera_nombre']??'') ?></td><td class="tc"><?= htmlspecialchars($r['gestion_varchar']??'') ?></td><td class="tc <?= (int)($r['estado']??0) ? 'green' : 'red' ?>"><?= (int)($r['estado']??0) ? 'Activo' : 'Inactivo' ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<?php endif; // fin switch de acción ?>

<div class="footer">CEETII · Sistema Académico · Reporte generado el <?= date('d/m/Y \a \l\a\s H:i:s') ?></div>
</body>
</html>
<?php
$html = ob_get_clean();

// ── Configurar y renderizar Dompdf ───────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');

// Landscape para centralizador (muchas columnas); portrait para el resto
$orientation = in_array($action, ['centralizador']) ? 'landscape' : 'portrait';
$dompdf->setPaper('letter', $orientation);
$dompdf->render();
$dompdf->stream($filename . '.pdf', ['Attachment' => false]);
exit();