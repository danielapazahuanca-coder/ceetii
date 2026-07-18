<?php
// functions.php

function api_get(string $url): ?array {
    return json_decode(@file_get_contents($url), true);
}

function api_post(string $url, array $payload): ?array {
    $opts = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'POST',
             'content' => json_encode($payload), 'ignore_errors' => true]];
    return json_decode(@file_get_contents($url, false, stream_context_create($opts)), true);
}

function api_put(string $url, array $payload): ?array {
    $opts = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'PUT',
             'content' => json_encode($payload), 'ignore_errors' => true]];
    return json_decode(@file_get_contents($url, false, stream_context_create($opts)), true);
}

function api_delete(string $url): ?array {
    $opts = ['http' => ['method' => 'DELETE', 'ignore_errors' => true]];
    return json_decode(@file_get_contents($url, false, stream_context_create($opts)), true);
}


function ok(string $txt): string {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert"><span class="material-icons align-middle me-2">check_circle</span>' . $txt . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

function err(string $txt): string {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert"><span class="material-icons align-middle me-2">error</span>' . $txt . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}


function rol_badge(int $r): string {
    return match($r) {
        1 => '<span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 fw-semibold rounded">Administrador</span>',
        2 => '<span class="badge bg-primary-subtle text-primary px-2.5 py-1.5 fw-semibold rounded">Secretaria</span>',
        3 => '<span class="badge bg-warning-subtle text-warning-emphasis px-2.5 py-1.5 fw-semibold rounded">Docente</span>',
        default => '<span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 fw-semibold rounded">Sin rol</span>'
    };
}