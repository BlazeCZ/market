<?php

// Načteme připojení k databázi a inicializujeme session
require 'inc/user.php';

// Načteme hlavičku stránky
include __DIR__ . '/inc/header.php';

// Získáme všechny kategorie z databáze seřazené podle názvu
$categories = $db->query('SELECT * FROM categories ORDER BY name;')->fetchAll(PDO::FETCH_ASSOC);

// Začneme vykreslování struktury stránky
echo '<div class="container-fluid">';
echo '<div class="row flex-nowrap">';

// Zobrazíme boční panel, pokud existují kategorie
if (!empty($categories)) {
    echo '<div class="border-primary border-5 col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">';
    echo '<div id="sidebar" class="collapse collapse-horizontal show ">';
    echo '<div id="sidebar-nav" class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-50">';
    echo '<ul class="nav nav-pills flex-column mb-sm-auto mb-0 text-center" id="menu">';

    // Vytvoříme položky v menu pro každou kategorii
    foreach ($categories as $category) {
        $selected = "";
        // Pokud je v URL parametr 'kategorie', zkontrolujeme, zda je kategorie vybraná
        if (!empty($_GET['kategorie'])){
            if ($category['category_id'] == $_GET['kategorie']) {
                $selected = "bg-secondary rounded-pill"; // Zvýrazníme vybranou kategorii
            }
        }

        // Vytvoříme položku menu pro každou kategorii
        echo '    <li class="nav-item '.$selected.'">
                    <a href="./?kategorie=' . htmlspecialchars($category['category_id']) . '" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">' . htmlspecialchars($category['name']) . '</span> </a>
                  </li>';
    }

    // Přidáme položku "Archiv" do menu
    echo '<li class="nav-item mt-10"><a href="archivedItems.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">Archiv</span></a></li>';

    // Pokud je uživatel přihlášen a má roli admina, přidáme odkaz na úpravu kategorií
    if (!empty($_SESSION['user_id'])) {
        if ($currentUser['role'] == 'admin') {
            echo '<li class="nav-item mt-10"><a href="categoryNew.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline btn-primary btn">Úprava kategorii</span></a></li>';
        }
    }

    // Ukončíme boční panel
    echo '</ul></div></div></div>';
}

// Zpracujeme filtrování a vyhledávání položek
if (!empty($_GET)) {
    // Pokud je zadán parametr 'search' a není zadána kategorie
    if (!empty($_GET['search']) && empty($_GET['kategorie'])) {
        $keywords = $_GET['search'];
        // Vyhledáváme zboží podle názvu nebo popisu
        $query = $db->prepare("SELECT * FROM goods WHERE (description LIKE '%$keywords%' OR name LIKE '%$keywords%');");
        $query->execute();
        $goods = $query;
    } else {
        // Pokud je zadán parametr 'search' i 'kategorie'
        if (!empty($_GET['search']) && !empty($_GET['kategorie'])) {
            $keywords = $_GET['search'];
            $query = $db->prepare("SELECT * FROM goods WHERE (description LIKE '%$keywords%' OR name LIKE '%$keywords%') AND (category_id=:category) ORDER BY id DESC;");
            $query->execute([ ':category' => $_GET['kategorie'] ]);
            $goods = $query;
        } else {
            // Pokud je zadána pouze kategorie
            if (empty($_GET['search']) && !empty($_GET['kategorie'])) {
                $query = $db->prepare('SELECT * FROM goods WHERE category_id=:category ORDER BY id DESC;');
                $query->execute([ ':category' => $_GET['kategorie'] ]);
                $goods = $query;
            }
        }
    }

} else {
    // Pokud nejsou žádné filtry, zobrazíme všechny položky
    $query = $db->prepare('SELECT * FROM goods ORDER BY id DESC;');
    $query->execute();
    $goods = $query;
}

// Začneme vykreslování seznamu položek
echo '<div class="col ps-md-3 pt-3">';
if (!empty($_SESSION['user_id'])) {
    // Pokud je uživatel přihlášen, přidáme tlačítko pro přidání nové položky
    echo '<div class="row my-3">
            <a href="new.php?category=' . @$_GET['kategorie'] . '" class="btn btn-primary">Přidat položku</a>
          </div>';
}

// Nadpis pro seznam položek
echo '<h2 class="">Položky na prodej</h2>';
echo '<div class="row">';

// Pokud máme položky, začneme je vykreslovat
if (!empty($goods)) {

    foreach ($goods as $item) {
        // Defaultní název souboru pro obrázek
        $filename = "inc/uploaded_files/image.jpg";
        $id = $item['id'];
        // Získáme soubory s odpovídajícím ID položky
        $files = glob("inc/uploaded_files/$id.*");
        foreach ($files as $file) {
            $filename = $file; // Pokud existuje obrázek, použijeme ho
        }

        // Vytvoříme odkaz na stránku s podrobnostmi o položce
        echo '<a class="col-sm-4 text-dark" href="item.php?id=' . $item['id'] . '">';

        // Vykreslíme kartu s informacemi o položce
        echo '<div class="card">';
        echo ' <img src="'.$filename.'" alt="photo" class="card-img-top img-thumbnail">';
        echo '<div class="card-body">
                    <h3 class="card-title">' . htmlspecialchars($item['name']) . '</h3>
                    <p class="card-text">' . htmlspecialchars($item['description']) . '</p>
                    <p class="card-text">Cena: ' . htmlspecialchars($item['price']) . '</p>
              </div>';
        echo '</div></a>';
    }
}

// Ukončíme řádky a sloupce pro zobrazení položek
echo '</div>'; //row
echo '</div>'; //col ps-md-3 pt-3

// Ukončíme hlavní obsah a boční panel
echo '</div></div>'; //container, row flex

// Načteme patičku stránky
include __DIR__ . '/inc/footer.php';
