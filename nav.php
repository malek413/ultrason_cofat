
<?php
// nav.php

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">


<style>
/* Reset simple */
* {
    box-sizing: border-box;
}

nav.navbar {
    position: sticky;     /* 👈 REND LA BARRE FIXÉE EN HAUT */
    top: 0;               /* 👈 POSITION EN HAUT DE LA PAGE */
    z-index: 1000;        /* 👈 POUR RESTER AU-DESSUS DU CONTENU */

    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #1e40af; /* أزرق غامق */
    padding: 10px 25px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    flex-wrap: wrap;
}

nav.navbar .logo {
    height: 50px;
    object-fit: contain;
    cursor: pointer;
}

nav.navbar .nav-links {
    display: flex;
    gap: 25px;
    font-weight: 600;
    font-size: 1rem;
    flex-wrap: wrap;
}

nav.navbar .nav-links a {
    color: #f3f4f6; /* رمادي فاتح */
    text-decoration: none;
    transition: color 0.3s ease;
    padding: 6px 0;
    border-bottom: 2px solid transparent;
}

nav.navbar .nav-links a:hover,
nav.navbar .nav-links a:focus {
    color: #93c5fd; /* أزرق فاتح */
    border-bottom: 2px solid #93c5fd;
}

nav.navbar form.logout-form {
    margin: 0;
}

nav.navbar form.logout-form button {
    background-color: #ef4444; /* أحمر */
    border: none;
    color: white;
    padding: 8px 16px;
    font-weight: 700;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-size: 1rem;
}

nav.navbar form.logout-form button:hover,
nav.navbar form.logout-form button:focus {
    background-color: #dc2626; /* أحمر داكن */
    outline: none;
}

/* Responsive */
@media (max-width: 650px) {
    nav.navbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    nav.navbar .nav-links {
        gap: 15px;
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<nav class="navbar" role="navigation" aria-label="Main navigation">
    <a href="dashboard.php" aria-label="Accueil">
        <img src="https://www.vinchin.com/res/img/upload/image/20220623/1655976375549896.png" alt="Logo société" class="logo" />
    </a>
    <div class="nav-links">
        <a href="dashboard.php">Suivi-Machines</a>
        <a href="details.php">Détails-Machines</a>
        <a href="historique.php">Historique-Machines</a>
    </div>
    <form method="post" class="logout-form" aria-label="Se déconnecter">
        <button type="submit" name="logout" aria-describedby="logout-desc">🔓 Se déconnecter</button>
        <span id="logout-desc" class="sr-only">Déconnexion de la session utilisateur</span>
    </form>
</nav>

<style>
/* Screen reader only text */
.sr-only {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}
</style>
