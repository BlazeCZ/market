<?php

// Načítání souboru pro práci s uživatelskými daty (např. autentifikace, role, atd.)
require 'inc/user.php';

// Kontrola, zda je uživatel přihlášen. Pokud není, přesměruje na přihlašovací stránku s chybovou hláškou
if (empty($_SESSION['user_id'])) {
    // Uživatel není přihlášen, přesměrování na přihlašovací stránku
    header("Location: index.php?error=login");
    exit();
}

// Pokud je v URL parametr 'id', pokračuje se s mazáním položky
if (!empty($_GET['id'])) {
    // Příprava SQL dotazu pro získání položky (zboží) podle ID
    $query = $db->prepare('SELECT * FROM goods WHERE id=?');
    $query->execute(array($_GET['id']));
    $item = $query->fetch(PDO::FETCH_ASSOC); // Načtení položky jako asociativní pole

    // Kontrola, zda položka patří aktuálně přihlášenému uživateli
    if ($_SESSION['user_id'] == $item['user_id']) {
        // Pokud ano, provede se smazání položky
        $query = $db->prepare('DELETE FROM goods WHERE id=?');
        $query->execute(array($_GET['id']));
        // Po úspěšném smazání přesměruje zpět na hlavní stránku
        header("Location: index.php");
    } else {
        // Pokud položka nepatří aktuálně přihlášenému uživateli, přesměruje ho na hlavní stránku
        header("Location: index.php");
        exit();
    }
} else {
    // Tento blok se vykoná, pokud není parametr 'id' v URL

    // Kontrola, zda je v URL parametr 'categoryid' pro mazání kategorie
    if (!empty($_GET['categoryid'])) {
        // Příprava SQL dotazu pro získání kategorie podle jejího ID
        $categoryQuery = $db->prepare('SELECT * FROM categories WHERE category_id=? LIMIT 1;');
        $categoryQuery->execute([$_GET['categoryid']]);

        // Pokud je kategorie nalezena
        if ($category = $categoryQuery->fetch(PDO::FETCH_ASSOC)) {
            $categoryId = $category['category_id']; // Uložení ID kategorie
        } else {
            // Pokud kategorie neexistuje, script skončí s chybovou zprávou
            exit('Kategorie neexistuje.');
        }

        // Pokud má aktuálně přihlášený uživatel roli 'admin', umožní se smazání kategorie
        if ($currentUser['role'] == 'admin') {
            // Příprava a vykonání SQL dotazu pro smazání kategorie
            $stmt = $db->prepare("DELETE FROM categories WHERE category_id=?");
            $stmt->execute([$_GET['categoryid']]);
            // Po úspěšném smazání přesměruje na stránku pro správu kategorií s hláškou o smazání
            header('Location: categoryNew.php?phase=delete');
        } else {
            // Pokud uživatel nemá roli 'admin', přesměruje na hlavní stránku s chybovou hláškou
            header("Location: index.php?error=admin");
            exit();
        }
    }
}