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
$ver_fecha_c = isset($_GET['col_fecha_c']) || !isset($_GET['filtrar']); // NUEVA
$ver_fecha = isset($_GET['col_fecha']) || !isset($_GET['filtrar']);
$ver_obs = isset($_GET['col_obs']) || !isset($_GET['filtrar']);

$params = http_build_query(['buscar' => $busqueda, 'ubicacion' => $ubicacion]);
$url = "http://localhost/api_ceti/public/index.php/activos?" . $params;
$response = @file_get_contents($url);
$resultado = json_decode($response, true);
$activos = $resultado['data'] ?? [];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventario CEETII');

$estiloHeader = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8D191D']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$sheet->setCellValue('A1', 'INSTITUTO TECNOLÓGICO CEETII EL ALTO');
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'REPORTE DE INVENTARIO DE ACTIVOS - ' . date('d/m/Y H:i'));
$sheet->mergeCells('A2:I2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$encabezados = [
    'A4' => 'NOMBRE DEL ACTIVO',
    'B4' => 'CÓDIGO',
    'C4' => 'UBICACIÓN',
    'D4' => 'ESTADO',
    'E4' => 'RESPONSABLE',
    'F4' => 'PRECIO (Bs)',
    'G4' => 'FECHA COMPRA', // NUEVA
    'H4' => 'FECHA REGISTRO',
    'I4' => 'OBSERVACIONES'
];

foreach ($encabezados as $celda => $texto) {
    $sheet->setCellValue($celda, $texto);
}

$sheet->getStyle('A4:I4')->applyFromArray($estiloHeader);

$fila = 5;
$estados = [1 => "Bueno", 2 => "Regular", 3 => "Malo", 4 => "Desecho"];

foreach ($activos as $a) {
    $sheet->setCellValue('A' . $fila, $a['nombre']);
    $sheet->setCellValue('B' . $fila, $a['codigo_activo']);
    $sheet->setCellValue('C' . $fila, $a['ubicacion']);
    $sheet->setCellValue('D' . $fila, $estados[$a['estado_id']] ?? 'N/A');
    $sheet->setCellValue('E' . $fila, $a['responsable'] ?? '');
    $sheet->setCellValue('F' . $fila, $a['precio_compra']);
    $sheet->getStyle('F' . $fila)->getNumberFormat()->setFormatCode('#,##0.00 "Bs."');
    $sheet->setCellValue('G' . $fila, $a['fecha_compra'] ?? 'N/A'); // NUEVA
    $sheet->setCellValue('H' . $fila, $a['fecha_registro']);
    $sheet->setCellValue('I' . $fila, $a['observaciones'] ?? '');
    
    $sheet->getStyle('A'.$fila.':I'.$fila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $fila++;
}

if (!$ver_resp)  $sheet->getColumnDimension('E')->setVisible(false);
if (!$ver_precio) $sheet->getColumnDimension('F')->setVisible(false);
if (!$ver_fecha_c) $sheet->getColumnDimension('G')->setVisible(false); // NUEVA
if (!$ver_fecha)  $sheet->getColumnDimension('H')->setVisible(false);
if (!$ver_obs)    $sheet->getColumnDimension('I')->setVisible(false);

foreach (range('A','I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

if (ob_get_contents()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Inventario_CEETII_'.date('dmY').'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;