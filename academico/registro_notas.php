<?php
require_once __DIR__ . '/config_api.php';
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    header("Location: ../login.php");
    exit();
}

$id_docente_sesion = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0);
$id_curso   = isset($_GET['id_curso'])   ? (int)$_GET['id_curso']   : 0;
$id_materia = isset($_GET['id_materia']) ? (int)$_GET['id_materia'] : 0;
$id_gestion = isset($_GET['id_gestion']) ? (int)$_GET['id_gestion'] : 0;

$msg = '';

// ── Guardar notas ──────────────────────────────────────────────────────────────
if (isset($_POST['guardar_notas'])) {
    $notas_array = [];
    if (isset($_POST['notas']) && is_array($_POST['notas'])) {
        foreach ($_POST['notas'] as $id_est => $valores) {
            $b1 = (int)($valores['b1'] ?? 0);
            $b2 = (int)($valores['b2'] ?? 0);
            $b3 = (int)($valores['b3'] ?? 0);
            $b4 = (int)($valores['b4'] ?? 0);
            $notas_array[] = [
                'id_estudiante'    => (int)$id_est,
                'b1'               => $b1,
                'b2'               => $b2,
                'b3'               => $b3,
                'b4'               => $b4,
                'primer_bimestre'  => $b1,
                'segundo_bimestre' => $b2,
                'tercer_bimestre'  => $b3,
                'cuarto_bimestre'  => $b4,
            ];
        }
    }

    $payload = [
        'id_materia' => $id_materia,
        'id_curso'   => $id_curso,
        'id_gestion' => $id_gestion,
        'id_docente' => $id_docente_sesion,
        'notas'      => $notas_array,
    ];

    $opts = ['http' => [
        'header'        => "Content-Type: application/json\r\n",
        'method'        => 'POST',
        'content'       => json_encode($payload),
        'ignore_errors' => true,
    ]];

    $res = json_decode(
        @file_get_contents(API_BASE_URL . "/notas", false, stream_context_create($opts)),
        true
    );

    if ($res && isset($res['status']) && $res['status'] === 'success') {
        $msg = '<div class="alert alert-success d-flex align-items-center gap-2"><span class="material-icons">check_circle</span><div><b>¡Éxito!</b> Notas guardadas correctamente y promedios actualizados en el sistema.</div></div>';
    } else {
        $error_api = $res['message'] ?? 'Error desconocido en el servidor de la API.';
        $msg = '<div class="alert alert-danger d-flex align-items-center gap-2"><span class="material-icons">error</span><div><b>Error al guardar:</b> ' . htmlspecialchars($error_api) . '</div></div>';
    }
}

// ── Listar estudiantes con notas actuales ──────────────────────────────────────
$url_estudiantes = API_BASE_URL . "/notas/estudiantes?id_curso={$id_curso}&id_materia={$id_materia}";
$opts_get = ['http' => [
    'header'        => "Content-Type: application/json\r\n",
    'method'        => 'GET',
    'ignore_errors' => true,
]];

$res_estudiantes  = @file_get_contents($url_estudiantes, false, stream_context_create($opts_get));
$data_estudiantes = json_decode($res_estudiantes, true);
$estudiantes      = $data_estudiantes['data'] ?? [];

// Datos del curso/materia para el encabezado (viene incluido en la respuesta de estudiantes)
$info_curso = $estudiantes[0] ?? [];
$label_curso = trim((($info_curso['nombre_nivel'] ?? '') ) . ' ' . ($info_curso['paralelo'] ?? ''));

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Registro de Notas', $current_page, 3);
?>

