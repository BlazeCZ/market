<?php

require 'inc/user.php'; // Načtení souboru s informacemi o uživateli

// Kontrola, zda je uživatel přihlášen
if (empty($_SESSION['user_id'])) {
    // Pokud není přihlášen, přesměrujeme ho na hlavní stránku s chybovou hláškou
    header("Location: index.php?error=login");
    exit();
}

// Kontrola, zda je v URL adrese předán parametr ID uživatele, který má být hodnocen
if (!empty($_GET['id'])) {

    // Dotaz na archivované transakce mezi přihlášeným uživatelem a cílovým uživatelem
    $queryArchive = $db->prepare('SELECT * FROM archive WHERE (buyer_id=:id) AND (user_id=:user)');
    $queryArchive->execute([
        ':id' => $_SESSION['user_id'],
        ':user' => $_GET['id']
    ]);
    $archive = $queryArchive->fetchAll(PDO::FETCH_ASSOC);

    // Kontrola, zda uživatel již napsal hodnocení
    if (!empty($archive)) {
        $queryReview = $db->prepare('SELECT * FROM review WHERE (user_id=:user) AND (evaluator_id=:id)');
        $queryReview->execute([
            ':user' => $_GET['id'],
            ':id' => $_SESSION['user_id']
        ]);
        $reviewDone = $queryReview->fetch(PDO::FETCH_ASSOC);
    } else {
        $reviewDone = 1; // Pokud není žádný záznam v archivu, hodnocení není možné
    }

    // Dotaz na informace o hodnoceném uživateli
    $queryUser = $db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1;');
    $queryUser->execute([
        ':id' => $_GET['id']
    ]);
    $user = $queryUser->fetch(PDO::FETCH_ASSOC);

    // Pokud uživatel ještě nebyl hodnocen, pokračujeme v zobrazení formuláře
    if (empty($reviewDone)) {

        $errors = []; // Pole pro ukládání chybových hlášek

        // Zpracování formuláře po odeslání
        if (!empty($_POST)) {

            // Kontrola, zda byla vybrána možnost doporučení
            if (empty($_POST['recomend'])) {
                $errors['recomend'] = 'Musíte vybrat jednu z možností.';
            }

            // Kontrola, zda bylo zadáno slovní hodnocení
            $reviewText = trim(@$_POST['text']);
            if (empty($reviewText)) {
                $errors['text'] = 'Je potřeba zadat alespoň nějaké hodnocení.';
            }

            // Pokud nejsou chyby, uložíme hodnocení do databáze
            if (empty($errors)) {
                $saveQuery = $db->prepare('INSERT INTO review (user_id, text, evaluator_id, recomend) VALUES (:user, :text, :evaluator, :recomend);');
                $saveQuery->execute([
                    ':user' => $user['user_id'],
                    ':text' => $reviewText,
                    ':evaluator' => $_SESSION['user_id'],
                    ':recomend' => $_POST['recomend']
                ]);

                // Přesměrování na profil hodnoceného uživatele
                header('Location: profile.php?id=' . $_GET['id']);
                exit();
            }
        }

        include __DIR__ . '/inc/header.php'; // Načtení hlavičky stránky

        $postCategory = 0; // Nevyužitá proměnná (může být odstraněna)
    } else {
        // Pokud uživatel již hodnocení napsal, přesměrujeme ho zpět na profil
        header('Location: profile.php?id=' . $_GET['id']);
        exit();
    }
?>

<!-- HTML formulář pro hodnocení uživatele -->
<h2>Hodnocení uživatele: <?php echo htmlspecialchars($user['name']); ?></h2>

<form method="post">
    <div class="form-group">
        <label for="recomend">Doporučil byste tohoto prodejce dalším uživatelům?</label>
        <select name="recomend" id="recomend" required class="form-control <?php echo (!empty($errors['recomend']) ? 'is-invalid' : ''); ?>">
            <option value="">--vyberte--</option>
            <option value="ano">Ano</option>
            <option value="ne">Ne</option>
        </select>
        <?php
        // Zobrazení chybové hlášky pro doporučení
        if (!empty($errors['recomend'])) {
            echo '<div class="text-danger"><small>' . $errors['recomend'] . '</small></div>';
        }
        ?>
    </div>

    <div class="form-group">
        <label for="text">Slovní hodnocení:</label>
        <textarea name="text" id="text" required class="form-control"><?php echo isset($_POST["text"]) ? htmlspecialchars($_POST["text"]) : ''; ?></textarea>
        <?php
        // Zobrazení chybové hlášky pro textové hodnocení
        if (!empty($errors['text'])) {
            echo '<div class="text-danger"><small>' . $errors['text'] . '</small></div>';
        }
        ?>
    </div>

    <button type="submit" class="btn btn-primary">Vložit</button>
    <a href="profile.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" class="btn btn-light">Zrušit</a>
</form>

<?php
} else {
    // Pokud není ID v URL adrese, přesměrování na hlavní stránku
    header('Location: index.php');
    exit();
}

include __DIR__ . '/inc/footer.php'; // Načtení patičky stránky
?>
