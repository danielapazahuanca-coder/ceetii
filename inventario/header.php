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

        /* NAVBAR */
        .navbar-ceetii { 
            background-color: var(--rojo-elegante) !important;
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 500;
            letter-spacing: 1px;
            font-size: 1rem;
            text-transform: uppercase;
        }

        /* Tarjetas y Tablas */
        .card-custom, .table-container, .card {
            background-color: #ffffff !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04) !important;
        }

        .table thead th {
            background-color: #343a40 !important;
            color: #ffffff !important;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 15px;
            border: none;
        }

        .btn-ceetii {
            background-color: var(--rojo-elegante);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .pagination .page-link {
            color: var(--rojo-elegante) !important;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--rojo-elegante) !important;
            border-color: var(--rojo-elegante) !important;
            color: white !important;
        }

        .pagination .page-link:hover {
            background-color: #f8d7da !important;
            color: var(--rojo-elegante) !important;
            border-color: var(--rojo-elegante);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-ceetii mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <span class="material-icons me-2" style="font-size: 1.2rem;">arrow_back</span>
            VOLVER AL SISTEMA
        </a>
        
        <div class="navbar-nav ms-auto">
            <a class="nav-link active small" href="activos.php">Inicio</a>
            <a class="nav-link small text-warning" href="papelera.php">
                <span class="material-icons align-middle" style="font-size: 1rem;">delete_outline</span> Papelera
            </a>
        </div>
    </div>
</nav>

<div class="container pb-5">