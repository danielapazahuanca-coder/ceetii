<?php
require_once __DIR__ . '/config_api.php';
session_start();
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header("Location: ../login.php");
    exit();
}

$id_estudiante = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_estudiante <= 0) {
    header("Location: vista_cursos.php");
    exit();
}

// Ficha completa del alumno
$res_ficha  = @file_get_contents(API_BASE_URL . "/academico/estudiante/{$id_estudiante}");
$ficha      = json_decode($res_ficha, true);
$estudiante = $ficha['data']['estudiante'] ?? null;
$notas      = $ficha['data']['notas']       ?? [];

if (!$estudiante) {
    header("Location: vista_cursos.php");
    exit();
}

require_once '_layout.php';
$current_page = 'estudiantes.php';
layout_head('Ficha de Estudiante', $current_page, $_SESSION['role_id']);
?>

<style>
    .perfil-card-moderna { background:#fff; border-radius:12px; overflow:hidden; }
    .perfil-banner-header { background:linear-gradient(135deg, var(--rojo-elegante,#8d191d) 0%, #580c0f 100%); padding:2.5rem 2rem; }
    .badge-indicador { background:rgba(255,255,255,.15); backdrop-filter:blur(4px); font-size:.9rem; padding:.5rem .9rem; border:1px solid rgba(255,255,255,.2); }
    .dato-titulo-label { font-size:11px; font-weight:700; text-transform:uppercase; color:#8c98a5; letter-spacing:.5px; margin-bottom:2px; }
    .dato-cuerpo-valor { font-size:15px; color:var(--texto-oscuro,#2d3436); font-weight:500; }
    .barra-progreso-contenedor { background:#e9ecef; border-radius:10px; height:8px; width:100%; overflow:hidden; }
    .barra-progreso-relleno { height:100%; border-radius:10px; transition:width .6s ease; }
    .tabla-grande-ficha { font-size:15px !important; }
    .tabla-grande-ficha thead th { font-size:13px !important; font-weight:700; letter-spacing:.5px; }

    /* Botones de reporte */
    .btn-reporte-grupo { display:flex; flex-wrap:wrap; gap:8px; }
    .btn-reporte { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; font-size:13px; font-weight:600; text-decoration:none; transition:all .15s; border:none; cursor:pointer; }
    .btn-rep-pdf   { background:#dc3545; color:#fff; }
    .btn-rep-pdf:hover   { background:#b02a37; color:#fff; }
    .btn-rep-excel { background:#1d7041; color:#fff; }
    .btn-rep-excel:hover { background:#155231; color:#fff; }
</style>

<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <a href="javascript:history.back()" class="btn btn-white border shadow-sm d-inline-flex align-items-center gap-2 py-2 px-3 text-secondary fw-medium rounded-3">
        <span class="material-icons fs-5">arrow_back</span> Volver
    </a>
</div>

<div class="card perfil-card-moderna border-0 shadow-sm mb-4">
    <div class="perfil-banner-header d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
        <div class="d-flex flex-column flex-md-row align-items-center gap-4 text-center text-md-start">
            <div class="text-white">
                <h3 class="fw-bold mb-2 text-uppercase">
                    <?= htmlspecialchars(($estudiante['apellidos'] ?? '') . ' ' . ($estudiante['nombres'] ?? '')) ?>
                </h3>
                <div class="d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2 mb-1">
                    <span class="badge badge-indicador rounded d-flex align-items-center gap-1 fw-bold">
                        <span class="material-icons fs-6">badge</span>
                        C.I: <?= htmlspecialchars($estudiante['ci'] ?? '') ?>
                        <?= !empty($estudiante['expedido']) ? '(' . htmlspecialchars($estudiante['expedido']) . ')' : '' ?>
                    </span>
                </div>
                <?php if (!empty($estudiante['carrera'])): ?>
                    <div class="text-white fs-6 fw-semibold mt-2 d-flex align-items-center gap-1">
                        <span class="material-icons fs-5">school</span>
                        <?= htmlspecialchars($estudiante['carrera']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-center text-white border-start border-white-50 ps-md-4 py-1 mt-3 mt-md-0 d-none d-sm-block">
            <div class="small text-white-50 fw-bold" style="font-size:11px; text-transform:uppercase; letter-spacing:.8px;">Aula Asignada</div>
            <div class="fs-4 fw-bold text-warning"><?= htmlspecialchars($estudiante['nivel'] ?? '—') ?></div>
            <div class="fw-bold fs-5 font-monospace">Paralelo "<?= htmlspecialchars($estudiante['paralelo'] ?? '—') ?>"</div>
        </div>
    </div>

    <div class="card-body p-4 p-lg-5">
        <div class="row g-4">
            <div class="col-12">
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2" style="color:var(--rojo-elegante);">
                    <span class="material-icons">contact_page</span> Información de Matrícula
                </h5>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="dato-titulo-label">Cédula de Identidad</div>
                        <div class="dato-cuerpo-valor font-monospace fw-bold text-dark fs-6">
                            <?= htmlspecialchars($estudiante['ci'] ?? '—') ?> <?= htmlspecialchars($estudiante['expedido'] ?? '') ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="dato-titulo-label">Teléfono</div>
                        <div class="dato-cuerpo-valor text-secondary fw-semibold"><?= htmlspecialchars($estudiante['telefono'] ?? '—') ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="dato-titulo-label">Gestión Actual</div>
                        <div class="dato-cuerpo-valor font-monospace fw-bold text-success"><?= htmlspecialchars($estudiante['gestion'] ?? '—') ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="dato-titulo-label">Paralelo</div>
                        <div class="dato-cuerpo-valor font-monospace fw-bold text-primary fs-6"><?= htmlspecialchars($estudiante['paralelo'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="dato-titulo-label">Carrera</div>
                        <div class="dato-cuerpo-valor fw-bold text-dark fs-6"><?= htmlspecialchars($estudiante['carrera'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="dato-titulo-label">Año Académico</div>
                        <div class="dato-cuerpo-valor fw-medium"><?= htmlspecialchars($estudiante['nivel'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm bg-white">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 fw-bold text-dark fs-5">
            <span class="material-icons text-secondary">format_list_numbered</span>
            <span>Planilla Centralizadora de Notas</span>
        </div>
        <div class="btn-reporte-grupo">
            <a href="generar_reporte.php?action=historial&id_estudiante=<?= $id_estudiante ?>" target="_blank" class="btn-reporte btn-rep-pdf">
                <span class="material-icons" style="font-size:16px;">picture_as_pdf</span> PDF
            </a>
            <a href="generar_reporte.php?action=historial&id_estudiante=<?= $id_estudiante ?>&format=excel" class="btn-reporte btn-rep-excel">
                <span class="material-icons" style="font-size:16px;">grid_on</span> Excel
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0 tabla-grande-ficha">
            <thead class="table-light text-muted text-center">
                <tr>
                    <th class="text-start" style="width:110px; padding-left:16px;">Sigla</th>
                    <th class="text-start">Asignatura</th>
                    <th class="text-start" style="width:220px;">Docente</th>
                    <th style="width:75px;">1° Bim</th>
                    <th style="width:75px;">2° Bim</th>
                    <th style="width:75px;">3° Bim</th>
                    <th style="width:75px;">4° Bim</th>
                    <th style="width:160px;">Promedio Final</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($notas)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5 fs-6">
                        <span class="material-icons fs-2 mb-2 text-black-50 d-block">folder_open</span>
                        No hay calificaciones registradas para este período.
                    </td>
                </tr>
            <?php else:
                foreach ($notas as $n):
                    $b1   = (int)($n['primer_bimestre']  ?? 0);
                    $b2   = (int)($n['segundo_bimestre'] ?? 0);
                    $b3   = (int)($n['tercer_bimestre']  ?? 0);
                    $b4   = (int)($n['cuarto_bimestre']  ?? 0);
                    $prom = (int)($n['promedio_final']   ?? round(($b1+$b2+$b3+$b4)/4));
                    $aprobado     = $prom >= 61;
                    $badge_est    = $aprobado ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle';
                    $color_barra  = $aprobado ? 'var(--bs-success,#28a745)' : 'var(--bs-danger,#dc3545)';
                    $ancho_barra  = min(100, $prom) . '%';
            ?>
                <tr>
                    <td class="font-monospace fw-bold text-dark fs-6" style="padding-left:16px;"><?= htmlspecialchars($n['sigla'] ?? '') ?></td>
                    <td class="fw-bold text-dark fs-6"><?= htmlspecialchars($n['materia'] ?? '') ?></td>
                    <td class="text-muted fw-medium" style="max-width:220px;">
                        <?php if (!empty($n['docente'])): ?>
                            <span class="d-inline-flex align-items-center gap-1">
                                <span class="material-icons fs-6 text-black-50">person_outline</span>
                                <?= htmlspecialchars($n['docente']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-black-50 small">— Sin Docente —</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center font-monospace fw-bold fs-6 <?= $b1 >= 61 ? 'text-success' : ($b1 > 0 ? 'text-danger' : 'text-muted') ?>"><?= $b1 ?></td>
                    <td class="text-center font-monospace fw-bold fs-6 <?= $b2 >= 61 ? 'text-success' : ($b2 > 0 ? 'text-danger' : 'text-muted') ?>"><?= $b2 ?></td>
                    <td class="text-center font-monospace fw-bold fs-6 <?= $b3 >= 61 ? 'text-success' : ($b3 > 0 ? 'text-danger' : 'text-muted') ?>"><?= $b3 ?></td>
                    <td class="text-center font-monospace fw-bold fs-6 <?= $b4 >= 61 ? 'text-success' : ($b4 > 0 ? 'text-danger' : 'text-muted') ?>"><?= $b4 ?></td>
                    <td>
                        <div class="d-flex flex-column align-items-center px-2">
                            <span class="badge border font-monospace px-3 rounded-pill <?= $badge_est ?>" style="font-size:14px; font-weight:700; min-width:55px; padding:5px 12px;"><?= $prom ?></span>
                            <div class="barra-progreso-contenedor mt-1">
                                <div class="barra-progreso-relleno" style="width:<?= $ancho_barra ?>; background-color:<?= $color_barra ?>;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php layout_foot(); ?>