<?php

// Načítání souboru pro práci s uživatelskými daty (např. autentifikace, role, atd.)
require 'inc/user.php';

// Kontrola, zda je uživatel přihlášen. Pokud není, přesměruje ho na přihlašovací stránku
if (empty($_SESSION['user_id'])){
    // Uživatel není přihlášen, přesměrování na přihlašovací stránku
    header("Location: index.php?error=login");
    exit();
}

// Pokud je v URL parametr 'id', pokračuje se s načítáním položky (zboží) pro úpravy
if (!empty($_GET['id']) ) {
    // Příprava SQL dotazu pro získání položky podle jejího ID
    $query = $db->prepare('SELECT * FROM goods WHERE id=?');
    $query->execute(array($_GET['id']));
    $item = $query->fetch(PDO::FETCH_ASSOC); // Načtení položky jako asociativní pole
} else {
    // Pokud není parametr 'id', přesměruje na hlavní stránku
    header("Location: index.php");
    exit();
}

// Pokud má aktuálně přihlášený uživatel roli admin nebo je vlastníkem položky, může upravovat položku
if ($_SESSION['user_id'] == $item['user_id'] || $currentUser['role'] == 'admin') {

    // Inicializace proměnných pro položku
    $itemId = '';
    $itemCategory = (!empty($_REQUEST['category']) ? intval($_REQUEST['category']) : '');
    $itemTitle = '';
    $itemText = '';
    $itemPrice = '';

    // Inicializace pole pro chyby formuláře
    $errors = [];

    // Pokud byl formulář odeslán
    if (!empty($_POST)) {

        // Ověření a zpracování kategorie
        if (!empty($_POST['category'])) {
            $categoryQuery = $db->prepare('SELECT * FROM categories WHERE category_id=:category LIMIT 1;');
            $categoryQuery->execute([ ':category' => $_POST['category'] ]);
            if ($categoryQuery->rowCount() == 0) {
                $errors['category'] = 'Zvolená kategorie neexistuje!';
                $itemCategory = '';
            } else {
                $itemCategory = $_POST['category'];
            }
        } else {
            $errors['category'] = 'Musíte vybrat kategorii.';
        }

        // Ověření a zpracování textu položky
        $itemText = trim(@$_POST['text']);
        if (empty($itemText)) {
            $errors['text'] = 'Musíte zadat alespoň nějaké informace o položce.';
        }

        // Ověření a zpracování nadpisu položky
        $itemTitle = trim(@$_POST['title']);
        if (empty($itemTitle)) {
            $errors['title'] = 'Musíte zadat nadpis položky.';
        }

        // Ověření a zpracování ceny
        $itemPrice = trim(@$_POST['price']);
        if (empty($itemPrice) || !is_numeric($itemPrice)) {
            $errors['price'] = 'Musíte zadat požadovanou cenu v korunách.';
        }

        // Pokud nejsou žádné chyby, provede se uložení změn do databáze
        if (empty($errors)) {
            // Příprava SQL dotazu pro aktualizaci položky
            $saveQuery = $db->prepare('UPDATE goods SET name=:title, description=:text, price=:price, category_id=:category WHERE id=:id;');
            $saveQuery->execute([
                ':title' => $itemTitle,
                ':text' => $itemText,
                ':price' => $itemPrice,
                ':category' => $itemCategory,
                'id' => $_GET['id']
            ]);

            // Po uložení položky načteme položku z databáze pro další operace (např. pro připojení obrázku)
            $loadQuery = $db->prepare('SELECT * FROM goods WHERE name=:title AND description=:text AND price=:price AND category_id=:category AND user_id=:user ');
            $loadQuery->execute([
                ':title' => $itemTitle,
                ':text' => $itemText,
                ':price' => $itemPrice,
                ':category' => $itemCategory,
                ':user' => $_SESSION['user_id']
            ]);
            $load = $loadQuery->fetch(PDO::FETCH_ASSOC);

            // Pokud je součástí formuláře nahrání souboru (obrázku)
            if (isset($_FILES['uploadedFile'])) {
                $fileTmpPath = $_FILES['uploadedFile']['tmp_name'];
                $fileName = $_FILES['uploadedFile']['name'];
                $fileSize = $_FILES['uploadedFile']['size'];
                $fileType = $_FILES['uploadedFile']['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $newFileName = $load['id'] . '.' . $fileExtension;

                // Povolené formáty souborů
                $allowedfileExtensions = array('jpg', 'png');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $uploadFileDir = 'inc/uploaded_files/';
                    $dest_path = $uploadFileDir . $newFileName;

                    // Pokud soubor již existuje, smaže ho před nahráním nového
                    if (file_exists($dest_path)) {
                        unlink($dest_path);
                    }

                    // Nahrání souboru na server
                    move_uploaded_file($fileTmpPath, $dest_path);
                } else {
                    // Chybová hláška pro neplatný formát souboru
                    $error['upload'] = 'Nahrávání se nezdařilo. Povolené formáty: ' . implode(',', $allowedfileExtensions);
                }
            }

            // Po úspěšném uložení přesměruje na hlavní stránku s kategorií
            header('Location: index.php?kategorie=' . $itemCategory);
            exit();
        }
    }

    // Načítání hlavičky stránky
    include __DIR__ . '/inc/header.php';

    // Inicializace proměnné pro kategorii
    $postCategory = 0;
    ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="category">Kategorie:</label>
            <select name="category" id="category" required class="form-control <?php echo (!empty($errors['category']) ? 'is-invalid' : ''); ?>">
                <option value="">--vyberte--</option>
                <?php
                // Načítání seznamu kategorií
                $categoryQuery = $db->prepare('SELECT * FROM categories ORDER BY name;');
                $categoryQuery->execute();
                $categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        echo '<option value="' . $category['category_id'] . '" ' . ($category['category_id'] == $item['category_id'] ? 'selected="selected"' : '') . '>' . htmlspecialchars($category['name']) . '</option>';
                    }
                }
                ?>
            </select>
            <?php
            if (!empty($errors['category'])) {
                echo '<div class="text-danger"><small>' . $errors['category'] . '</small></div>';
            }
            ?>
        </div>

        <div class="form-group">
            <label for="title" class="form-label">Nadpis:</label>
            <input name="title" id="title" required class="form-control" value="<?php echo htmlspecialchars($item['name']); echo htmlspecialchars(@$_POST['title']); ?>">
            <?php
            if (!empty($errors['title'])) {
                echo '<div class="text-danger"><small>' . $errors['title'] . '</small></div>';
            }
            ?>
        </div>

        <div class="form-group">
            <label for="price" class="form-label">Cena:</label>
            <input name="price" id="price" required class="form-control" value="<?php echo htmlspecialchars($item['price']); echo htmlspecialchars(@$_POST['price']); ?>">
            <?php
            if (!empty($errors['price'])) {
                echo '<div class="text-danger"><small>' . $errors['price'] . '</small></div>';
            }
            ?>
        </div>

        <div class="form-group">
            <label for="text">Text příspěvku:</label>
            <textarea name="text" id="text" required placeholder=" <?php echo htmlspecialchars($item['description']) ?> " class="form-control <?php echo(!empty($errors['text']) ? 'is-invalid' : ''); ?>"><?php echo htmlspecialchars($item['description']); echo htmlspecialchars(@$_POST['text']); ?></textarea>
            <?php
            if (!empty($errors['text'])) {
                echo '<div class="text-danger">' . $errors['text'] . '</div>';
            }
            ?>
            <div class="form-group mb-5">
                <label for="file-upload" class="form-label">Nahrazení fotografie novou</label>
                <input class="form-control" type="file" id="uploadedFile" name="uploadedFile" >
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Uložit</button>
        <a href="index.php?category=<?php echo $postCategory;?>" class="btn btn-light">zrušit</a>
    </form>

<?php
    // Načítání patičky stránky
    include __DIR__ . '/inc/footer.php';

} else {
    // Pokud uživatel nemá právo upravovat položku, přesměrování na hlavní stránku s chybovou zprávou
    header("Location: index.php?error=user");
    exit();
}