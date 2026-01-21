<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/config/db.php'; // deve creare $mysqli (mysqli)

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $password_confirm = (string)($_POST['password_confirm'] ?? '');

    // Validazioni base
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email non valida.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password troppo corta (min 8 caratteri).";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Le password non coincidono.";
    }

    // Se tutto ok, controlla duplicati e inserisci
    if (!$errors) {
        // Controllo email già presente (senza get_result per compatibilità)
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $errors[] = "Esiste già un account con questa email.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hash);

            if ($stmt->execute()) {
                $userId = (int)$stmt->insert_id;
                $stmt->close();

                // Login automatico post-registrazione
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;

                // Anti session fixation
                session_regenerate_id(true);

                // Step successivo: onboarding profilo
                header('Location: edit_profile.php');
                exit;
            }

            $stmt->close();
            $errors[] = "Errore durante la registrazione. Riprova.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Crea il tuo account UniBoQuest e inizia l'avventura." />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="css/main.css">

        <title>UniBoQuest - Registrazione</title>
    </head>

    <body class="manual-bg auth-page">
        <a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

        <header class="header-glass">
            <nav class="navbar navbar-expand-md navbar-dark">
                <div class="container-fluid">
                    <a class="navbar-brand font-8bit" href="index.html">UniBoQuest</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Apri menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="nav">
                        <ul class="navbar-nav mx-auto mb-2 mb-md-0 ubq-nav-center">
                            <li class="nav-item"><a class="nav-link" href="index.html">HOME</a></li>
                            <li class="nav-item"><a class="nav-link" href="gioco.html">IL GIOCO</a></li>
                            <li class="nav-item"><a class="nav-link" href="faq.html">FAQ</a></li>
                        </ul>
                        <div class="d-flex gap-2 ubq-nav-right">
                            <a class="btn-pixel-yellow" href="login.php">Accedi</a>
                            <a class="btn-pixel" href="registrazione.php" aria-current="page">Registrati</a>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main id="contenuto" class="container">
            <div class="auth-card">
                <h2 class="mb-4 font-8bit text-center text-white" style="font-size: 1.2rem;">NUOVO PLAYER</h2>

                <?php if (!empty($errors)): ?>
                    <div class="checkin-msg err" style="margin-bottom: 1rem;">
                        <?php foreach ($errors as $e): ?>
                            <div><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="registrazione.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Ateneo</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control p-3"
                            placeholder="nome.cognome@studio.unibo.it"
                            autocomplete="email"
                            required
                            value="<?php echo htmlspecialchars($email); ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control p-3"
                            autocomplete="new-password"
                            required
                            minlength="8"
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Conferma password</label>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-control p-3"
                            required
                            minlength="8"
                        >
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn-pixel-yellow py-3 font-8bit" style="font-size: 0.9rem;">
                            CREA ACCOUNT
                        </button>
                    </div>
                </form>

                <div class="auth-footer-link">
                    Hai già un account? <a href="login.php">Esegui il Login</a>
                </div>
            </div>
        </main>

        <footer class="footer-ubq mt-5">
            <div class="container py-4">
                <div class="row gy-3 align-items-start">
                    <div class="col-md-4">
                        <h5 class="fw-bold mb-2 text-white">UniBoQuest</h5>
                        <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
                        <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Bologna.</p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="fw-bold mb-2 text-white">Navigazione</h6>
                        <ul class="list-unstyled small mb-0">
                            <li><a href="gioco.html" class="footer-link text-white text-decoration-none">Il Gioco</a></li>
                            <li><a href="faq.html" class="footer-link text-white text-decoration-none">FAQ</a></li>
                            <li><a href="chi-siamo.html" class="footer-link text-white text-decoration-none">Chi siamo</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6 class="fw-bold mb-2 text-white">Seguici</h6>
                        <div class="footer-social d-flex gap-3">
                            <a href="#" class="text-white fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="text-white fs-5" aria-label="Discord"><i class="bi bi-discord"></i></a>
                            <a href="https://github.com/LolloMarche25/uniboquest.git" class="text-white fs-5" aria-label="GitHub"><i class="bi bi-github"></i></a>
                        </div>
                    </div>
                </div>
                 <div class="footer-bottom border-top border-light-subtle mt-4 pt-3 d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
                    <span>&copy; 2026 UniBoQuest – Prototipo Alpha.</span>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
