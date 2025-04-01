<?php

// Refacotirng, 
// Načteme připojení k databázi a inicializujeme session
require_once 'inc/user.php';

// Zkontrolujeme, zda je uživatel přihlášen. Pokud není, přesměrujeme ho na hlavní stránku s chybovou hláškou.
if (empty($_SESSION['user_id'])){
    header("Location: index.php?error=login");
    exit();
}

// Pokud uživatel nemá roli "admin", přesměrujeme ho na hlavní stránku s chybovou hláškou.
if ($currentUser['role'] != 'admin') {
    header("Location: index.php?error=admin");
    exit();
}

$categoryId = '';  // Proměnná pro uchování ID kategorie
$categoryName = '';  // Proměnná pro uchování názvu kategorie

// Pokud je v URL parametr 'id', znamená to, že chceme upravit existující kategorii
if (!empty($_REQUEST['id'])){
    $pageTitle = 'Úprava kategorie';  // Titulek stránky pro úpravu

    // Načteme údaje o kategorii podle ID
    $categoryQuery = $db->prepare('SELECT * FROM categories WHERE category_id=:id LIMIT 1;');
    $categoryQuery->execute([':id' => $_REQUEST['id']]);

    // Pokud kategorie existuje, naplníme proměnné daty
    if ($category = $categoryQuery->fetch(PDO::FETCH_ASSOC)){
        $categoryId = $category['category_id'];
        $categoryName = $category['name'];
    } else {
        // Pokud kategorie neexistuje, přesměrujeme uživatele na stránku pro přidání kategorie s chybovou hláškou
        header("Location: categoryNew.php?error=nonexist");
        exit();
    }
} else {
    // Pokud není parametr 'id', nastavíme titulek pro vytvoření nové kategorie
    $pageTitle = 'Nová kategorie';
}

// Pole pro uchování chyb při zpracování formuláře
$errors = [];

// Pokud byl formulář odeslán, zpracujeme data
if (!empty($_POST)){
    $categoryName = trim(@$_POST['name']);  // Získáme název kategorie z formuláře
    if (empty($categoryName)){
        // Pokud název kategorie není zadán, přidáme chybu
        $errors['name'] = 'Musíte zadat název kategorie.';
    }

    // Pokud nejsou žádné chyby, provedeme uložení
    if (empty($errors)){
        if ($categoryId){
            // Pokud má kategorie ID (tedy se jedná o úpravu existující kategorie)
            $saveQuery = $db->prepare('UPDATE categories SET name=:name WHERE category_id=:id LIMIT 1;');
            $saveQuery->execute([
                ':name' => $categoryName,
                ':id' => $categoryId
            ]);

            // Po úspěšné úpravě přesměrujeme na stránku pro úpravu s informací o úspěšné aktualizaci
            header('Location: categoryNew.php?phase=exist');
            exit();
        } else {
            // Pokud kategorie nemá ID (tedy se jedná o novou kategorii)
            $saveQuery = $db->prepare('INSERT INTO categories (name) VALUES (:name);');
            $saveQuery->execute([
                ':name' => $categoryName
            ]);

            // Po úspěšném vytvoření nové kategorie přesměrujeme na stránku pro novou kategorii s informací o úspěchu
            header('Location: categoryNew.php?phase=new');
            exit();
        }
    }
}

// Načteme hlavičku stránky
include 'inc/header.php';
?>

<form method="post">
    <div class="form-group">
        <label for="name">Název kategorie:</label>
        <textarea name="name" id="name" required class="form-control <?php echo (!empty($errors['name']) ? 'is-invalid' : ''); ?>">
            <?php echo htmlspecialchars($categoryName); ?>
        </textarea>
        <?php
        // Pokud byla chyba u názvu kategorie, zobrazíme chybovou zprávu
        if (!empty($errors['name'])){
            echo '<div class="invalid-feedback">'.$errors['name'].'</div>';
        }
        ?>
    </div>

    <button type="submit" class="btn btn-primary">uložit...</button>
    <a href="categoryNew.php" class="btn btn-light">zrušit</a>
</form>

<?php
// Načteme patičku stránky
include 'inc/footer.php';
