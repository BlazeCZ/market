<?php

// Zahrnutí souboru pro práci s uživatelskými daty (např. pro připojení k databázi, ověřování, atd.)
require_once 'inc/user.php';

// Ověření, zda uživatel již je přihlášen
if (!empty($_SESSION['user_id'])){
    // Pokud je uživatel přihlášen, ukončíme jeho session (odhlášení)
    unset($_SESSION['user_id']);  // Odstraní ID uživatele z session
    unset($_SESSION['user_name']);  // Odstraní jméno uživatele z session
}

// Po úspěšném odhlášení přesměrujeme uživatele na domovskou stránku
header('Location: index.php');
exit();  // Zajistí, že skript bude okamžitě ukončen po přesměrování

?>