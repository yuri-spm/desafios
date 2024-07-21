<?php


function entry_time($conn, $dateNow, $user_id) {
    if (!validateDateUser($conn, $dateNow, $user_id)) {
        $query = "INSERT INTO register(date, entry_time, user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die("Erro ao executar " . implode(", ", $conn->errorInfo()));
        }
        $stmt->execute([$dateNow, date('H:i:s'), $user_id]);
        echo "Entrada registrada com sucesso.";
    } else {
        echo "Já foi batida a entrada.";
    }
}

function validateDateUser($conn, $dateNow, $user_id) {
    $query = "SELECT * FROM register WHERE user_id = :user_id AND DATE(date) = DATE(:dateNow) LIMIT 1";
    $result = $conn->prepare($query);
    $result->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $result->bindParam(':dateNow', $dateNow);
    $result->execute();

    if ($result && $result->rowCount() != 0) {
        return true;
    }
    return false;
}