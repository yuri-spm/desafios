<?php
session_start();

// session_destroy();
// echo "Sessão destruída. Atualize a página para continuar.";
// exit;

$fileJson = "carrinho.json";

// init card
if (!isset($_SESSION["carrinho"])) {
    if (file_exists($fileJson)) {
        $json = file_get_contents($fileJson);
        $_SESSION["carrinho"] = json_decode($json, true) ?? [];
    } else {
        $_SESSION["carrinho"] = [];
    }
}

function saveCard($data, $file) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// add item
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "adicionar") {
    $produto = [
        "nome"       => $_POST["nome"],
        "preco"      => (float) $_POST["preco"],
        "quantidade" => (int) $_POST["quantidade"]
    ];
    $_SESSION["carrinho"][] = $produto;

    saveCard($_SESSION["carrinho"], $fileJson);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// update qtd
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "atualizar") {
    $indice = (int) $_POST["indice"];
    $novaQtd = (int) $_POST["nova_quantidade"];

    if (isset($_SESSION["carrinho"][$indice]) && $novaQtd > 0) {
        $_SESSION["carrinho"][$indice]["quantidade"] = $novaQtd;

        saveCard($_SESSION["carrinho"], $fileJson);
    }
}

// remove item
if (isset($_GET["remover"])) {
    $indice = (int) $_GET["remover"];
    if (isset($_SESSION["carrinho"][$indice])) {
        unset($_SESSION["carrinho"][$indice]);
        $_SESSION["carrinho"] = array_values($_SESSION["carrinho"]);

        saveCard($_SESSION["carrinho"], $fileJson);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// show card
echo "<h2>Carrinho:</h2>";
$totalGeral = 0;

if (count($_SESSION["carrinho"]) === 0) {
    echo "Carrinho vazio.<br><br>";
} else {
    foreach ($_SESSION["carrinho"] as $index => $item) {
        $subtotal = $item["preco"] * $item["quantidade"];
        $totalGeral += $subtotal;

        echo "<strong>Produto:</strong> {$item['nome']}<br>";
        echo "Preço: R$ {$item['preco']}<br>";
        echo "Quantidade: {$item['quantidade']}<br>";
        echo "Subtotal: R$ $subtotal<br>";

        echo "<form method='POST' style='display:inline;'>
                <input type='hidden' name='indice' value='$index'>
                <input type='number' name='nova_quantidade' value='{$item['quantidade']}' min='1' style='width:60px;'>
                <input type='hidden' name='acao' value='atualizar'>
                <button type='submit'>Atualizar</button>
              </form> ";

        echo "<a href='?remover=$index'>Remover</a><br><hr>";
    }

    echo "<strong>Total geral: R$ $totalGeral</strong><br><br>";
}
?>

<!-- Forms -->
<h3>Adicionar novo produto:</h3>
<form method="POST">
    <input type="hidden" name="acao" value="adicionar">

    <label>Nome do produto:</label>
    <input type="text" name="nome" required><br><br>

    <label>Preço:</label>
    <input type="number" step="0.01" name="preco" required><br><br>

    <label>Quantidade:</label>
    <input type="number" name="quantidade" min="1" required><br><br>

    <button type="submit">Adicionar ao carrinho</button>
</form>