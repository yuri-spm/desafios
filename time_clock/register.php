<?php
include_once "conect.php";
include_once "helpers.php";

$action  =  $_GET['action'];

date_default_timezone_set('America/Sao_Paulo');
$dateNow =  date("H:i:s");
$year = date('Y-m-d');

$id = 1;

switch ($action) {
    case 'Registar Entrada':
        entry_time($conn, $year, $dateNow, $id);
        break;

    case 'Registrar Pausa':
        echo "Bem Almoço, pausa registada as {$dateNow}";


        break;
    case 'Registrar Retorno':
        echo "Vamos Produzir, retorno as {$dateNow}";
        break;
    case 'Registrar Saída':
        echo "Até amanhã, saída registrada as {$dateNow}";
        break;
}
