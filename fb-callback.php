<?php

// Načteme připojení k databázi a inicializujeme session
require_once 'inc/user.php';

// Načteme knihovnu pro Facebook SDK
require_once 'inc/facebook.php';

// Pomocník pro přesměrování přihlášení pomocí Facebooku
$fbHelper = $fb->getRedirectLoginHelper();

try {
   // Pokusíme se získat přístupový token z URL po přihlášení pomocí Facebooku
   $accessToken = $fbHelper->getAccessToken();
} catch (Exception $e) {
    // Pokud dojde k chybě při získávání tokenu
    echo 'Přihlášení pomocí Facebooku selhalo. Chyba: ' . $e->getMessage();
    exit();  // Ukončíme skript
}

// Pokud není přístupový token k dispozici, přihlášení selhalo
if (!$accessToken){
    exit('Přihlášení pomocí Facebooku se nezdařilo. Zkuste to znovu.');
}

// Získáme OAuth2 klienta pro ověření tokenu
$oAuth2Client = $fb->getOAuth2Client();

// Získáme metadata tokenu, včetně uživatelského ID
$accessTokenMetadata = $oAuth2Client->debugToken($accessToken);
$fbUserId = $accessTokenMetadata->getUserId();

// Pokusíme se získat základní informace o uživatelském profilu
$response = $fb->get('/me?fields=name,email', $accessToken);
$graphUser = $response->getGraphUser();

// Získáme email a jméno uživatele z Facebooku
$fbUserEmail = $graphUser->getEmail();
$fbUserName = $graphUser->getName();

// Zkontrolujeme, zda uživatel s tímto Facebook ID už existuje v naší databázi
$query = $db->prepare('SELECT * FROM users WHERE facebook_id=:facebookId LIMIT 1;');
$query->execute([ ':facebookId' => $fbUserId ]);

// Pokud uživatel existuje, uložíme informace do proměnné $user
if ($query->rowCount() > 0){
    $user = $query->fetch(PDO::FETCH_ASSOC);

} else {
    // Pokud uživatel s tímto Facebook ID neexistuje, zkontrolujeme, zda existuje uživatel s tímto emailem
    $query = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $query->execute([ ':email' => $fbUserEmail ]);

    // Pokud uživatel s tímto emailem existuje, přiřadíme mu Facebook ID
    if ($query->rowCount() > 0){
        $user = $query->fetch(PDO::FETCH_ASSOC);

        // Aktualizujeme Facebook ID u existujícího uživatele
        $updateQuery = $db->prepare('UPDATE users SET facebook_id=:facebookId WHERE user_id=:id LIMIT 1;');
        $updateQuery->execute([
            ':facebookId' => $fbUserId,
            ':id' => $user['user_id']
        ]);

    } else {
        // Pokud uživatel s tímto emailem neexistuje, vytvoříme nového uživatele
        $insertQuery = $db->prepare('INSERT INTO users (name,email, facebook_id) VALUES (:name, :email, :facebookId);');
        $insertQuery->execute([
            ':name' => $fbUserName,
            ':email' => $fbUserEmail,
            ':facebookId' => $fbUserId
        ]);

        // Po vložení nového uživatele, znovu načteme jeho data podle Facebook ID
        $query = $db->prepare('SELECT * FROM users WHERE facebook_id=:facebookId LIMIT 1;');
        $query->execute([ ':facebookId' => $fbUserId ]);
        $user = $query->fetch(PDO::FETCH_ASSOC);
    }
}

// Pokud byl uživatel nalezen, nastavíme session proměnné pro identifikaci uživatele
if (!empty($user)) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
}

// Přesměrujeme uživatele na domovskou stránku
header('Location: index.php');