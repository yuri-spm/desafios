<?php
include_once "conect.php";


$action  = $_GET['action'];


date_default_timezone_set('America/Sao_Paulo');
$time = date("H:i:s");
$date = date('Y-m-d');

$user_id = 1;

$query = "SELECT * FROM register WHERE user_id = :user_id AND DATE(date) = :date LIMIT 1";
$result = $conn->prepare($query);
$result->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$result->bindParam(':date', $date);
$result->execute();

$row = $result->fetch(PDO::FETCH_ASSOC);

switch ($action) {
    case 'Registar Entrada':
        if (!$row) {
            $query = "INSERT INTO register (date, entry_time, user_id) VALUES (:date, :entry_time, :user_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':entry_time', $time);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt === false) {
                die("Erro ao executar " . implode(", ", $conn->errorInfo()));
            }
            $stmt->execute();
            echo "Entrada registrada com sucesso.";
        } else {
            echo "Já foi batida a entrada.";
        }
        break;

        case 'Registrar Pausa':
            if ($row && ($row['lunch_start'] == '' || $row['lunch_start'] == null)) {
                $query = "UPDATE register SET lunch_start = :lunch_start WHERE user_id = :user_id AND DATE(date) = :date";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':date', $date);
                $stmt->bindParam(':lunch_start', $time);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if ($stmt === false) {
                    die("Erro ao executar " . implode(", ", $conn->errorInfo()));
                }
                $stmt->execute();
                echo "Pausa para almoço registrada com sucesso.";
            } else {
                echo "A pausa para almoço já foi registrada.";
            }
            break;

        case 'Registrar Retorno':
            if($row && ($row['lunch_end'] == '' || $row['lunch_end'] == null)){
                $query = "UPDATE register SET lunch_end = :lunch_end WHERE user_id = :user_id AND DATE(date) = :date";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':lunch_end', $time);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':date', $date);
                
                if($stmt === false){
                    die("Erro ao executar " . implode(", ", $conn->errorInfo()));
                }

                $stmt->execute();
                echo "Retorno registrado com sucesso";
            }else{
                echo "O retorno do almoço já foi registrado.";
            }
            
            break;
        case 'Registrar Saída':
            if($row && ($row['exit_time'] == '' || $row['exit_time'] == null)){
                $query = "UPDATE register SET exit_time = :exit_time WHERE user_id = :user_id AND DATE(date) = :date";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':exit_time', $time);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':date', $date);
                
                if($stmt === false){
                    die("Erro ao executar " . implode(", ", $conn->errorInfo()));
                }

                $stmt->execute();
                echo "Sáida registrada com sucesso";
            }else{
                echo "Até amanhã bom descanso.";
            }
            break;


}

        
