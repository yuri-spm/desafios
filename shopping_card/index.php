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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Carrinho</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }
        h2, h3 {
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 0 5px #ccc;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #007bff;
            color: #fff;
        }
        form {
            display: inline;
        }
        input[type=number] {
            width: 60px;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .add-form {
            margin-top: 20px;
            background: #fff;
            padding: 15px;
            box-shadow: 0 0 5px #ccc;
        }
    </style>
</head>
<body>

<h2>Carrinho de Compras</h2>

<?php if (empty($_SESSION["carrinho"])): ?>
    <p>Carrinho vazio.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Produto</th>
            <th>Preço</th>
            <th>Quantidade</th>
            <th>Subtotal</th>
            <th>Ações</th>
        </tr>
        <?php
        $totalGeral = 0;
        foreach ($_SESSION["carrinho"] as $index => $item):
            $subtotal = $item["preco"] * $item["quantidade"];
            $totalGeral += $subtotal;
        ?>
        <tr>
            <td><?= htmlspecialchars($item["nome"]) ?></td>
            <td>R$ <?= number_format($item["preco"], 2, ',', '.') ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="acao" value="atualizar">
                    <input type="hidden" name="indice" value="<?= $index ?>">
                    <input type="number" name="nova_quantidade" value="<?= $item['quantidade'] ?>" min="1">
                    <button type="submit">Atualizar</button>
                </form>
            </td>
            <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
            <td><a href="?remover=<?= $index ?>">Remover</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="total">Total: R$ <?= number_format($totalGeral, 2, ',', '.') ?></div>
<?php endif; ?>

<div class="add-form">
    <h3>Adicionar novo produto</h3>
    <form method="POST">
        <input type="hidden" name="acao" value="adicionar">

        <label>Nome:</label>
        <input type="text" name="nome" required><br><br>

        <label>Preço:</label>
        <input type="number" step="0.01" name="preco" required><br><br>

        <label>Quantidade:</label>
        <input type="number" name="quantidade" min="1" required><br><br>

        <button type="submit">Adicionar ao carrinho</button>
    </form>
</div>

</body>
</html>