<?php
session_start();

// Détermine où renvoyer l'utilisateur
if (isset($_SESSION['user'])) {
    $link = 'dashboard.php';
    $text = 'Retour au Dashboard';
} else {
    $link = 'index.php';
    $text = 'Retour à la connexion';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo_64.png">
    <link rel="stylesheet" href="style/dashboard.css?v=final2">
    <title>Page introuvable - 404</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            height: 100vh;
            overflow: hidden;
        }

        .error-container {
            background: var(--card-bg);
            padding: 3rem;
            border-radius: 24px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 60px var(--shadow-soft);
            max-width: 500px;
            width: 90%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .error-code {
            font-size: 6rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            line-height: 1;
        }

        .error-title {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin: 1rem 0 0.5rem 0;
            font-weight: 700;
        }

        .error-desc {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .icon-404 svg {
            width: 120px;
            height: 120px;
            color: var(--text-lighter);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: white;
            padding: 12px 25px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px var(--shadow-soft);
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--shadow-soft);
        }
    </style>
</head>
<body>

<div class="error-container">
    <div class="icon-404">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h1 class="error-code">404</h1>
    <h2 class="error-title">Oups ! Page introuvable</h2>
    <p class="error-desc">La page que vous recherchez semble avoir disparu ou n'a jamais existé.</p>

    <a href="<?= $link ?>" class="btn-home">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:20px; height:20px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <?= $text ?>
    </a>
</div>

<script>
    // Appliquer le thème immédiatement pour éviter le flash blanc
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark' || (!currentTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.body.classList.add('dark-mode');
    }
</script>

</body>
</html>