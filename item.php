<?php

// Načteme připojení k databázi a inicializujeme session
require 'inc/user.php';

// Pokud je odesláno tlačítko pro projev zájmu o produkt
if (isset($_POST['interest'])) {
    // Aktualizujeme stav zájmu o produkt v databázi
    $queryItem = $db->prepare('UPDATE goods SET interested_id=:interested WHERE id=:id LIMIT 1;');
    $queryItem->execute([
        ':interested' => $_SESSION['user_id'], // Uživatel projevil zájem
        ':id' => $_GET['id'] // ID produktu
    ]);
}

// Načteme hlavičku stránky
include __DIR__ . '/inc/header.php';

// Načteme všechny kategorie z databáze
$categories = $db->query('SELECT * FROM categories ORDER BY name;')->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="container-fluid">';
echo '<div class="row flex-nowrap">';

// Pokud existují kategorie, vykreslíme je v bočním panelu
if (!empty($categories)) {
    echo '<div class="border-primary border-5 col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">';
    echo '<div id="sidebar" class="collapse collapse-horizontal show ">';
    echo '<div id="sidebar-nav" class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-50">';
    echo '<ul class="nav nav-pills flex-column mb-sm-auto mb-0 text-center" id="menu">';
    // Pro každou kategorii vytvoříme položku v menu
    foreach ($categories as $category) {
        echo '    <li class="nav-item">
                    <a href="./?kategorie=' . htmlspecialchars($category['category_id']) . '" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">' . htmlspecialchars($category['name']) . '</span> </a>
                  </li>';
    }
    // Přidáme položku pro archiv
    echo '<li class="nav-item mt-10"><a href="archivedItems.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">Archiv</span></a></li>';
    // Pokud je uživatel přihlášen a má roli admin, přidáme odkaz na úpravu kategorií
    if (!empty($_SESSION['user_id'])) {
        if ($currentUser['role'] == 'admin') {
            echo '<li class="nav-item mt-10"><a href="categoryNew.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline btn-primary btn">Úprava kategorií</span></a></li>';
        }
    }
    echo '</ul></div></div></div>';
}

// Získáme informace o produktu podle ID
if (isset ($_GET['id'])) {
    $queryItem = $db->prepare('SELECT * FROM goods WHERE id=:id LIMIT 1;');
    $queryItem->execute([
        ':id' => $_GET['id']
    ]);
    $item = $queryItem->fetch(PDO::FETCH_ASSOC);
    $item['archived'] = '0'; // Ujistíme se, že položka není archivována
} else {
    // Pokud je požadováno zobrazení archivovaného produktu
    if (isset ($_GET['archiveid'])) {
        $queryItem = $db->prepare('SELECT * FROM archive WHERE id=:id LIMIT 1;');
        $queryItem->execute([
            ':id'=> $_GET['archiveid']
        ]);
        $item = $queryItem->fetch(PDO::FETCH_ASSOC);
        $item['interested_id'] = ''; // U resetujeme 'interested_id' pro archivované položky
        $item['archived'] = '1'; // Označíme položku jako archivovanou
    } else {
        header('Location: index.php'); // Pokud není platný parametr, přesměrujeme na hlavní stránku
        exit();
    }
}

// Pokud je položka načtena, pokračujeme v zobrazení
if (!empty($item)) {

    // Načteme informace o uživateli, který produkt přidal
    $queryUser = $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1;');
    $queryUser->execute([
        ':id' => $item['user_id']
    ]);
    $user = $queryUser->fetch(PDO::FETCH_ASSOC);
    echo '<div class="col ps-md-3 pt-3">';

    // Pokud je uživatel přihlášen
    if (isset($_SESSION["user_id"])) {
        // Pokud uživatel projevil zájem o produkt
        if ($_SESSION['user_id'] == $item['interested_id']) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">U tohoto produktu jsi projevil zájem.</div>';
        }
        // Pokud produkt již někdo zamluvil a není to aktuální uživatel
        if (($_SESSION['user_id'] != $item['interested_id']) && ($item['interested_id'] != 0) && ($_SESSION['user_id'] != $item['user_id'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Tento produkt si již někdo zamluvil.</div>';
        }
    }

    // Pokud je položka archivována, zobrazí se upozornění
    if ($item['archived'] == 1) {
        echo '<div class="h2 alert alert-danger alert-dismissible fade show" role="alert">Tato položka je již prodána.</div>';
    }

    // Pokud je uživatel přihlášen a není to archivovaný produkt
    if (!empty($_SESSION['user_id']) && empty($_GET['archiveid'])) {
        // Pokud produkt není archivován nebo je uživatel admin, umožníme editaci nebo smazání produktu
        if ($item['archived'] != 1 || $currentUser['role'] == 'admin') {
            if ($_SESSION['user_id'] == $item['user_id'] || $currentUser['role'] == 'admin') {
                echo '<div class="row my-3">
                        <a href="edit.php?id=' . $_GET['id'] . '" class="btn btn-primary mb-1">Editovat</a>
                        <a href="delete.php?id=' . $_GET['id'] . '" class="btn btn-danger mb-1">Smazat</a>
                     </div>';
            }
        }
    }

    // Načteme recenze uživatele, který produkt přidal
    $queryReview = $db->prepare('SELECT * FROM review WHERE user_id=:id;');
    $queryReview->execute([
        ':id' => $item['user_id']
    ]);
    $reviews = $queryReview;
    $yes = 0;
    $no = 0;

    // Spočítáme pozitivní a negativní recenze
    if (!empty($reviews)){
        foreach($reviews as $review){
            if ($review['recomend'] == "ano"){
                $yes++;
            } else {
                $no++;
            }
        }
    }

    // Nastavíme výchozí obrázek pro položku
    $filename = "inc/uploaded_files/image.jpg";
    $id = $item['id'];
    // Hledáme soubor s odpovídajícím ID
    $files = glob("inc/uploaded_files/$id.*");
    foreach ($files as $file){
        $filename = $file; // Pokud existuje soubor, použijeme ho jako obrázek
    }

    // Zobrazíme informace o produktu, včetně obrázku a popisu
    echo '
    <h2 class="border border-dark text-center">' . htmlspecialchars($item['name']) . '</h2>
    <div class="d-flex justify-content-center"><img src="'.$filename.'" alt="photo" class="img-thumbnail"></div>
    <p class="h4 border border-dark text-center">'. htmlspecialchars($item['description']).'</p>
    <div class="d-flex justify-content-between">
    <p class="h5">Cena: '. round(htmlspecialchars($item['price']), 0) .' Kč</p>
    <a href="profile.php?id='.$user['user_id'].'" class="text-dark"><p class="h5">Uživatel: '. htmlspecialchars($user['name']).'(+'.$yes.'/-'.$no.')</p></a>
    <p class="h5">Email: '. htmlspecialchars($user['email']).'</p>
    </div>
    ';

    // Pokud není uživatel vlastníkem nebo zájemcem, zobrazí se možnost projevit zájem o produkt
    if (!empty($_SESSION['user_id']) && ($_SESSION['user_id'] != $item['user_id']) && ($_SESSION['user_id'] != $item['interested_id']) && ($item['archived'] == 0)) {
        echo '<form method="post" class="row my-3">
                <button type="submit" name="interest" class="btn btn-primary">Mám zájem</button>
              </form>';
    }

    echo '</div>';
} else {
    // Pokud položka není nalezena, přesměrujeme na hlavní stránku
    header('Location: index.php');
    exit();
}

echo '</div></div>';

// Načteme patičku stránky
include __DIR__ . '/inc/footer.php';
