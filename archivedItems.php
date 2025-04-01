<?php

// Načtení uživatelských funkcí a relace
require 'inc/user.php';

// Vložení hlavičky stránky
include __DIR__ . '/inc/header.php';

// Načtení kategorií z databáze a seřazení podle názvu
$categories = $db->query('SELECT * FROM categories ORDER BY name;')->fetchAll(PDO::FETCH_ASSOC);

// Načtení archivovaných položek, seřazení podle ID sestupně
$query = $db->prepare('SELECT * FROM archive ORDER BY id DESC;');
$query->execute();
$goods = $query;

// Začátek HTML struktury
echo '<div class="container-fluid">';
echo '<div class="row flex-nowrap">';

// Pokud existují kategorie, vytvoříme postranní panel
if (!empty($categories)) {
    echo '<div class="border-primary border-5 col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">';
    echo '<div id="sidebar" class="collapse collapse-horizontal show ">';
    echo '<div id="sidebar-nav" class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-50">';
    echo '<ul class="nav nav-pills flex-column mb-sm-auto mb-0 text-center" id="menu">';

    // Výpis kategorií
    foreach ($categories as $category) {
        echo '    <li class="nav-item">
                    <a href="./?kategorie=' . htmlspecialchars($category['category_id']) . '" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">' . htmlspecialchars($category['name']) . '</span> </a>
                  </li>';
    }
    echo '<li class="nav-item bg-secondary rounded-pill"><a href="archivedItems.php" class="nav-link align-middle px-0 text-light " data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline ">Archiv</span></a></li>';
    if (!empty($_SESSION['user_id'])) {
        if ($currentUser['role'] == 'admin') {
            echo '<li class="nav-item mt-10"><a href="categoryNew.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline btn-primary btn">Úprava kategorii</span></a></li>';
        }
    }
    echo '</ul></div></div></div>';
}

// Hlavní obsah stránky
echo '<div class="col ps-md-3 pt-3">';

echo '<h2 class="">Archiv - Prodané položky</h2>';
echo '<div class="row">';

// Kontrola, zda existují archivované položky
if (!empty($goods)) {
    foreach ($goods as $item) {
        $filename = "inc/uploaded_files/image.jpg";
        $id = $item['id'];
        $files = glob("inc/uploaded_files/$id.*");
        foreach ($files as $file){
            $filename = $file;
        }
        echo '<a class="col-sm-4 text-dark" href="item.php?archiveid=' . $item['id'] . '">';

        // Vytvoření karty položky
        echo '<div class="card" <!--style="width: 18rem;"-->';
        echo ' <img src="'.$filename.'" alt="photo" class="card-img-top img-thumbnail">';
        echo '<div class="card-body">
                    <h3 class="card-title">' . htmlspecialchars($item['name']) . '</h3>
                    <p class="card-text">' . htmlspecialchars($item['description']) . '</p>
                    <p class="card-text">Cena: ' . htmlspecialchars($item['price']) . '</p>
              </div>';
        echo '</div></a>';
    }
}

echo '</div>'; // Uzavření řádku s kartami

echo '</div>'; // Uzavření hlavního obsahu

echo '</div></div>'; // Uzavření kontejneru a řádku

// Vložení patičky stránky
include __DIR__ . '/inc/footer.php';

?>