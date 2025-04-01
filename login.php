<?php

// Načteme připojení k databázi a funkce pro uživatele
require_once 'inc/user.php';

// Načteme knihovnu pro Facebook přihlášení
require_once 'inc/facebook.php';

// Pokud je uživatel již přihlášen, přesměrujeme ho na hlavní stránku
if (!empty($_SESSION['user_id'])){
    header('Location: index.php');
    exit();
}

// Nastavení proměnné pro chyby při přihlašování
$errors = false;

// Pokud byl formulář odeslán
if (!empty($_POST)){

    // Načteme uživatele z databáze podle zadaného e-mailu
    $userQuery = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $userQuery->execute([
        ':email' => trim($_POST['email']) // Oříznutí a zadání e-mailu do dotazu
    ]);

    // Pokud je uživatel v databázi
    if ($user = $userQuery->fetch(PDO::FETCH_ASSOC)){

        // Ověříme, zda zadané heslo odpovídá tomu v databázi
        if (password_verify($_POST['password'], $user['password'])){
            // Heslo je platné, přihlásíme uživatele
            $_SESSION['user_id'] = $user['user_id']; // Uložíme ID uživatele do session
            $_SESSION['user_name'] = $user['name']; // Uložíme jméno uživatele do session
            header('Location: index.php'); // Přesměrujeme na hlavní stránku
            exit();
        } else {
            // Pokud heslo neodpovídá, nastavíme chybu
            $errors = true;
        }

    } else {
        // Pokud uživatel není nalezen v databázi, nastavíme chybu
        $errors = true;
    }
}

// Inicializace Facebook přihlášení
$fbHelper = $fb->getRedirectLoginHelper();

// Povolení oprávnění pro přihlášení přes Facebook (email)
$permissions = ['email'];

// Callback URL pro Facebook přihlášení
$callbackUrl = htmlspecialchars('https://eso.vse.cz/~sinj04/market_semestralka/fb-callback.php');

// Generujeme URL pro přihlášení přes Facebook
$fbLoginUrl = $fbHelper->getLoginUrl($callbackUrl, $permissions);

// Nastavení názvu stránky
$pageTitle = 'Přihlášení uživatele';

// Vložení hlavičky stránky
include 'inc/header.php';
?>

<!-- Zobrazíme formulář pro přihlášení -->
<h2>Přihlášení uživatele</h2>

<form method="post">
    <div class="form-group">
        <label for="email">E-mail:</label>
        <!-- Vstupní pole pro e-mail -->
        <input type="email" name="email" id="email" required class="form-control <?php echo ($errors ? 'is-invalid' : ''); ?>" value="<?php echo htmlspecialchars(@$_POST['email']) ?>"/>
        <?php
        // Zobrazíme chybu, pokud je přihlášení neplatné
        echo ($errors ? '<div class="invalid-feedback">Neplatná kombinace přihlašovacího e-mailu a hesla.</div>' : '');
        ?>
    </div>
    <div class="form-group">
        <label for="password">Heslo:</label>
        <!-- Vstupní pole pro heslo -->
        <input type="password" name="password" id="password" required class="form-control <?php echo ($errors ? 'is-invalid' : ''); ?>" />
    </div>
    <!-- Tlačítko pro přihlášení -->
    <button type="submit" class="btn btn-primary">přihlásit se</button>
    <?php
    // Tlačítko pro přihlášení přes Facebook
    echo '<a href="'.$fbLoginUrl.'" class="btn btn-primary">Přihlášení pomocí Facebooku</a>';
    ?>
    <!-- Odkaz pro registraci nového uživatele -->
    <a href="registration.php" class="btn btn-light">registrovat se</a>
    <!-- Odkaz pro zrušení přihlášení -->
    <a href="index.php" class="btn btn-light">zrušit</a>
</form>

<?php
// Vložení patičky stránky
include 'inc/footer.php';
?>
