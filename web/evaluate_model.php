<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messages = json_decode($_POST['messages'], true);

    $command = escapeshellcmd('python3 evaluate_model.py ' . escapeshellarg(json_encode($messages)));
    $output = shell_exec($command);
    echo $output;
}
?>
