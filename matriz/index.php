<?php

function addMatrices($matrixA, $matrixB) {
    $result = [];
    for ($i = 0; $i < count($matrixA); $i++) {
        for ($j = 0; $j < count($matrixA[0]); $j++) {
            $result[$i][$j] = $matrixA[$i][$j] + $matrixB[$i][$j];
        }
    }
    return $result;
}

// Exemplo de uso
$matrixA = [
    [1, 2],
    [3, 4]
];

$matrixB = [
    [5, 6],
    [7, 8]
];

$result = addMatrices($matrixA, $matrixB);
echo "<pre>";gii
var_dump($result);
echo "</pre>";
?>