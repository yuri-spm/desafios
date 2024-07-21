<?php

try {
    $conn = new PDO('mysql:host=localhost;dbname=time_clock', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "conexao com o banco realizada com sucesso.";
} catch (PDOException $e) {
    echo 'ERROR' . $e->getMessage();
}

