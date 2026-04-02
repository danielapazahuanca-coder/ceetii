<?php include 'header.php'; ?>

<div class="d-flex align-items-center justify-content-center mb-4 p-3 bg-white shadow-sm rounded-3 border-top border-4 border-danger">
    <img src="img/logo.png" alt="Logo CEETII" style="max-height: 70px; margin-right: 20px;">
    <div class="text-start">
        <h2 class="mb-0 fw-bold text-dark" style="letter-spacing: -1px; line-height: 1;">REGISTRAR ACTIVO</h2>
        <p class="text-muted mb-0 small text-uppercase">Formulario de Ingreso - CEETII</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5"> <div class="card shadow border-0 mb-5">
            <div class="card-body p-4">
                <form method="POST" action="guardar.php">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Nombre del Activo</label>
                        <input type="text" name="nombre" class="form-control" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Código / ID</label>
                        <input type="text" name="codigo_activo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Estado</label>
                        <select name="estado_id" class="form-select">
                            <option value="1">Bueno</option>
                            <option value="2">Regular</option>
                            <option value="3">Malo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Ubicación</label>
                        <input type="text" name="ubicacion" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Responsable</label>
                        <input type="text" name="responsable" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Precio (Bs.)</label>
                        <input type="number" step="0.01" name="precio_compra" class="form-control">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="d-grid gap-2 border-top pt-3">
                        <button type="submit" class="btn btn-ceetii py-2">
                             Guardar Activo
                        </button>
                        <a href="activos.php" class="btn btn-link btn-sm text-decoration-none text-muted">
                            Volver al listado
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>