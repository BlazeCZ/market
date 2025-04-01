<?php

require_once 'inc/user.php'; // Načtení souboru s informacemi o uživateli

// Kontrola, zda je uživatel již přihlášen, pokud ano, přesměrování na hlavní stránku
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = []; // Pole pro ukládání chyb

// Zpracování formuláře, pokud byl odeslán
if (!empty($_POST)) {

    // Načtení a validace jména/přezdívky
    $name = trim(@$_POST['name']);
    if (empty($name)) {
        $errors['name'] = 'Musíte zadat své jméno či přezdívku.';
    }

    // Načtení a validace e-mailu
    $email = trim(@$_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Musíte zadat platnou e-mailovou adresu.';
    } else {
        // Kontrola, zda e-mail již existuje v databázi
        $mailQuery = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
        $mailQuery->execute([
            ':email' => $email
        ]);
        if ($mailQuery->rowCount() > 0) {
            $errors['email'] = 'Uživatelský účet s touto e-mailovou adresou již existuje.';
        }
    }

    // Kontrola hesla - minimální délka 5 znaků
    if (empty($_POST['password']) || (strlen($_POST['password']) < 5)) {
        $errors['password'] = 'Musíte zadat heslo o délce alespoň 5 znaků.';
    }

    // Kontrola, zda se hesla shodují
    if ($_POST['password'] != $_POST['password2']) {
        $errors['password2'] = 'Zadaná hesla se neshodují.';
    }

    // Pokud nejsou žádné chyby, uložíme uživatele do databáze
    if (empty($errors)) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Zahashování hesla

        // Vložení nového uživatele do databáze
        $query = $db->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password);');
        $query->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password
        ]);

        // Nastavení session pro nového uživatele
        $_SESSION['user_id'] = $db->lastInsertId();
        $_SESSION['user_name'] = $name;

        // Přesměrování na hlavní stránku
        header('Location: index.php');
        exit();
    }
}

// Nastavení názvu stránky
$pageTitle = 'Registrace nového uživatele';
include 'inc/header.php';
?>

<h2>Registrace nového uživatele</h2>

<!-- Formulář pro registraci -->
<form method="post">
    <div class="form-group">
        <label for="name">Jméno či přezdívka:</label>
        <input type="text" name="name" id="name" required class="form-control <?php echo (!empty($errors['name']) ? 'is-invalid' : ''); ?>"
               value="<?php echo htmlspecialchars(@$name); ?>" />
        <?php
        // Zobrazení chybové zprávy, pokud existuje
        echo (!empty($errors['name']) ? '<div class="invalid-feedback">' . $errors['name'] . '</div>' : '');
        ?>
    </div>

    <div class="form-group">
        <label for="email">E-mail:</label>
        <input type="email" name="email" id="email" required class="form-control <?php echo (!empty($errors['email']) ? 'is-invalid' : ''); ?>"
               value="<?php echo htmlspecialchars(@$email); ?>" />
        <?php
        // Zobrazení chybové zprávy, pokud existuje
        echo (!empty($errors['email']) ? '<div class="invalid-feedback">' . $errors['email'] . '</div>' : '');
        ?>
    </div>

    <div class="form-group">
        <label for="password">Heslo:</label>
        <input type="password" name="password" id="password" required class="form-control <?php echo (!empty($errors['password']) ? 'is-invalid' : ''); ?>" />
        <?php
        // Zobrazení chybové zprávy, pokud existuje
        echo (!empty($errors['password']) ? '<div class="invalid-feedback">' . $errors['password'] . '</div>' : '');
        ?>
    </div>

    <div class="form-group">
        <label for="password2">Potvrzení hesla:</label>
        <input type="password" name="password2" id="password2" required class="form-control <?php echo (!empty($errors['password2']) ? 'is-invalid' : ''); ?>" />
        <?php
        // Zobrazení chybové zprávy, pokud existuje
        echo (!empty($errors['password2']) ? '<div class="invalid-feedback">' . $errors['password2'] . '</div>' : '');
        ?>
    </div>

    <button type="submit" class="btn btn-primary">Registrovat se</button>
    <a href="login.php" class="btn btn-light">Přihlásit se</a>
    <a href="index.php" class="btn btn-light">Zrušit</a>
</form>

<?php
// Načtení patičky stránky
include 'inc/footer.php';
?>
