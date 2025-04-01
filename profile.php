<?php

require 'inc/user.php'; // Načtení souboru se skriptem pro uživatele

include __DIR__ . '/inc/header.php'; // Načtení hlavičky stránky

// Načtení kategorií z databáze
$categories = $db->query('SELECT * FROM categories ORDER BY name;')->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="container-fluid">';
echo '<div class="row flex-nowrap">';

// Kontrola, zda existují kategorie
if (!empty($categories)) {
    echo '<div class="border-primary border-5 col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">';
        echo '<div id="sidebar" class="collapse collapse-horizontal show ">';
            echo '<div id="sidebar-nav" class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-50">';
                echo '<ul class="nav nav-pills flex-column mb-sm-auto mb-0 text-center" id="menu">';
                    foreach ($categories as $category) {
                        echo '    <li class="nav-item">
                                    <a href="./?kategorie=' . htmlspecialchars($category['category_id']) . '" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">' . htmlspecialchars($category['name']) . '</span> </a>
                                  </li>';
                    }
                    // Odkaz na archiv
                    echo '<li class="nav-item mt-10"><a href="archivedItems.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline">Archiv</span></a></li>';

    // Pokud je uživatel přihlášen a je admin, zobrazit odkaz na úpravu kategorií
    if (!empty($_SESSION['user_id']) && $currentUser['role'] == 'admin') {
        echo '<li class="nav-item mt-10"><a href="categoryNew.php" class="nav-link align-middle px-0 text-light" data-bs-parent="#sidebar"><span class="ms-1 d-none d-sm-inline btn-primary btn">Úprava kategorii</span></a></li>';
    }
                echo '</ul>
                </div>
        </div>
 </div>';
}

// Kontrola, zda je v URL parametr "id"
if (!empty($_GET['id'])) {

    // Načtení dat o uživateli z databáze
    $queryUser= $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1;');
    $queryUser->execute([
        ':id' => $_GET['id']
    ]);
    $user = $queryUser->fetch(PDO::FETCH_ASSOC);

    // Pokud je uživatel přihlášen, kontrola archivu a hodnocení
    if (!empty($_SESSION['user_id'])) {
        $queryArchive = $db->prepare('SELECT * FROM archive WHERE (buyer_id=:id) AND (user_id=:user)');
        $queryArchive->execute([
            ':id' => $_SESSION['user_id'],
            ':user' => $_GET['id']
        ]);
        $archive = $queryArchive->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($archive)) {
            $queryReview = $db->prepare('SELECT * FROM review WHERE (user_id=:user) AND (evaluator_id=:id)');
            $queryReview->execute([
                ':user' => $_GET['id'],
                ':id' => $_SESSION['user_id']
            ]);
            $reviewDone = $queryReview->fetch(PDO::FETCH_ASSOC);
        } else {
            $reviewDone = 1;
        }
    } else {
        $reviewDone = 1;
    }

    // Pokud byl uživatel nalezen, zobrazit jeho profil
    if (!empty($user)) {
        echo '<div class="col ps-md-3 pt-3">
                <h2>Hodnocení a inzeráty uživatele ' . htmlspecialchars($user['name']) . '</h2>
                <div><h3>Hodnocení</h3>
                <ul class="list-group">';
        
        // Možnost přidat hodnocení
        if (empty($reviewDone)) {
            echo '<a class="btn btn-primary" href="review.php?id=' . $_GET['id'] . '">Přidat hodnocení</a>';
        }

        // Načtení recenzí uživatele
        $queryReview = $db->prepare('SELECT * FROM review WHERE user_id=:id');
        $queryReview->execute([
            ':id' => $user['user_id']
        ]);
        $reviews = $queryReview->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($reviews)) {
            foreach ($reviews as $review) {
                $queryUsers = $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1;');
                $queryUsers->execute([
                    ':id' => $review['evaluator_id']
                ]);
                $users = $queryUsers->fetch(PDO::FETCH_ASSOC);

                if ($review['recomend'] == "ne") {
                    echo '<li class="list-group-item border-danger">' . htmlspecialchars($review['text']) . ' - Uživatel: ' . htmlspecialchars($users['name']) . '</li>';
                } else {
                    echo '<li class="list-group-item border-success">' . htmlspecialchars($review['text']) . ' - Uživatel: ' . htmlspecialchars($users['name']) . '</li>';
                }
            }
        } else {
            echo '<li class="list-group-item">Tento uživatel nemá zatím žádné hodnocení.</li>';
        }

        echo '</ul></div>';

        // Zobrazení inzerátů uživatele
        echo '<div><h4>Inzeráty</h4>';
        $queryGoods = $db->prepare('SELECT * FROM goods WHERE user_id=:id ORDER BY id DESC;');
        $queryGoods->execute([
            ':id' => $user['user_id']
        ]);
        $goods = $queryGoods->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($goods)) {
            echo '<div class="row">';
            foreach ($goods as $item) {
                $filename = "inc/uploaded_files/image.jpg";
                $id = $item['id'];
                $files = glob("inc/uploaded_files/$id.*");
                foreach ($files as $file) {
                    $filename = $file;
                }

                echo '<div class="col-sm-4 text-dark">';
                echo '<div class="card">';
                echo '<a class="card-title h3 text-dark" href="item.php?id=' . $item['id'] . '"><img src="' . $filename . '" alt="photo" class="card-img-top img-thumbnail"></a>';
                echo '<div class="card-body">
                        <a class="card-title h3 text-dark" href="item.php?id=' . $item['id'] . '">' . htmlspecialchars($item['name']) . '</a>
                        <p class="card-text">' . htmlspecialchars($item['description']) . '</p>
                        <p class="card-text">Cena: ' . htmlspecialchars($item['price']) . '</p>';

                if ($item['interested_id'] > 0) {
                    echo '<p class="card-text h6">O tento produkt někdo projevil zájem</p>';
                    if ($item['user_id'] == $_SESSION['user_id']) {
                        echo '<div class="d-flex justify-contet-between center">
                                <a class="btn btn-success m-1" href="archive.php?prodano=ano&id=' . $item['id'] . '">Prodáno</a>
                                <a class="btn btn-danger m-1" href="archive.php?prodano=ne&id=' . $item['id'] . '">Neprodáno</a>
                              </div>';
                    }
                }

                echo '</div></div></div>';
            }
            echo '</div>';
        } else {
            echo '<p>Uživatel nemá žádné aktivní inzeráty.</p>';
        }

        echo '</div></div>';
    } else {
        echo '<div class="col ps-md-3 pt-3"><h2>Takový uživatel neexistuje.</h2></div>';
    }
} else {
    echo '<div class="col ps-md-3 pt-3"><h2>Nebyl vybrán žádný uživatel.</h2></div>';
}

include __DIR__ . '/inc/footer.php';
?>