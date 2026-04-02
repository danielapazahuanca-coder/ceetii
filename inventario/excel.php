<?php
error_reporting(0);
// zona horaria
date_default_timezone_set('America/La_Paz');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$busqueda = $_GET['buscar'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';

$ver_resp = isset($_GET['col_resp']) || !isset($_GET['filtrar']);
$ver_precio = isset($_GET['col_precio']) || !isset($_GET['filtrar']);
$ver_fecha = isset($_GET['col_fecha']) || !isset($_GET['filtrar']);
$ver_obs = isset($_GET['col_obs']) || !isset($_GET['filtrar']);

$params = http_build_query(['buscar' => $busqueda, 'ubicacion' => $ubicacion]);
$url = "http://localhost/api_ceti/public/index.php/activos?" . $params;
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$activos = $resultado['data'] ?? [];

// CREAR EL EXCEL
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventario CEETII');

// --- ESTILOS ---
$estiloHeader = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8D191D']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// titulos
$sheet->setCellValue('A1', 'INSTITUTO TECNOLÓGICO CEETII EL ALTO');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'REPORTE DE INVENTARIO DE ACTIVOS - ' . date('d/m/Y H:i'));
$sheet->mergeCells('A2:H2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// encabezados de tabla
$encabezados = [
    'A4' => 'NOMBRE DEL ACTIVO',
    'B4' => 'CÓDIGO',
    'C4' => 'UBICACIÓN',
    'D4' => 'ESTADO',
    'E4' => 'RESPONSABLE',
    'F4' => 'PRECIO (Bs)',
    'G4' => 'FECHA REGISTRO',
    'H4' => 'OBSERVACIONES'
];

foreach ($encabezados as $celda => $texto) {
    $sheet->setCellValue($celda, $texto);
}

// estilo fila4
$sheet->getStyle('A4:H4')->applyFromArray($estiloHeader);

$fila = 5;
$estados = [1 => "Bueno", 2 => "Regular", 3 => "Malo", 4 => "Desecho"];

foreach ($activos as $a) {
    $sheet->setCellValue('A' . $fila, $a['nombre']);
    $sheet->setCellValue('B' . $fila, $a['codigo_activo']);
    $sheet->setCellValue('C' . $fila, $a['ubicacion']);
    $sheet->setCellValue('D' . $fila, $estados[$a['estado_id']] ?? 'N/A');
    $sheet->setCellValue('E' . $fila, $a['responsable'] ?? '');
    
    // Insertar precio como num
    $sheet->setCellValue('F' . $fila, $a['precio_compra']);
    $sheet->getStyle('F' . $fila)->getNumberFormat()->setFormatCode('#,##0.00 "Bs."');
    
    $sheet->setCellValue('G' . $fila, $a['fecha_registro']);
    $sheet->setCellValue('H' . $fila, $a['observaciones'] ?? '');
    
    $sheet->getStyle('A'.$fila.':H'.$fila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $fila++;
}

// Ocultar columnas seggun filtro
if (!$ver_resp)  $sheet->getColumnDimension('E')->setVisible(false);
if (!$ver_precio) $sheet->getColumnDimension('F')->setVisible(false);
if (!$ver_fecha)  $sheet->getColumnDimension('G')->setVisible(false);
if (!$ver_obs)    $sheet->getColumnDimension('H')->setVisible(false);

// Autoajustar ancho de columnas
foreach (range('A','H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

if (ob_get_contents()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Inventario_CEETII_'.date('dmY').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;