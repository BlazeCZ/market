<?php

// Načítání souboru, který obsahuje definici funkcí nebo tříd pro práci s uživatelskými daty
require_once 'inc/user.php';

// Kontrola, zda je uživatel přihlášen. Pokud není, přesměruje na přihlašovací stránku s chybovou hláškou
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?error=login");
    exit();
}

// Pokud přihlášený uživatel nemá roli 'admin', přesměruje ho na hlavní stránku s chybovou hláškou
if ($currentUser['role'] != 'admin') {
    header("Location: index.php?error=admin");
    exit();
}

// Načítání hlavičky stránky
include 'inc/header.php';

// Definice pole pro zobrazení různých zpráv podle fáze operace (nová kategorie, úprava, smazání)
$phase = array (
    "new" => "Kategorie byla vytvořena.",
    "exist" => "Kategorie byla upravena.",
    "delete" => "Kategorie byla smazána."
);

// Pokud je v URL parametr 'phase', zobrazí příslušnou zprávu
if (!empty($_GET['phase'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">  
            <button type="button" class="close" data-dismiss="alert">&times;</button>' . 
            $phase[$_GET['phase']] . 
          '</div>';
}

// Příprava SQL dotazu pro získání všech kategorií z databáze
$categoryQuery = $db->prepare('SELECT * FROM categories;');
$categoryQuery->execute();  // Spuštění dotazu
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);  // Načtení výsledků jako asociativní pole

// Pokud jsou kategorie nalezeny, zobrazí se v tabulce
if (!empty($categories)) {
    echo '<table class="table table-striped w-auto">';
    foreach ($categories as $category) {
        echo '     <tr>
                     <td class="lead font-weight-bold">' . htmlspecialchars($category['name']) . '</td>
                     <td><a href="edit_category.php?id=' . $category['category_id'] . '" class="btn btn-warning">Upravit</a></td>
                     <td><a href="delete.php?categoryid=' . $category['category_id'] . ' " class="btn btn-danger">Smazat</a></td>
                   </tr>';
    };
    echo '</table>';
}

// Zobrazení tlačítek pro přidání nové kategorie nebo zrušení
echo '<div class="btn-group">
            <a href="edit_category.php" class="btn btn-primary">Přidat kategorii</a>
          ';
echo '<a href="index.php" class="btn btn-light">Zrušit</a></div>';

// Načítání patičky stránky
include 'inc/footer.php';