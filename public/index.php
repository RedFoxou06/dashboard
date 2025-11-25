<?php
session_start();
require '../conf/data.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $users = get_json(USER_FILE);

    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $username;
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Identifiant ou mot de passe incorrect.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo_64.png">
    <title>Connexion - Task Manager</title>
    <style>
        :root {
            --primary-blue: #4A90E2;
            --light-blue: #E3F2FD;
            --soft-blue: #64B5F6;
            --bg-gradient-start: #E8F4F8;
            --bg-gradient-end: #FFFFFF;
            --text-dark: #2D2D2D;
            --text-light: #6B6B6B;
            --shadow-soft: rgba(74, 144, 226, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Décoration d'arrière-plan avec des cercles */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, var(--light-blue) 0%, transparent 70%);
            opacity: 0.4;
        }

        body::before {
            width: 400px;
            height: 400px;
            top: -150px;
            right: -100px;
        }

        body::after {
            width: 350px;
            height: 350px;
            bottom: -100px;
            left: -80px;
        }

        .login-container {
            position: relative;
            z-index: 1;
            background: white;
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 60px var(--shadow-soft);
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(10px);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--soft-blue) 100%);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 24px var(--shadow-soft);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            filter: brightness(0) invert(1);
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .error-message {
            background: linear-gradient(135deg, #FFE5E5 0%, #FFD0D0 100%);
            color: #D32F2F;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid #D32F2F;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #F0F0F0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
            background: #FAFAFA;
        }

        input:focus {
            border-color: var(--primary-blue);
            background: white;
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: var(--text-light);
        }

        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--soft-blue) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px var(--shadow-soft);
            margin-top: 0.5rem;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--shadow-soft);
        }

        button:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 2rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <div class="logo-circle">
            <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>
        <h1>Bienvenue</h1>
        <p class="subtitle">Connectez-vous pour accéder au dashboard</p>
    </div>

    <?php if($error): ?>
        <div class="error-message">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Identifiant</label>
            <div class="input-wrapper">
                <span class="input-icon">👤</span>
                <input type="text" id="username" name="username" placeholder="Votre identifiant" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-wrapper">
                <span class="input-icon">🔒</span>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit">Se connecter</button>
    </form>

    <p class="footer-text">© Nathan Fraysse</p>
</div>
</body>
</html>