<?php
include_once "conect.php";

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

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

$response = ['status' => 'error', 'message' => ''];

switch ($action) {
    case 'Registar Entrada':
        if (!$row) {
            $query = "INSERT INTO register (date, entry_time, user_id) VALUES (:date, :entry_time, :user_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':entry_time', $time);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Entrada registrada com sucesso.'];
            } else {
                $response['message'] = 'Erro ao registrar entrada.';
            }
        } else {
            $response['message'] = 'Já foi batida a entrada.';
        }
        break;

    case 'Registrar Pausa':
        if ($row && ($row['lunch_start'] == '' || $row['lunch_start'] == null)) {
            $query = "UPDATE register SET lunch_start = :lunch_start WHERE user_id = :user_id AND DATE(date) = :date";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':lunch_start', $time);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Pausa para almoço registrada com sucesso.'];
            } else {
                $response['message'] = 'Erro ao registrar pausa.';
            }
        } else {
            $response['message'] = 'A pausa para almoço já foi registrada.';
        }
        break;

    case 'Registrar Retorno':
        if ($row && ($row['lunch_end'] == '' || $row['lunch_end'] == null)) {
            $query = "UPDATE register SET lunch_end = :lunch_end WHERE user_id = :user_id AND DATE(date) = :date";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':lunch_end', $time);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Retorno registrado com sucesso.'];
            } else {
                $response['message'] = 'Erro ao registrar retorno.';
            }
        } else {
            $response['message'] = 'O retorno do almoço já foi registrado.';
        }
        break;

    case 'Registrar Saída':
        if ($row && ($row['exit_time'] == '' || $row['exit_time'] == null)) {
            $query = "UPDATE register SET exit_time = :exit_time WHERE user_id = :user_id AND DATE(date) = :date";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':exit_time', $time);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Saída registrada com sucesso.'];
            } else {
                $response['message'] = 'Erro ao registrar saída.';
            }
        } else {
            $response['message'] = 'Até amanhã, bom descanso.';
        }
        break;
}

echo json_encode($response);
