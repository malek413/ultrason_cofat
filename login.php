<?php
session_start();

// Définir mot de passe ici (à modifier selon besoin)
$motDePasseCorrect = "cofat123";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $motDePasseSaisi = $_POST['password'] ?? '';

    if ($motDePasseSaisi === $motDePasseCorrect) {
        $_SESSION['connecte'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $erreur = "❌ Mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">
    <title>Connexion Cofat</title>
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-box {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.8s ease-in-out;
        }
        .login-box img {
            width: 80px;
            margin-bottom: 20px;
        }
        .login-box h2 {
            margin-bottom: 25px;
            font-size: 24px;
            color: #0ea5e9;
        }
        input[type="password"] {
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 1rem;
        }
        button {
            background-color: #0ea5e9;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0284c7;
        }
        .error {
            color: #ef4444;
            margin-top: 10px;
            font-size: 0.95rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="https://www.vinchin.com/res/img/upload/image/20220623/1655976375549896.png" alt="Logo Cofat">
        <h2>Connexion</h2>
        <form method="post">
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
            <?php if (!empty($erreur)) echo "<div class='error'>$erreur</div>"; ?>
        </form>
    </div>
</body>
</html>
