<?php
require_once 'db.php';

session_start(); //spustíme session

require_once __DIR__.'/../vendor/autoload.php';

if (!empty($_SESSION['user_id'])){
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION["user_id"]]);

    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser){
        session_destroy();
        header('Location: index.php');
        exit();
    }
}
