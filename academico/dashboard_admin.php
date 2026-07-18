<?php
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header("Location: ../login.php"); 
    exit();
}

require_once '_layout.php';
$current_page = basename($_SERVER['PHP_SELF']);
layout_head('Inicio — Administración', $current_page, 1);
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
    <div class="d-flex align-items-center justify-content-center mb-4 p-3 bg-white shadow-sm rounded-3 border-top border-4 border-danger">
        <img src="../inventario/img/logo.png" alt="Logo CEETII" style="max-height: 75px; margin-right: 25px;" onerror="this.src='img/logo.png';">
        <div class="text-start">
            <h2 class="mb-0 fw-bold text-dark" style="letter-spacing: -1px; line-height: 1;">SISTEMA ACADÉMICO</h2>
            <p class="text-muted mb-0 small text-uppercase" style="letter-spacing: 1px;">Control del Alumnado y Notas - CEETII</p>
        </div>
    </div>


<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="usuarios.php" class="text-decoration-none d-block h-100">
            <div class="card tarjeta-modulo border-0 shadow-sm p-3 bg-white h-100 d-flex flex-row align-items-center gap-3">
                <div class="p-3 bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center">
                    <span class="material-icons fs-3">group</span>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px;">Módulo Activo</div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Control de Usuarios</h5>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="gestiones.php" class="text-decoration-none d-block h-100">
            <div class="card tarjeta-modulo border-0 shadow-sm p-3 bg-white h-100 d-flex flex-row align-items-center gap-3">
                <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center">
                    <span class="material-icons fs-3">calendar_month</span>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px;">Planificación</div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Gestiones Académicas</h5>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="estudiantes.php" class="text-decoration-none d-block h-100">
            <div class="card tarjeta-modulo border-0 shadow-sm p-3 bg-white h-100 d-flex flex-row align-items-center gap-3">
                <div class="p-3 bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                    <span class="material-icons fs-3">school</span>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px;">Alumnado</div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Registro de Estudiantes</h5>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <a href="carreras.php" class="text-decoration-none d-block h-100">
            <div class="card tarjeta-modulo border-0 shadow-sm p-3 bg-white h-100 d-flex flex-row align-items-center gap-3">
                <div class="p-3 bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                    <span class="material-icons fs-3">auto_stories</span>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px;">Currícula</div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 15px;">Carreras y Materias</h5>
                </div>
            </div>
        </a>
    </div>
</div>



<?php layout_foot(); ?>