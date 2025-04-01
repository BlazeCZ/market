<?php

// Zahrnutí souboru pro práci s uživatelskými daty (např. pro připojení k databázi, ověřování, atd.)
require 'inc/user.php';

// Ověření, zda je uživatel přihlášen. Pokud není, přesměrování na úvodní stránku s chybovou hláškou.
if (empty($_SESSION['user_id'])){
    // Uživatel není přihlášen, přesměrování na index
    header("Location: index.php?error=login");
    exit();
}

// Inicializace proměnných pro formulář
$itemId = '';
$itemCategory = (!empty($_REQUEST['category']) ? intval($_REQUEST['category']) : '');
$itemTitle = '';
$itemText = '';
$itemPrice = '';

// Pole pro uchování chybových hlášení
$errors = [];

// Zpracování formuláře
if (!empty($_POST)) {

    // Ověření kategorie
    if (!empty($_POST['category'])) {

        // Příprava dotazu na ověření existence kategorie v databázi
        $categoryQuery = $db->prepare('SELECT * FROM categories WHERE category_id=:category LIMIT 1;');
        $categoryQuery->execute([
            ':category' => $_POST['category']
        ]);
        
        // Kontrola, zda kategorie existuje
        if ($categoryQuery->rowCount() == 0) {
            // Pokud kategorie neexistuje, přidání chyby
            $errors['category'] = 'Zvolená kategorie neexistuje!';
            $itemCategory = '';
        } else {
            // Nastavení zvolené kategorie
            $itemCategory = $_POST['category'];
        }

    } else {
        // Pokud není zvolená kategorie, přidání chyby
        $errors['category'] = 'Musíte vybrat kategorii.';
    }

    // Ověření textu položky
    $itemText = trim(@$_POST['text']);
    if (empty($itemText)) {
        $errors['text'] = 'Musíte zadat alespoň nějaké informace o položce.';
    }

    // Ověření nadpisu položky
    $itemTitle = trim(@$_POST['title']);
    if (empty($itemTitle)) {
        $errors['title'] = 'Musíte zadat nadpis položky.';
    }

    // Ověření ceny položky
    $itemPrice = trim(@$_POST['price']);
    if (empty($itemPrice) || !is_numeric($itemPrice) || $itemPrice < 0) {
        $errors['price'] = 'Musíte zadat požadovanou cenu v korunách. (Cena nemůže být záporná!)';
    }

    // Pokud nejsou žádné chyby, uložíme položku do databáze
    if (empty($errors)) {
        // Příprava dotazu pro vložení položky
        $saveQuery = $db->prepare('INSERT INTO goods (name, description, price, category_id, user_id) VALUES (:title, :text, :price, :category, :user);');
        $saveQuery->execute([
            ':title' => $itemTitle,
            ':text' => $itemText,
            ':price' => $itemPrice,
            ':category' => $itemCategory,
            ':user' => $_SESSION['user_id']
        ]);

        // Načtení právě vložené položky
        $loadQuery = $db->prepare('SELECT * FROM goods WHERE name=:title AND description=:text AND price=:price AND category_id=:category AND user_id=:user ');
        $loadQuery->execute([
            ':title' => $itemTitle,
            ':text' => $itemText,
            ':price' => $itemPrice,
            ':category' => $itemCategory,
            ':user' => $_SESSION['user_id']
        ]);
        $load = $loadQuery->fetch(PDO::FETCH_ASSOC);

        // Kontrola a zpracování nahraného souboru
        if (isset($_FILES['uploadedFile'])) {
            // Získání informací o souboru
            $fileTmpPath = $_FILES['uploadedFile']['tmp_name'];
            $fileName = $_FILES['uploadedFile']['name'];
            $fileSize = $_FILES['uploadedFile']['size'];
            $fileType = $_FILES['uploadedFile']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Nový název souboru
            $newFileName = $load['id'] . '.' . $fileExtension;

            // Povolené přípony souborů
            $allowedfileExtensions = array('jpg', 'png');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Cíl pro uložení souboru
                $uploadFileDir = 'inc/uploaded_files/';
                $dest_path = $uploadFileDir . $newFileName;
                // Přesunutí souboru na cílové místo
                move_uploaded_file($fileTmpPath, $dest_path);
            } else {
                // Chybová hláška pro nepovolený formát
                $error['upload'] = 'Nahrávání se nezdařilo. Povolené formáty: ' . implode(',', $allowedfileExtensions);
            }
        }

        // Přesměrování na úvodní stránku po úspěšném vložení položky
        header('Location: index.php?kategorie=' . $itemCategory);
        exit();
    }
}

// Načtení hlavičky stránky
include __DIR__ . '/inc/header.php';

// Zobrazení formuláře pro vložení položky
$postCategory = 0;
?>
<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="category">Kategorie:</label>
        <select name="category" id="category" required class="form-control <?php echo (!empty($errors['category'])?'is-invalid':''); ?>">
            <option value="">--vyberte--</option>
            <?php
            // Načtení dostupných kategorií z databáze
            $categoryQuery = $db->prepare('SELECT * FROM categories ORDER BY name;');
            $categoryQuery->execute();
            $categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    echo '<option value="' . $category['category_id'] . '" ' . ($category['category_id'] == $postCategory ? 'selected="selected"' : '') . '>' . htmlspecialchars($category['name']) . '</option>';
                }
            }
            ?>
        </select>
        <?php
        // Zobrazení chyby pro kategorii, pokud je přítomna
        if (!empty($errors['category'])) {
            echo '<div class="text-danger"><small>' . $errors['category'] . '</small></div>';
        }
        ?>
    </div>

    <div class="form-group">
        <label for="title" class="form-label">Nadpis:</label>
        <input name="title" id="title" required class="form-control" value="<?php echo isset($_POST["title"]) ? $_POST["title"] : ''; ?>">
        <?php
        // Zobrazení chyby pro nadpis, pokud je přítomna
        if (!empty($errors['title'])) {
            echo '<div class="text-danger"><small>' . $errors['title'] . '</small></div>';
        }
        ?>
    </div>

    <div class="form-group">
        <label for="price" class="form-label">Cena:</label>
        <input name="price" id="price" required class="form-control" value="<?php echo isset($_POST["price"]) ? $_POST["price"] : ''; ?>">
        <?php
        // Zobrazení chyby pro cenu, pokud je přítomna
        if (!empty($errors['price'])) {
            echo '<div class="text-danger"><small>' . $errors['price'] . '</small></div>';
        }
        ?>
    </div>

    <div class="form-group">
        <label for="text">Text:</label>
        <textarea name="text" id="text" required class="form-control"><?php echo isset($_POST["text"]) ? $_POST["text"] : ''; ?></textarea>
        <?php
        // Zobrazení chyby pro text, pokud je přítomna
        if (!empty($errors['text'])) {
            echo '<div class="text-danger"><small>' . $errors['text'] . '</small></div>';
        }
        ?>
    </div>

    <div class="form-group mb-5">
        <label for="file-upload" class="form-label">Vyberte fotografii položky</label>
        <input class="form-control" type="file" id="uploadedFile" name="uploadedFile">
    </div>

    <button type="submit" class="btn btn-primary">Vložit</button>
    <a href="index.php?category=<?php echo $postCategory; ?>" class="btn btn-light">zrušit</a>
</form>

<?php
// Načtení patičky stránky
include __DIR__ . '/inc/footer.php';
?>