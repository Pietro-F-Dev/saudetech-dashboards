<?php
// Atalho para quem acessa a raiz do projeto sem digitar "/public/".
// Redireciona (em vez de "require") para manter o cálculo de BASE_URL correto.
$target = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/public/';
header('Location: ' . $target);
exit;
