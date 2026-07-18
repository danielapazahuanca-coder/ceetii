<?php
require_once __DIR__ . '/config_api.php';
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    header("Location: ../login.php"); exit();
}
$id_docente = $_SESSION['user_id'] ?? 0;
$res  = @file_get_contents(API_BASE_URL . "/notas/docente/{$id_docente}");
$data = json_decode($res, true);
$cursos = $data['data'] ?? [];

// ── Orden: Año (id_nivel) → Paralelo (A,B,C..) → Materia ─────────────────────
usort($cursos, function($a, $b) {
    $nivelA = (int)($a['id_nivel'] ?? 99);
    $nivelB = (int)($b['id_nivel'] ?? 99);
    if ($nivelA !== $nivelB) return $nivelA <=> $nivelB;

    $paraA = strtoupper(trim($a['paralelo'] ?? ''));
    $paraB = strtoupper(trim($b['paralelo'] ?? ''));
    if ($paraA !== $paraB) return strcmp($paraA, $paraB);

    return strcmp($a['materia_nombre'] ?? '', $b['materia_nombre'] ?? '');
});

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Mis Cursos', $current_page, 3);
?>

<style>
    .card-materia {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease;
        height: 100%;
    }
    .card-materia:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,.08);
    }
    .card-materia-header {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .badge-curso {
        background: var(--rojo-elegante, #8d191d);
        color: #fff;
        font-weight: 800;
        font-family: monospace;
        font-size: 15px;
        padding: 4px 10px;
        border-radius: 6px;
        letter-spacing: .5px;
    }
    .card-materia-body { padding: 16px 18px; }
    .card-materia-body .materia-nombre {
        font-weight: 700;
        color: var(--texto-oscuro, #2d3436);
        font-size: 16px;
        margin-bottom: 2px;
    }
    .card-materia-body .materia-sigla {
        font-size: 12px;
        color: #8c98a5;
        font-family: monospace;
        letter-spacing: .5px;
    }
    .dato-mini {
        font-size: 13px;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
    }
    .dato-mini .material-icons { font-size: 16px; color: #b0b8c0; }
    .card-materia-footer {
        padding: 12px 18px;
        background: #fafbfc;
        border-top: 1px solid #f1f3f5;
    }
    .btn-ir-notas {
        width: 100%;
        background: var(--rojo-elegante, #8d191d);
        border: none;
        color: #fff;
        font-weight: 600;
        font-size: 13px;
        padding: 9px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background .15s ease;
    }
    .btn-ir-notas:hover { background: #6e1316; color:#fff; text-decoration:none; }

    .separador-nivel {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: .6px;
        color: var(--rojo-elegante, #8d191d);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 28px 0 14px 0;
    }
    .separador-nivel::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e9ecef;
    }
    .separador-nivel:first-child { margin-top: 4px; }
</style>

<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body p-4 d-flex align-items-center">
        <div class="p-3 rounded-3 me-3" style="background-color: var(--gris-suave, #f4f4f4); color: var(--rojo-elegante, #8d191d);">
            <span class="material-icons fs-1 d-block">menu_book</span>
        </div>
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--texto-oscuro, #2d3436);">Mis Materias Asignadas</h4>
            <p class="text-muted small mb-0">Selecciona un curso para registrar o consultar las calificaciones de tus estudiantes.</p>
        </div>
    </div>
</div>

<?php if (empty($cursos)): ?>
    <div class="alert alert-info d-flex align-items-center gap-2">
        <span class="material-icons">info</span>
        <div>Todavía no tienes materias asignadas para esta gestión. Si crees que es un error, contacta al administrador.</div>
    </div>
<?php else: ?>

    <?php
    $nivel_anterior = null;
    foreach ($cursos as $c):
        $nivel_actual = (int)($c['id_nivel'] ?? 0);
        if ($nivel_actual !== $nivel_anterior):
            $nivel_anterior = $nivel_actual;
    ?>
        <div class="separador-nivel">
            <span class="material-icons" style="font-size:16px;">school</span>
            <?= htmlspecialchars($c['nivel_nombre'] ?? 'Sin Año Definido'); ?>
        </div>
        <div class="row g-3">
    <?php endif; ?>

            <div class="col-md-4 col-lg-3">
                <div class="card-materia">
                    <div class="card-materia-header">
                        <span class="badge-curso"><?= htmlspecialchars(($c['id_nivel'] ?? '') . ($c['paralelo'] ?? '')); ?></span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small px-2 py-1 rounded">
                            <?= htmlspecialchars($c['gestion_varchar'] ?? '—'); ?>
                        </span>
                    </div>
                    <div class="card-materia-body">
                        <div class="materia-nombre"><?= htmlspecialchars($c['materia_nombre'] ?? ''); ?></div>
                        <div class="materia-sigla"><?= htmlspecialchars($c['sigla'] ?? ''); ?></div>
                        <div class="dato-mini">
                            <span class="material-icons">business_center</span>
                            <?= htmlspecialchars($c['carrera_nombre'] ?? '—'); ?>
                        </div>
                    </div>
                    <div class="card-materia-footer">
                        <a href="registro_notas.php?id_curso=<?= (int)$c['id_curso']; ?>&id_materia=<?= (int)$c['id_materia']; ?>&id_gestion=<?= (int)$c['id_gestion']; ?>"
                           class="btn-ir-notas">
                            <span class="material-icons" style="font-size:16px;">edit_note</span> Ver / Registrar Notas
                        </a>
                    </div>
                </div>
            </div>

    <?php
    endforeach;
    echo '</div>'; // cierra el último .row
    ?>

<?php endif; ?>

<?php layout_foot(); ?>