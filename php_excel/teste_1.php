<?php


require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

// $activeWorksheet->setCellValue('A1', 'Hello World !');

// $writer = new Xlsx($spreadsheet);
// $writer->save('hello world.xlsx');


$title = 'Nome';
$nomes = ['João', 'Maria', 'Carlos', 'Ana', 'Joana'];

$activeWorksheet->setCellValue('A1', $title);


$linha = 2;

for ($i = 0; $i < count($nomes); $i++) {
    $activeWorksheet->setCellValue('A' . $linha, $nomes[$i]);
    $linha++;
    
}

$writer = new Xlsx($spreadsheet);
$writer->save('teste_1.xlsx');