<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sort_names = isset($_POST['sort']) ? $_POST['sort'] : '';

    $names = explode(',', $sort_names);

    $rand = array_rand($names, 1);

    $name = htmlspecialchars(trim($names[$rand]), ENT_QUOTES, 'UTF-8');
    
    echo $name;
}else{
    echo "Por favor insira, nomes validos";
}
?>