<style>
    .card-planilla { background:#fff; border-radius:12px; overflow:hidden; }
    .card-planilla .card-header-custom {
        background: linear-gradient(135deg, var(--rojo-elegante,#8d191d) 0%, #580c0f 100%);
        color:#fff; padding: 1.25rem 1.5rem;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    }
    .card-header-custom h4 { color:#fff; margin:0; }
    .card-header-custom .subt { color:rgba(255,255,255,.8); font-size:13px; margin-top:2px; }

    .tabla-notas th { text-align:center; vertical-align:middle !important; font-size:13px; text-transform:uppercase; letter-spacing:.4px; }
    .tabla-notas td { vertical-align:middle !important; padding: 8px 6px !important; }

    /* ── REGULACIÓN DE CASILLAS (Tamaño más óptimo y estilizado) ── */
    .celda-nota { text-align:center; padding:6px 4px !important; }
    .nota-caja {
        width:55px; height:36px; margin:0 auto; text-align:center;
        font-size:15px; font-weight:700; color:#2d3436;
        background:#f4f6f9; border:2px solid #e1e6ec; border-radius:6px;
        outline:none; transition:all .15s ease;
        -moz-appearance: textfield;
    }
    .nota-caja::-webkit-outer-spin-button, .nota-caja::-webkit-inner-spin-button {
        -webkit-appearance:none; margin:0;
    }
    .nota-caja:disabled {
        background:#eef1f4; border-style:dashed; border-color:#dfe3e8; color:#99a3ad; cursor:not-allowed;
    }
    .nota-caja:not(:disabled):hover { border-color: var(--rojo-elegante,#8d191d); }
    .nota-caja:not(:disabled):focus {
        border-color: var(--rojo-elegante,#8d191d); background:#fff;
        box-shadow: 0 0 0 3px rgba(141,25,29,0.12);
    }
    .nota-caja.nota-aprobada:not(:disabled)  { border-color:#2e7d32; color:#1e5e22; background:#f1f9f1; }
    .nota-caja.nota-reprobada:not(:disabled) { border-color: var(--rojo-elegante,#8d191d); color:#6e1316; background:#fdf3f3; }

    /* Promedio optimizado */
    .label-promedio {
        font-size:14px; font-weight:700; padding:4px 12px; border-radius:12px; display:inline-block; min-width:45px;
    }

    #barra-modo-edicion {
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
        padding:14px 20px; border-radius:10px; margin-bottom:20px;
        border:1px solid #e9ecef; background:#f8f9fa; transition: all .2s ease;
    }
    #barra-modo-edicion.modo-activo { background:#fdf3f3; border-color:#f0c4c6; }
    #barra-modo-edicion .estado-texto { font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px; }
    #barra-modo-edicion.modo-activo .estado-texto { color:#6e1316; }
    @media (max-width: 480px) {
        #btn-toggle-edicion { width: 100%; justify-content: center; }
    }

    #btn-toggle-edicion {
        background: var(--rojo-elegante,#8d191d); border:none; color:#fff;
        font-weight:600; padding:8px 16px; border-radius:7px;
        display:flex; align-items:center; gap:6px; font-size:13px;
        transition: background .15s ease;
    }
    #btn-toggle-edicion:hover { background:#6e1316; color:#fff; }

    .fila-modificada { background-color:#fdf3f3 !important; }

    .btn-guardar-notas {
        background:#1d7041; border:none; color:#fff; font-weight:700; padding:12px 28px;
        border-radius:8px; display:inline-flex; align-items:center; gap:8px; font-size:15px;
        transition: background .15s ease;
    }
    .btn-guardar-notas:hover:not(:disabled) { background:#155231; color:#fff; }
    .btn-guardar-notas:disabled { background:#a8c3b3; cursor:not-allowed; color:#fff; }

    .btn-volver-cursos {
        display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px;
        background:#fff; border:1px solid #dee2e6; color:#495057; font-weight:600; font-size:13px;
        text-decoration:none; transition: all .15s ease;
    }
    .btn-volver-cursos:hover { background:#f8f9fa; color:#495057; text-decoration:none; }

    .btn-acta-pdf {
        display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:7px;
        background:var(--rojo-elegante,#8d191d); color:#fff; font-weight:600; font-size:13px;
        text-decoration:none; transition: background .15s ease;
    }
    .btn-acta-pdf:hover { background:#6e1316; color:#fff; text-decoration:none; }
</style>

<div class="mb-3">
    <a href="dashboard_docente.php" class="btn-volver-cursos">
        <span class="material-icons" style="font-size:16px;">arrow_back</span> Volver a Mis Cursos
    </a>
</div>

<?= $msg; ?>

<div class="card card-planilla border-0 shadow-sm mb-4">
    <div class="card-header-custom">
        <div>
            <h4><span class="material-icons align-middle me-1">edit_note</span> Planilla de Calificaciones Bimestrales</h4>
            <?php if ($label_curso): ?>
                <div class="subt">Curso: <b><?= htmlspecialchars($label_curso); ?></b></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body p-4">

        <!-- ── BARRA DE MODO EDICIÓN ── -->
        <div id="barra-modo-edicion">
            <div class="estado-texto" id="texto-estado-modo">
                <span class="material-icons">lock</span> Planilla en modo solo lectura. Las notas están protegidas.
            </div>
            <button type="button" id="btn-toggle-edicion">
                <span class="material-icons" style="font-size:16px;">lock_open</span> Habilitar Edición
            </button>
        </div>

        <form action="registro_notas.php?id_curso=<?php echo $id_curso; ?>&id_materia=<?php echo $id_materia; ?>&id_gestion=<?php echo $id_gestion; ?>" method="POST" id="form-notas">
            <input type="hidden" name="guardar_notas" value="1">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 tabla-notas" id="tabla-notas">
                    <thead class="table-light">
                        <tr>
                            <th style="width:120px;">C.I.</th>
                            <th class="text-start">Apellidos y Nombres</th>
                            <th style="width:85px;">1er Bim</th>
                            <th style="width:85px;">2do Bim</th>
                            <th style="width:85px;">3er Bim</th>
                            <th style="width:85px;">4to Bim</th>
                            <th style="width:110px;">Promedio Final</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($estudiantes)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <span class="material-icons fs-2 mb-2 text-black-50 d-block">folder_open</span>
                                No hay estudiantes inscritos en este curso para la materia seleccionada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $e): ?>
                        <?php
                            $id_est   = $e['id_estudiante'];
                            $b1       = (int)($e['primer_bimestre']  ?? 0);
                            $b2       = (int)($e['segundo_bimestre'] ?? 0);
                            $b3       = (int)($e['tercer_bimestre']  ?? 0);
                            $b4       = (int)($e['cuarto_bimestre']  ?? 0);
                            $promedio = (int)round(($b1 + $b2 + $b3 + $b4) / 4);
                            $nombre_completo = ($e['apellidos'] ?? '') . ' ' . ($e['nombres'] ?? '');

                            $claseNota = function($val) {
                                if ($val <= 0) return '';
                                return $val >= 61 ? 'nota-aprobada' : 'nota-reprobada';
                            };
                        ?>
                        <tr class="fila-estudiante" data-nombre="<?php echo htmlspecialchars($nombre_completo, ENT_QUOTES); ?>">
                            <td class="text-center font-monospace fw-bold text-secondary">
                                <?php echo htmlspecialchars($e['ci'] ?? ''); ?>
                            </td>
                            <td class="fw-bold text-dark">
                                <?php echo htmlspecialchars($nombre_completo); ?>
                            </td>
                            <td class="celda-nota">
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3"
                                    name="notas[<?php echo $id_est; ?>][b1]"
                                    value="<?php echo $b1; ?>"
                                    data-original="<?php echo $b1; ?>"
                                    data-label="1er Bimestre"
                                    class="nota-caja bim-input <?php echo $claseNota($b1); ?>" disabled>
                            </td>
                            <td class="celda-nota">
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3"
                                    name="notas[<?php echo $id_est; ?>][b2]"
                                    value="<?php echo $b2; ?>"
                                    data-original="<?php echo $b2; ?>"
                                    data-label="2do Bimestre"
                                    class="nota-caja bim-input <?php echo $claseNota($b2); ?>" disabled>
                            </td>
                            <td class="celda-nota">
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3"
                                    name="notas[<?php echo $id_est; ?>][b3]"
                                    value="<?php echo $b3; ?>"
                                    data-original="<?php echo $b3; ?>"
                                    data-label="3er Bimestre"
                                    class="nota-caja bim-input <?php echo $claseNota($b3); ?>" disabled>
                            </td>
                            <td class="celda-nota">
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3"
                                    name="notas[<?php echo $id_est; ?>][b4]"
                                    value="<?php echo $b4; ?>"
                                    data-original="<?php echo $b4; ?>"
                                    data-label="4to Bimestre"
                                    class="nota-caja bim-input <?php echo $claseNota($b4); ?>" disabled>
                            </td>
                            <td class="text-center">
                                <span class="label-promedio <?php echo ($promedio >= 61) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?>">
                                    <?php echo $promedio; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($estudiantes)): ?>
            <div class="text-end mt-4">
                <button type="submit" name="guardar_notas" id="btn-guardar-notas" class="btn-guardar-notas" disabled>
                    <span class="material-icons">save</span> Guardar Calificaciones
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (!empty($estudiantes)): ?>
<a href="generar_reporte.php?action=acta&format=pdf&id_curso=<?php echo $id_curso; ?>&id_materia=<?php echo $id_materia; ?>" target="_blank" class="btn-acta-pdf">
    <span class="material-icons" style="font-size:18px;">picture_as_pdf</span> Imprimir Acta PDF
</a>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var modoEdicionActivo = false;

document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.bim-input');
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            let val = this.value.replace(/[^0-9]/g, '');
            val = val.replace(/^0+(?=\d)/, '');
            if (val !== '') {
                let num = parseInt(val, 10);
                if (num > 100) num = 100;
                val = String(num);
            }
            this.value = val;
            recalcularFila(this);
        });

        input.addEventListener('blur', function () {
            if (this.value === '') {
                this.value = '0';
                recalcularFila(this);
            }
        });
    });
});

function recalcularFila(input) {
    const fila = input.closest('.fila-estudiante');
    const bimInputs = fila.querySelectorAll('.bim-input');

    let suma = 0;
    bimInputs.forEach(inBim => {
        const num = parseInt(inBim.value) || 0;
        suma += num;
        inBim.classList.remove('nota-aprobada', 'nota-reprobada');
        if (num > 0) {
            inBim.classList.add(num >= 61 ? 'nota-aprobada' : 'nota-reprobada');
        }
    });

    const promedio = Math.round(suma / 4);
    const contenedorPromedio = fila.querySelector('.label-promedio');
    contenedorPromedio.textContent = promedio;

    contenedorPromedio.classList.remove('bg-danger-subtle','text-danger','border-danger-subtle','bg-success-subtle','text-success','border-success-subtle');
    if (promedio >= 61) {
        contenedorPromedio.classList.add('bg-success-subtle','text-success','border-success-subtle');
    } else {
        contenedorPromedio.classList.add('bg-danger-subtle','text-danger','border-danger-subtle');
    }

    let huboCambio = false;
    bimInputs.forEach(inBim => {
        if (String(inBim.value) !== String(inBim.dataset.original)) huboCambio = true;
    });
    fila.classList.toggle('fila-modificada', huboCambio);
}

$('#btn-toggle-edicion').on('click', function() {
    if (!modoEdicionActivo) {
        Swal.fire({
            title: '¿Habilitar edición de notas?',
            html: 'Estás por modificar el <b>registro oficial de calificaciones</b> de tus estudiantes.<br>Ingresa los valores con cuidado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, habilitar edición',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#8d191d',
        }).then((result) => {
            if (result.isConfirmed) activarEdicion();
        });
    } else {
        Swal.fire({
            title: '¿Cancelar edición?',
            text: 'Se descartarán los cambios no guardados y la planilla volverá a bloquearse.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar cambios',
            cancelButtonText: 'Seguir editando',
            confirmButtonColor: '#8d191d',
        }).then((result) => {
            if (result.isConfirmed) desactivarEdicion(true);
        });
    }
});

function activarEdicion() {
    modoEdicionActivo = true;
    $('.bim-input').prop('disabled', false);
    $('#btn-guardar-notas').prop('disabled', false);
    $('#btn-toggle-edicion').html('<span class="material-icons" style="font-size:16px;">lock</span> Cancelar Edición');
    $('#texto-estado-modo').html('<span class="material-icons">lock_open</span> Modo edición activo — recuerda revisar bien antes de guardar.');
    $('#barra-modo-edicion').addClass('modo-activo');
}

function desactivarEdicion(restaurarValores) {
    modoEdicionActivo = false;
    if (restaurarValores) {
        $('.bim-input').each(function() {
            $(this).val($(this).data('original'));
            recalcularFila(this);
        });
    }
    $('.bim-input').prop('disabled', true);
    $('#btn-guardar-notas').prop('disabled', true);
    $('#btn-toggle-edicion').html('<span class="material-icons" style="font-size:16px;">lock_open</span> Habilitar Edición');
    $('#texto-estado-modo').html('<span class="material-icons">lock</span> Planilla en modo solo lectura. Las notas están protegidas.');
    $('#barra-modo-edicion').removeClass('modo-activo');
}

$('#form-notas').on('submit', function(e) {
    e.preventDefault();

    let cambios = [];
    $('.fila-estudiante').each(function() {
        const nombre = $(this).data('nombre');
        $(this).find('.bim-input').each(function() {
            const original = $(this).data('original');
            const nuevo = $(this).val();
            if (String(original) !== String(nuevo)) {
                cambios.push(`<b>${nombre}</b> — ${$(this).data('label')}: ${original} → <b>${nuevo}</b>`);
            }
        });
    });

    if (cambios.length === 0) {
        Swal.fire('Sin cambios', 'No modificaste ninguna nota respecto a lo ya guardado.', 'info');
        return;
    }

    const listaHtml = '<div style="text-align:left; max-height:220px; overflow-y:auto; font-size:13px;">' +
        cambios.map(c => `<div class="mb-1">• ${c}</div>`).join('') +
        '</div>';

    Swal.fire({
        title: `Confirmar ${cambios.length} cambio(s)`,
        html: listaHtml,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar definitivamente',
        cancelButtonText: 'Revisar de nuevo',
        confirmButtonColor: '#1d7041',
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
});
</script>
<?php layout_foot(); ?>