<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/profile_guard.php';
require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];

$id = trim((string)($_GET['id'] ?? 'checkin'));
if ($id === '' || !preg_match('/^[a-z0-9_-]{1,50}$/i', $id)) {
    header('Location: missioni.php');
    exit;
}

$stmt = $mysqli->prepare("
    SELECT id, title, xp, requires_checkin, checkin_code
    FROM missions
    WHERE id = ? AND active = 1
    LIMIT 1
");
$stmt->bind_param("s", $id);
$stmt->execute();
$mission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mission || (int)$mission['requires_checkin'] !== 1) {
    header('Location: missione_dettaglio.php?id=' . urlencode($id));
    exit;
}

$title = (string)$mission['title'];
$xp = (int)$mission['xp'];
$expectedCode = (string)($mission['checkin_code'] ?? '');

$stmt = $mysqli->prepare("
    SELECT status
    FROM user_missions
    WHERE user_id = ? AND mission_id = ?
    LIMIT 1
");
$stmt->bind_param("is", $userId, $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$userStatus = (string)($row['status'] ?? '');
$alreadyCompleted = ($userStatus === 'completed');

$errors = [];
$successMsg = "";

if (!empty($_GET['ok'])) {
    $successMsg = "Check-in confermato! +{$xp} XP.";
}

if (!$alreadyCompleted) {
    $stmt = $mysqli->prepare("
    INSERT INTO user_missions (user_id, mission_id, status)
    VALUES (?, ?, 'active')
    ON DUPLICATE KEY UPDATE status = IF(status='completed','completed','active')
  ");
    $stmt->bind_param("is", $userId, $id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyCompleted) {
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));

    if ($code === '') {
        $errors[] = "Inserisci un codice.";
    } elseif ($expectedCode === '') {
        $errors[] = "Codice non configurato per questa missione.";
    } elseif (!hash_equals(strtoupper($expectedCode), $code)) {
        $errors[] = "Codice non valido. Riprova.";
    } else {
        $stmt = $mysqli->prepare("
      INSERT INTO user_missions (user_id, mission_id, status, completed_at)
      VALUES (?, ?, 'completed', NOW())
      ON DUPLICATE KEY UPDATE status='completed'
    ");
        $stmt->bind_param("is", $userId, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: checkin.php?id=' . urlencode($id) . '&ok=1');
        exit;
    }
}

if ($alreadyCompleted && $successMsg === "") {
    $successMsg = "Check-in già completato.";
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Check-in UniBoQuest con codice fallback." />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="css/main.css" />
    <link rel="stylesheet" href="css/header.css" />

    <title>UniBoQuest - Check-in</title>
</head>

<body class="manual-bg checkin-page">
    <a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

    <header class="header-glass">
        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand font-8bit" href="dashboard.php" aria-label="UniBoQuest Dashboard">UniBoQuest</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                    aria-controls="nav" aria-expanded="false" aria-label="Apri menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="nav">
                    <ul class="navbar-nav mx-auto mb-2 mb-md-0 ubq-nav-center">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">DASHBOARD</a></li>
                        <li class="nav-item"><a class="nav-link active" href="missioni.php" aria-current="page">MISSIONI</a></li>
                        <li class="nav-item"><a class="nav-link" href="profilo.php">PROFILO</a></li>
                    </ul>

                    <div class="d-flex gap-2 ubq-nav-right">
                        <a class="btn-pixel-yellow" href="logout.php">Esci</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="contenuto" class="container mt-4">
        <div class="checkin-card">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h1 class="checkin-title font-8bit h2" style="font-size: 1.05rem;">
                        <?php echo h(mb_strtoupper($title, 'UTF-8')); ?>
                    </h1>
                    <p class="checkin-subtitle mb-0">
                        Inserisci il codice evento per confermare la presenza.
                    </p>
                </div>

                <span class="checkin-badge">
                    <?php echo $alreadyCompleted ? "Completato" : "Non completato"; ?>
                </span>
            </div>

            <hr class="my-4" style="border-color: rgba(255,255,255,.15);" />

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <section class="checkin-panel">
                        <h2 class="font-8bit h3" style="font-size: 0.9rem; color: #ffca2c; background-color: #000; padding: 5px 10px; display: inline-block; border-radius: 4px;">
                            SCANSIONA QR
                        </h2>

                        <div class="checkin-qr mt-2">
                            <div class="text-center p-3">
                                <p class="h6 mb-2 text-white">QR (placeholder)</p>
                                <p class="mb-2">Nel prototipo non usiamo la camera.</p>
                                <p class="mb-0 small opacity-75">
                                    In PHP: QR &rarr; token evento &rarr; validazione server.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-12 col-lg-6">
                    <section class="checkin-panel">
                        <h2 class="font-8bit h3" style="font-size: 0.9rem; color: #ffca2c; background-color: #000; padding: 5px 10px; display: inline-block; border-radius: 4px;">
                            CODICE FALLBACK
                        </h2>

                        <form id="checkinForm" action="checkin.php?id=<?php echo urlencode($id); ?>" method="post">
                            <label for="code" class="form-label mt-2">Inserisci codice</label>
                            <input type="text" id="code" name="code" class="form-control p-3"
                                placeholder="Codice fornito dall'organizzatore" autocomplete="off" required
                                <?php echo $alreadyCompleted ? 'disabled' : ''; ?> />

                            <div class="checkin-help mt-2">
                                Inserisci il codice mostrato durante l’evento.
                            </div>

                            <div class="checkin-actions mt-3 d-flex gap-2">
                                <a class="btn-pixel" href="missioni.php">Torna alle missioni</a>
                                <button type="submit" class="btn-pixel-yellow" <?php echo $alreadyCompleted ? 'disabled' : ''; ?>>
                                    Conferma
                                </button>
                            </div>

                            <?php if (!empty($errors)): ?>
                                <div class="checkin-msg err mt-3" role="alert" aria-live="polite">
                                    <?php foreach ($errors as $e): ?>
                                        <div><?php echo h($e); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($successMsg !== ''): ?>
                                <div class="checkin-msg ok mt-3" role="status" aria-live="polite">
                                    <?php echo h($successMsg); ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    </section>

                    <section class="checkin-panel mt-3">
                        <h2 class="font-8bit h3" style="font-size: 0.9rem; color: #ffca2c; background-color: #000; padding: 5px 10px; display: inline-block; border-radius: 4px;">
                            RICOMPENSA
                        </h2>
                        <p class="checkin-help mb-0">
                            Completando il check-in ottieni <strong>+<?php echo (int)$xp; ?> XP</strong>.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-ubq mt-5">
        <div class="container py-4">
            <div class="row gy-3 align-items-start">
                <div class="col-md-4">
                    <h3 class="fw-bold mb-2 h5 text-white">UniBoQuest</h3>
                    <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
                    <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Cesena.</p>
                </div>

                <div class="col-md-3">
                    <h4 class="fw-bold mb-2 h6 text-white">Navigazione</h4>
                    <ul class="list-unstyled small mb-0">
                        <li><a href="gioco.html" class="footer-link text-white text-decoration-none">Il Gioco</a></li>
                        <li><a href="faq.html" class="footer-link text-white text-decoration-none">FAQ</a></li>
                    </ul>
                </div>

                <div class="col-md-3">
                    <h4 class="fw-bold mb-2 h6 text-white">Seguici</h4>
                    <div class="footer-social d-flex gap-3">
                        <a href="#" class="text-white fs-5" aria-label="Instagram"><span class="bi bi-instagram" aria-hidden="true"></span></a>
                        <a href="#" class="text-white fs-5" aria-label="Discord"><span class="bi bi-discord" aria-hidden="true"></span></a>
                        <a href="https://github.com/LolloMarche25/uniboquest.git" class="text-white fs-5" aria-label="GitHub"><span class="bi bi-github" aria-hidden="true"></span></a>
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