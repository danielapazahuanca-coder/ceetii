<?php
require_once __DIR__ . '/config_api.php';
// PÁGINA PÚBLICA — sin session_start(), sin verificación de rol, sin _layout.php
$error = '';
$estudiante = null;
$notas = [];
$id_estudiante = 0;
if (isset($_POST['consultar'])) {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $ci              = trim($_POST['ci'] ?? '');
    $partes = preg_split('/\s+/', $nombre_completo, -1, PREG_SPLIT_NO_EMPTY);
    $nombre    = $partes[0] ?? '';
    $apellido1 = $partes[1] ?? '';
    $apellido2 = isset($partes[2]) ? implode(' ', array_slice($partes, 2)) : '';
    if ($nombre === '' || $apellido1 === '' || $apellido2 === '' || $ci === '') {
        $error = 'Completa tu primer nombre, tus dos apellidos y tu CI.';
    } else {
        $query = http_build_query([
            'nombre'    => $nombre,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'ci'        => $ci,
        ]);
        $url = API_BASE_URL . "/academico/buscar?{$query}";
        $res = @file_get_contents($url);
        $json = json_decode($res, true);
        if ($json && $json['status'] === 'success') {
            $estudiante    = $json['data']['estudiante'] ?? null;
            $notas         = $json['data']['notas']      ?? [];
            $id_estudiante = (int)($estudiante['id_estudiante'] ?? 0);
        } else {
            $error = $json['message'] ?? 'No se pudo realizar la consulta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Consulta de Notas - CETI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        body { background:#f2f2f2; }
        :root { --rojo-elegante:#8d191d; --texto-oscuro:#2d3436; }
        .perfil-card-moderna { background:#fff; border-radius:12px; overflow:hidden; }
        .perfil-banner-header { background:linear-gradient(135deg, var(--rojo-elegante) 0%, #580c0f 100%); padding:2.5rem 2rem; }
        .badge-indicador { background:rgba(255,255,255,.15); backdrop-filter:blur(4px); font-size:.9rem; padding:.5rem .9rem; border:1px solid rgba(255,255,255,.2); }
        .dato-titulo-label { font-size:11px; font-weight:700; text-transform:uppercase; color:#8c98a5; letter-spacing:.5px; margin-bottom:2px; }
        .dato-cuerpo-valor { font-size:15px; color:var(--texto-oscuro); font-weight:500; }
        .barra-progreso-contenedor { background:#e9ecef; border-radius:10px; height:8px; width:100%; overflow:hidden; }
        .barra-progreso-relleno { height:100%; border-radius:10px; }
        .tabla-grande-ficha { font-size:15px !important; }
        .tabla-grande-ficha thead th { font-size:13px !important; font-weight:700; letter-spacing:.5px; }
        .card-consulta { max-width:480px; margin:0 auto 2rem auto; }
    </style>
</head>
<body>
<div class="container py-4">
    <!-- ── FORMULARIO DE CONSULTA (Solo se muestra si NO hay estudiante cargado) ── -->
    <?php if (!$estudiante): ?>
    <div class="card card-consulta border-0 shadow-sm">
        <div class="card-body p-4">
            <h4 class="fw-bold text-center mb-1" style="color:var(--rojo-elegante);">Consulta de Notas</h4>
            <p class="text-muted text-center small mb-4">Ingresa tu primer nombre y tus apellidos</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nombre y Apellidos</label>
                    <input type="text" name="nombre_completo" class="form-control capitalize-input" required
                           value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>"
                           placeholder="Ej: Juan García López">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Cédula de Identidad (C.I.)</label>
                    <input type="text" name="ci" class="form-control ci-input" required
                           value="<?= htmlspecialchars($_POST['ci'] ?? '') ?>"
                           placeholder="Ej: 1234567890"
                           maxlength="10"
                           inputmode="numeric">
                </div>
                <button type="submit" name="consultar" class="btn btn-danger w-100 fw-bold"
                        style="background:var(--rojo-elegante); border:none;">
                    Consultar Notas
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="../login.php" class="small text-muted">&larr; Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($estudiante): ?>
    
    <!-- Botón para regresar a realizar otra consulta -->
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
            <span class="material-icons fs-6">arrow_back</span> Realizar otra consulta
        </a>
        <a href="../login.php" class="small text-muted">Ir al inicio de sesión</a>
    </div>
    <!-- ── TARJETA DE PERFIL ── -->
    <div class="card perfil-card-moderna border-0 shadow-sm mb-4">
        <div class="perfil-banner-header d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
            <div class="text-white text-center text-md-start">
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
            <div class="text-center text-white border-start border-white-50 ps-md-4 py-1 mt-3 mt-md-0 d-none d-sm-block">
                <div class="small text-white-50 fw-bold" style="font-size:11px; text-transform:uppercase; letter-spacing:.8px;">Aula Asignada</div>
                <div class="fs-4 fw-bold text-warning"><?= htmlspecialchars($estudiante['nivel'] ?? '—') ?></div>
                <div class="fw-bold fs-5 font-monospace">Paralelo "<?= htmlspecialchars($estudiante['paralelo'] ?? '—') ?>"</div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-12">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:var(--rojo-elegante);">
                        <span class="material-icons">contact_page</span> Información de Matrícula
                    </h5>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="dato-titulo-label">Gestión Actual</div>
                            <div class="dato-cuerpo-valor font-monospace fw-bold text-success"><?= htmlspecialchars($estudiante['gestion'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="dato-titulo-label">Año Académico</div>
                            <div class="dato-cuerpo-valor fw-medium"><?= htmlspecialchars($estudiante['nivel'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="dato-titulo-label">Carrera</div>
                            <div class="dato-cuerpo-valor fw-bold text-dark"><?= htmlspecialchars($estudiante['carrera'] ?? '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ── TABLA DE NOTAS ── -->
    <div class="card border-0 shadow-sm bg-white">
        <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
            <span class="material-icons text-secondary">format_list_numbered</span>
            <span class="fw-bold text-dark fs-5">Planilla Centralizadora de Notas</span>
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
                        $b1 = (int)($n['primer_bimestre'] ?? 0);
                        $b2 = (int)($n['segundo_bimestre'] ?? 0);
                        $b3 = (int)($n['tercer_bimestre'] ?? 0);
                        $b4 = (int)($n['cuarto_bimestre'] ?? 0);
                        $prom = (int)($n['promedio_final'] ?? round(($b1+$b2+$b3+$b4)/4));
                        $aprobado = $prom >= 61;
                        $badge_est = $aprobado ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle';
                        $color_barra = $aprobado ? '#28a745' : '#dc3545';
                        $ancho_barra = min(100, $prom) . '%';
                ?>
                    <tr>
                        <td class="font-monospace fw-bold text-dark fs-6" style="padding-left:16px;"><?= htmlspecialchars($n['sigla'] ?? '') ?></td>
                        <td class="fw-bold text-dark fs-6"><?= htmlspecialchars($n['materia'] ?? '') ?></td>
                        <td class="text-muted fw-medium">
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
                                <span class="badge border font-monospace px-3 rounded-pill <?= $badge_est ?>"
                                      style="font-size:14px; font-weight:700; min-width:55px; padding:5px 12px;">
                                    <?= $prom ?>
                                </span>
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
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.capitalize-input').forEach(input => {
    input.addEventListener('input', function() {
        // Permitir solo letras, espacios y acentos
        let valor = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');

        // Capitalizar la primera letra de cada palabra
        valor = valor.toLowerCase().replace(/(?:^|\s)\S/g, letra => letra.toUpperCase());

        this.value = valor;
    });
});
// Validación para el campo de CI: solo 10 dígitos
document.querySelector('.ci-input').addEventListener('input', function() {
    // Permitir solo números
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
});
</script>
</body>
</html>