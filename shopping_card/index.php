<?php

session_start();
// session_destroy(); // ⚠️ apaga tudo da sessão (só use agora)
// echo "Sessão destruída. Atualize a página para continuar.";
// exit;

if(!isset($_SESSION["carrinho"]) || !is_array($_SESSION["carrinho"])){
    $_SESSION["carrinho"] = [];
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $produto = [
        "nome"       => $_POST["nome"],
        "preco"      => (float) $_POST["preco"],
        "quantidade" => (int) $_POST["quantidade"]
    ];

    $_SESSION['carrinho'][] = $produto;

  
}
echo "<h2>Carrinho:</h2>";
$totalGeral = 0;

    foreach($_SESSION['carrinho'] as $item){
        $total = $item['preco'] * $item["quantidade"];
        $totalGeral += $total;

        echo "Produto: {$item['nome']}<br>";
        echo "Preço: {$item['preco']}<br>";
        echo "Quantidade: {$item['quantidade']}<br>";
        echo "Subtotal: R$ $total<br><br>";
    }

echo "<strong>Total geral: R$ $totalGeral</strong><br><br>";




?>
<!-- Formulário HTML -->
<form method="POST">
    <label>Nome do produto:</label>
    <input type="text" name="nome" required><br><br>

    <label>Preço:</label>
    <input type="number" step="0.01" name="preco" required><br><br>

    <label>Quantidade:</label>
    <input type="number" name="quantidade" required><br><br>

    <button type="submit">Adicionar ao carrinho</button>
</form>