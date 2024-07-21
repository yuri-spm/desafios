<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Relogio de Ponto</title>
</head>

<body>
    <div class="clock">
        <div>
            <span id="hour">00</span>
            <span class="time">Horas</span>
        </div>
        <div>
            <span id="minutes">00</span>
            <span class="time">minutos</span>
        </div>
        <div>
            <span id="seconds">00</span>
            <span class="time">Segundos</span>
        </div>
    </div>

    <div class="register">
        <button class="button" data-action="register_entry">
                <span class="msg">
                    Registrar Entrada
                </span>
        </button>
    </div>
    <div id="response"></div>
    <script src="js/script.js"></script>
</body>

</html>