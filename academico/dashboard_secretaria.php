<?php
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 2) {
    header("Location: ../login.php"); 
    exit();
}
require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Inicio — Secretaria', $current_page, 2);
?>

<style>
    /* Efecto de elevación interactivo para las tarjetas del panel */
    .tarjeta-modulo {
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        border-radius: 12px;
        background-color: #ffffff;
    }
    .tarjeta-modulo:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
</style>

<div class="d-flex align-items-center gap-2 mb-4 pb-3 border-bottom">
    <span class="material-icons text-secondary fs-2">home</span>
    <h2 class="h4 fw-bold text-dark mb-0">Panel de Control de Secretaría</h2>
</div>

<div class="card border-0 shadow-sm bg-white mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1" style="color: var(--rojo-elegante, #8d191d);">¡Bienvenido al Sistema Académico!</h5>
        <p class="text-secondary small mb-0 opacity-85">
            Como miembro del equipo de secretaría, tiene acceso a las herramientas globales de matriculación, revisión curricular y centralización de paralelos. Seleccione una de las siguientes opciones para comenzar a operar.
        </p>
    </div>
</div>

<div class="row g-4">

    <div class="col-12 col-md-6 col-lg-4">
        <a href="estudiantes.php" class="text-decoration-none d-block h-100">
            <div class="card h-100 border-0 border-start border-4 border-primary shadow-sm tarjeta-modulo p-3">
                <div class="card-body d-flex align-items-start gap-3 p-2">
                    <div class="p-3 bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <span class="material-icons fs-2">person_add</span>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Registrar Estudiantes</h6>
                        <p class="text-muted small mb-0" style="font-size: 13px; line-height: 1.4;">Añade nuevos alumnos al sistema e inscríbelos en sus respectivas carreras.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <a href="materias.php" class="text-decoration-none d-block h-100">
            <div class="card h-100 border-0 border-start border-4 border-purple shadow-sm tarjeta-modulo p-3" style="border-left-color: #9b59b6 !important;">
                <div class="card-body d-flex align-items-start gap-3 p-2">
                    <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background-color: #f5eef8; color: #9b59b6;">
                        <span class="material-icons fs-2">auto_stories</span>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Materias Curriculares</h6>
                        <p class="text-muted small mb-0" style="font-size: 13px; line-height: 1.4;">Consulta, verifica y gestiona el plan de estudios vigente de la institución.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <a href="cursos.php" class="text-decoration-none d-block h-100">
            <div class="card h-100 border-0 border-start border-4 border-warning shadow-sm tarjeta-modulo p-3" style="border-left-color: #e67e22 !important;">
                <div class="card-body d-flex align-items-start gap-3 p-2">
                    <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background-color: #fdf2e9; color: #e67e22;">
                        <span class="material-icons fs-2">room_preferences</span>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Cursos / Paralelos</h6>
                        <p class="text-muted small mb-0" style="font-size: 13px; line-height: 1.4;">Monitorea la distribución de las aulas habilitadas en la gestión académica actual.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <a href="vista_cursos.php" class="text-decoration-none d-block h-100">
            <div class="card h-100 border-0 border-start border-4 border-success shadow-sm tarjeta-modulo p-3" style="border-left-color: #1abc9c !important;">
                <div class="card-body d-flex align-items-start gap-3 p-2">
                    <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background-color: #e8f8f5; color: #1abc9c;">
                        <span class="material-icons fs-2">view_list</span>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Alumnos por Curso</h6>
                        <p class="text-muted small mb-0" style="font-size: 13px; line-height: 1.4;">Visualiza las listas centralizadas de alumnos asignados a cada paralelo.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <a href="reportes.php" class="text-decoration-none d-block h-100">
            <div class="card h-100 border-0 border-start border-4 shadow-sm tarjeta-modulo p-3" style="border-left-color: #8d191d !important;">
                <div class="card-body d-flex align-items-start gap-3 p-2">
                    <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background-color: #fbebeb; color: #8d191d;">
                        <span class="material-icons fs-2">summarize</span>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;">Centro de Reportes</h6>
                        <p class="text-muted small mb-0" style="font-size: 13px; line-height: 1.4;">Genera historiales, actas, centralizadores, boletines y listados en PDF o Excel.</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<?php layout_foot(); ?>