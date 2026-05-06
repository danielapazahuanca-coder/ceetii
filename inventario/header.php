<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario CEETII - El Alto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --rojo-elegante: #8d191d; 
            --gris-suave: #f1f3f5;
            --texto-oscuro: #2d3436;
        }

        body { 
            background-color: var(--gris-suave); 
            font-family: 'Inter', sans-serif;
            color: var(--texto-oscuro);
            margin: 0;
            padding: 0;
        }

        .navbar-ceetii { 
            background-color: var(--rojo-elegante) !important;
            padding: 8px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        .btn-exit-system {
            background-color: rgba(255,255,255,0.1);
            color: white !important;
            border-radius: 6px;
            padding: 5px 12px !important;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .btn-exit-system:hover {
            background-color: white;
            color: var(--rojo-elegante) !important;
        }

        .nav-link {
            transition: all 0.3s ease;
            margin: 0 3px;
            border-radius: 6px;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            text-decoration: none;
        }
        .btn-edit { color: #0d6efd; background: rgba(13, 110, 253, 0.1); }
        .btn-edit:hover { background: #0d6efd; color: white; }
        .btn-delete { color: #dc3545; background: rgba(220, 53, 69, 0.1); }
        .btn-delete:hover { background: #dc3545; color: white; }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-ceetii mb-4">
    <div class="container">
        <a class="navbar-brand btn-exit-system d-flex align-items-center" href="../index.php">
            <span class="material-icons me-1" style="font-size: 1.1rem;">grid_view</span>
            Sistema Central
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link active small d-flex align-items-center" href="activos.php">
                    <span class="material-icons me-1" style="font-size: 1.2rem;">inventory_2</span> 
                    Lista de Activos
                </a>
                
                <a class="nav-link small d-flex align-items-center text-white" href="prestamos.php">
                    <span class="material-icons me-1" style="font-size: 1.2rem;">calendar_month</span> 
                    Préstamos
                </a>

                <div class="ms-lg-3 ps-lg-3 border-start border-light border-opacity-25">
                    <a class="nav-link small text-warning d-flex align-items-center" href="papelera.php">
                        <span class="material-icons me-1" style="font-size: 1.2rem;">delete_outline</span> 
                        Papelera
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">