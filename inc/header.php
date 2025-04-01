<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <title>Not-Wanted</title>
    <link rel="stylesheet" type="text/css" href="inc/styles.css">
</head>
<body class="container">

<header class="jumbotron">
    <div class="container text-center">
    <a href="index.php"><h1 class="display-1 text-black py-4 px-2" >Not-Wanted</h1></a>
    </div>
</header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <form method="get" class="d-flex">
                <input type="hidden" name="kategorie" value="<?php echo htmlspecialchars($_GET['kategorie']);?>">
                <input class="form-control me-2" type="search" placeholder="Vyhledat" aria-label="Vyhledat" name="search" id="search">
                <button class="btn btn-primary" type="submit">Vyhledat</button>
            </form>
            <div class="collapse navbar-collapse" id="navbarRightAlignExample">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php
        if (!empty($_SESSION['user_id'])){
            echo '<li class="nav-item text-light me-2 my-auto"><a href="profile.php?id='.htmlspecialchars($_SESSION['user_id']).'" class="text-light"><strong>'.htmlspecialchars($_SESSION['user_name']).'</strong></li>';
            echo '<li class="nav-item text-light me-2"><a class="btn btn-primary" href="buyings.php?id='.htmlspecialchars($_SESSION['user_id']).'">Moje nákupy</a></li>';
            echo '<li class="nav-item text-light me-2"><a class="btn btn-primary" href="profile.php?id='.htmlspecialchars($_SESSION['user_id']).'">Moje inzeráty</a></li>';
            echo '<li class="nav-item text-light "><a class="btn btn-primary" href="logout.php">odhlásit se</a></li>';
        }else{
            echo '<li class="nav-item text-light"><a class="btn btn-primary" href="login.php">přihlásit se</a></li>';
        }
        ?>
                </ul>
            </div>
        </div>
    </nav>


<main class="">