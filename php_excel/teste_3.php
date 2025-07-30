<?php


require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;


$input_file = 'teste_2.xlsx';

$load_file = IOFactory::load($input_file);

$sheet = $load_file->getActiveSheet();

foreach ($sheet->getRowIterator() as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);

    foreach ($cellIterator as $cell) {
        echo $cell->getValue() . "\t";
    }
    echo PHP_EOL;
}

