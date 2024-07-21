<?php


function entry_time($conn, $dateNow, $timeNow, $user_id) {
    if (!validateDateUser($conn, $dateNow, $user_id)) {
        $query = "INSERT INTO register(date, entry_time, user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die("Erro ao executar " . implode(", ", $conn->errorInfo()));
        }
        $stmt->execute([$dateNow, $timeNow, $user_id]);
        echo "Bem vindo, entrada registrada com sucesso.";
    } else {
       echo "Olá verificamos que sua entrada já foi batida.";
    }
}

function validateDateUser($conn, $dateNow, $user_id) {
    $query = "SELECT * FROM register WHERE user_id = :user_id AND DATE(date) = :dateNow LIMIT 1";
    $result = $conn->prepare($query);
    $result->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $result->bindParam(':dateNow', $dateNow);
    $result->execute();

    if ($result && $result->rowCount() != 0) {
        return true;
    }
    return false;
}