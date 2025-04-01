<?php

// Načtení uživatelských funkcí a relace
require 'inc/user.php';

// Vložení hlavičky stránky
include __DIR__ . '/inc/header.php';

// Kontrola přihlášení uživatele
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?error=login");
    exit();
}

// Kontrola, zda uživatel odpovídá ID v GET parametru
if ($_SESSION['user_id'] == $_GET['id']) {
    echo '<div class="container-fluid">';
    echo '<div class="row flex-nowrap">';
    echo '<div class="col ps-md-3 pt-3">';
    echo '<h2>Moje nákupy</h2>';

    // Načtení zakoupených položek z archivu
    $queryGoods = $db->prepare('SELECT * FROM archive WHERE buyer_id=:id ORDER BY id DESC;');
    $queryGoods->execute([
        ':id' => $_SESSION['user_id']
    ]);
    $goods = $queryGoods->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($goods)) {
        echo '<div class="row">';
        
        // Výpis zakoupených položek
        foreach ($goods as $item) {
            $filename = "inc/uploaded_files/image.jpg";
            $id = $item['id'];
            
            // Kontrola existence souborů s obrázky
            $files = glob("inc/uploaded_files/$id.*");
            foreach ($files as $file){
                $filename = $file;
            }

            // Vykreslení karty zakoupené položky
            echo '<div class="col-sm-4 text-dark" >';
            echo '<div class="card">';
            echo '<a class="card-title h3 text-dark" href="item.php?archiveid=' . $item['id'] . '"> <img src="'.$filename.'" alt="photo" class="card-img-top img-thumbnail"></a>';
            echo '<div class="card-body">';
            echo '<a class="card-title h3 text-dark" href="item.php?archiveid=' . $item['id'] . '">' . htmlspecialchars($item['name']) . '</a>';
            echo '<p class="card-text">' . htmlspecialchars($item['description']) . '</p>';
            echo '<p class="card-text">Cena: ' . htmlspecialchars($item['price']) . '</p>';
            echo '</div>';
            echo '</div></div>';
        }
        echo '</div>'; // Zavření řádku s kartami
        echo '</div>'; // Zavření sloupce s obsahem
    } else {
        // Pokud uživatel nemá žádné zakoupené položky
        echo '<p>Nevedeme u vás žádné provedené nákupy.</p>';
    }
    echo '</div></div>'; // Zavření kontejneru
} else {
    // Přesměrování při neautorizovaném přístupu
    header("Location: index.php");
    exit();
}

// Vložení patičky stránky
include __DIR__ . '/inc/footer.php';

?>
