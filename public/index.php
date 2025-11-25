<?php
session_start();
require __DIR__ . '/../conf/data.php';

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
    <title>Connexion - Dashboard</title>
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary-blue: #4A90E2;
            --light-blue: #E3F2FD;
            --soft-blue: #64B5F6;
            --bg-gradient-start: #E8F4F8;
            --bg-gradient-end: #FFFFFF;
            --text-dark: #2D2D2D;
            --text-light: #6B6B6B;
            --shadow-soft: rgba(74, 144, 226, 0.2);
            --card-bg: #FFFFFF;
            --input-bg: #FAFAFA;
            --border-color: #F0F0F0;
            --icon-color: #6B6B6B;
        }

        /* --- DARK MODE VARIABLES --- */
        body.dark-mode {
            --bg-gradient-start: #0f172a;
            --bg-gradient-end: #1e293b;
            --text-dark: #f1f5f9;
            --text-light: #94a3b8;
            --card-bg: #1e293b;
            --input-bg: #0f172a;
            --border-color: #334155;
            --shadow-soft: rgba(0, 0, 0, 0.5);
            --light-blue: #334155;
            --icon-color: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            transition: background 0.3s ease;
        }

        /* Décoration d'arrière-plan */
        body::before, body::after {
            content: ''; position: absolute; border-radius: 50%;
            background: radial-gradient(circle, var(--light-blue) 0%, transparent 70%); opacity: 0.4;
            transition: background 0.3s ease;
        }
        body::before { width: 400px; height: 400px; top: -150px; right: -100px; }
        body::after { width: 350px; height: 350px; bottom: -100px; left: -80px; }

        .theme-switch {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 10;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px var(--shadow-soft);
            cursor: pointer;
            color: var(--text-light);
            transition: all 0.3s ease;
            padding: 0;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-switch:hover { color: var(--primary-blue); transform: scale(1.1); }
        .theme-switch svg { width: 24px; height: 24px; }
        .login-container {
            position: relative; z-index: 1;
            background: var(--card-bg);
            padding: 3rem 2.5rem;
            border-radius: 24px; box-shadow: 0 20px 60px var(--shadow-soft);
            width: 100%; max-width: 420px; backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .logo-section { text-align: center; margin-bottom: 2rem; }
        .logo-circle {
            width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-blue) 0%, var(--soft-blue) 100%);
            border-radius: 50%; margin: 0 auto 1.5rem; display: flex; justify-content: center; align-items: center;
            box-shadow: 0 8px 24px var(--shadow-soft); animation: float 3s ease-in-out infinite;
        }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        .logo-icon { width: 40px; height: 40px; filter: brightness(0) invert(1); }

        h1 { font-size: 1.8rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; transition: color 0.3s; }
        .subtitle { color: var(--text-light); font-size: 0.95rem; margin-bottom: 2rem; transition: color 0.3s; }

        .error-message {
            background: linear-gradient(135deg, #FFE5E5 0%, #FFD0D0 100%); color: #D32F2F;
            padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem;
            border-left: 4px solid #D32F2F; animation: shake 0.3s ease-in-out;
        }
        body.dark-mode .error-message { background: #450a0a; color: #fca5a5; border-left-color: #ef4444; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        .form-group { margin-bottom: 1.5rem; }
        label { display: block; color: var(--text-dark); font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; transition: color 0.3s; }

        .input-wrapper { position: relative; }

        input {
            width: 100%; padding: 1rem 3rem 1rem 3rem;
            border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem;
            transition: all 0.3s ease; outline: none; background: var(--input-bg); color: var(--text-dark);
        }
        input:focus { border-color: var(--primary-blue); background: var(--card-bg); box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.15); }

        .input-icon, .toggle-password {
            position: absolute; top: 50%; transform: translateY(-50%);
            color: var(--text-light); display: flex; align-items: center;
            transition: color 0.3s ease;
        }
        .input-icon { left: 1rem; pointer-events: none; }
        .toggle-password { right: 1rem; cursor: pointer; }
        .toggle-password:hover { color: var(--primary-blue); }
        .input-icon svg, .toggle-password svg { width: 20px; height: 20px; }

        button.btn-submit {
            width: 100%; padding: 1rem; background: linear-gradient(135deg, var(--primary-blue) 0%, var(--soft-blue) 100%);
            color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px var(--shadow-soft); margin-top: 0.5rem;
        }
        button.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--shadow-soft); }
        button.btn-submit:active { transform: translateY(0); }

        .footer-text { text-align: center; color: var(--text-light); font-size: 0.85rem; margin-top: 2rem; transition: color 0.3s; }

        @media (max-width: 480px) { .login-container { padding: 2rem 1.5rem; } h1 { font-size: 1.5rem; } }
    </style>
</head>
<body>

<button class="theme-switch" onclick="toggleTheme()" title="Changer le thème">
    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
</button>

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
        <div class="error-message">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Identifiant</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <input type="text" id="username" name="username" placeholder="Votre identifiant" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>

                <input type="password" id="password" name="password" placeholder="••••••••" required>

                <span class="toggle-password" onclick="togglePassword()">
                    <svg id="icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </span>
            </div>
        </div>

        <button type="submit" class="btn-submit">Se connecter</button>
    </form>

    <p class="footer-text">© Nathan Fraysse</p>
</div>

<script>
    // --- GESTION THEME SOMBRE ---
    const iconMoon = document.getElementById('icon-moon');
    const iconSun = document.getElementById('icon-sun');

    // 1. Détection au chargement
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark' || (!currentTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.body.classList.add('dark-mode');
        iconMoon.style.display = 'none';
        iconSun.style.display = 'block';
    }

    // 2. Fonction Switch
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');

        let theme = 'light';
        if (document.body.classList.contains('dark-mode')) {
            theme = 'dark';
            iconMoon.style.display = 'none';
            iconSun.style.display = 'block';
        } else {
            iconMoon.style.display = 'block';
            iconSun.style.display = 'none';
        }
        localStorage.setItem('theme', theme);
    }

    // --- GESTION MOT DE PASSE ---
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const iconContainer = document.querySelector('.toggle-password');

        const svgShow = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>`;

        const svgHide = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            </svg>`;

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            iconContainer.innerHTML = svgHide;
        } else {
            passwordInput.type = 'password';
            iconContainer.innerHTML = svgShow;
        }
    }
</script>
</body>
</html>