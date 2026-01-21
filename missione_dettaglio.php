<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/profile_guard.php';
require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '' || !preg_match('/^[a-z0-9_-]{1,50}$/i', $id)) {
  header('Location: missioni.php');
  exit;
}

// Carica missione
$stmt = $mysqli->prepare("
  SELECT id, title, subtitle, description, category, difficulty, time_label, xp,
         goal, steps_json, note,
         requires_checkin
  FROM missions
  WHERE id = ? AND active = 1
  LIMIT 1
");
$stmt->bind_param("s", $id);
$stmt->execute();
$mission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mission) {
  header('Location: missioni.php');
  exit;
}

$title = (string)$mission['title'];
$subtitle = (string)($mission['subtitle'] ?? '');
$desc = (string)($mission['description'] ?? '');
$cat = (string)($mission['category'] ?? '');
$diff = (string)($mission['difficulty'] ?? '');
$timeLabel = (string)($mission['time_label'] ?? '');
$xp = (int)($mission['xp'] ?? 0);
$goal = (string)($mission['goal'] ?? '');
$note = (string)($mission['note'] ?? '');
$requiresCheckin = ((int)($mission['requires_checkin'] ?? 0) === 1);

// Steps: JSON array di stringhe
$steps = [];
$rawSteps = (string)($mission['steps_json'] ?? '');
if ($rawSteps !== '') {
  $decoded = json_decode($rawSteps, true);
  if (is_array($decoded)) {
    foreach ($decoded as $s) {
      if (is_string($s) && trim($s) !== '') $steps[] = trim($s);
    }
  }
}

// Stato utente
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

$status = (string)($row['status'] ?? ''); // '' | active | completed

$errors = [];
$success = "";

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  // ricarico stato "fresh" prima di mutare (per sicurezza minima)
  $stmt = $mysqli->prepare("SELECT status FROM user_missions WHERE user_id = ? AND mission_id = ? LIMIT 1");
  $stmt->bind_param("is", $userId, $id);
  $stmt->execute();
  $fresh = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $status = (string)($fresh['status'] ?? '');

  if ($action === 'join') {
    if ($status === 'completed') {
      $errors[] = "Missione già completata.";
    } else {
      $stmt = $mysqli->prepare("
        INSERT INTO user_missions (user_id, mission_id, status)
        VALUES (?, ?, 'active')
        ON DUPLICATE KEY UPDATE status = IF(status='completed','completed','active'), completed_at=NULL
      ");
      $stmt->bind_param("is", $userId, $id);
      $stmt->execute();
      $stmt->close();
      $status = 'active';
      $success = "Sei iscritto alla missione.";
    }
  }

  if ($action === 'abandon') {
    if ($status === 'completed') {
      $errors[] = "Non puoi abbandonare: missione già completata.";
    } else {
      $stmt = $mysqli->prepare("DELETE FROM user_missions WHERE user_id = ? AND mission_id = ?");
      $stmt->bind_param("is", $userId, $id);
      $stmt->execute();
      $stmt->close();
      $status = '';
      $success = "Hai abbandonato la missione.";
    }
  }

  if ($action === 'complete') {
    if ($requiresCheckin) {
      $errors[] = "Questa missione si completa dalla pagina Check-in.";
    } elseif ($status !== 'active') {
      $errors[] = "Per completare devi prima partecipare.";
    } else {
      $stmt = $mysqli->prepare("
        INSERT INTO user_missions (user_id, mission_id, status, completed_at)
        VALUES (?, ?, 'completed', NOW())
        ON DUPLICATE KEY UPDATE status='completed', completed_at=NOW()
      ");
      $stmt->bind_param("is", $userId, $id);
      $stmt->execute();
      $stmt->close();
      $status = 'completed';
      $success = "Missione completata! +{$xp} XP.";
    }
  }

  // Evita resubmit su refresh
  header('Location: missione_dettaglio.php?id=' . urlencode($id) . ($success !== '' ? '&ok=1' : ''));
  exit;
}

// Se arriviamo da redirect ok
if (!empty($_GET['ok']) && $success === '' && empty($errors)) {
  // messaggio neutro
  // (se vuoi, puoi ricaricare stato e mostrare un testo più specifico)
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function statusLabel(string $status): string {
  if ($status === 'completed') return 'Completata';
  if ($status === 'active') return 'In corso';
  return 'Disponibile';
}
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Dettaglio missione UniBoQuest." />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="css/main.css" />

    <title>UniBoQuest - Dettaglio Missione</title>
  </head>

  <body class="manual-bg missione-page">
    <a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

    <!-- Header PRIVATO -->
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
              <li class="nav-item"><a class="nav-link" href="edit_profile.php">PROFILO</a></li>
            </ul>

            <div class="d-flex gap-2 ubq-nav-right">
              <a class="btn-pixel-yellow" href="logout.php">Esci</a>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <main id="contenuto" class="container">
      <div class="missione-card">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
          <div>
            <h2 class="missione-title font-8bit" style="font-size: 1.05rem;">
              <?php echo h(mb_strtoupper($title, 'UTF-8')); ?>
            </h2>
            <p class="missione-subtitle mb-0"><?php echo h($subtitle !== '' ? $subtitle : ''); ?></p>
          </div>

          <span class="missione-badge"><?php echo h(statusLabel($status)); ?></span>
        </div>

        <hr class="my-4" style="border-color: rgba(255,255,255,.15);">

        <div class="row g-3">
          <!-- Colonna sinistra: descrizione -->
          <div class="col-12 col-lg-7">
            <div class="missione-panel">
              <p class="missione-text"><?php echo h($desc); ?></p>

              <div class="missione-kv">
                <?php if ($cat !== ''): ?><span class="missione-pill">Categoria: <?php echo h($cat); ?></span><?php endif; ?>
                <?php if ($diff !== ''): ?><span class="missione-pill">Difficoltà: <?php echo h($diff); ?></span><?php endif; ?>
                <?php if ($timeLabel !== ''): ?><span class="missione-pill">Tempo: <?php echo h($timeLabel); ?></span><?php endif; ?>
                <span class="missione-pill xp">Ricompensa: +<?php echo (int)$xp; ?> XP</span>
              </div>

              <?php if (!empty($errors)): ?>
                <div class="missione-alert" id="mAlert" style="margin-top: 12px;">
                  <?php foreach ($errors as $e): ?>
                    <div><?php echo h($e); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="missione-panel mt-3">
              <h3 class="font-8bit" style="font-size: 0.9rem; color: #fff;">OBIETTIVO</h3>
              <p class="missione-text"><?php echo h($goal); ?></p>

              <h3 class="font-8bit mt-3" style="font-size: 0.9rem; color: #fff;">STEP</h3>
              <ol class="missione-steps">
                <?php if (empty($steps)): ?>
                  <li>—</li>
                <?php else: ?>
                  <?php foreach ($steps as $s): ?>
                    <li><?php echo h($s); ?></li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ol>

              <p class="missione-note"><?php echo h($note); ?></p>
            </div>
          </div>

          <!-- Colonna destra: azioni -->
          <div class="col-12 col-lg-5">
            <div class="missione-panel">
              <h3 class="font-8bit" style="font-size: 0.9rem; color: #fff;">AZIONI</h3>

              <div class="missione-actions">
                <a class="btn-pixel" href="missioni.php">Torna alle missioni</a>

                <?php if ($status === 'completed'): ?>
                  <button type="button" class="btn-pixel-yellow" disabled>Completata</button>
                <?php elseif ($status === 'active'): ?>
                  <form method="post" action="missione_dettaglio.php?id=<?php echo urlencode($id); ?>" style="display:inline;">
                    <input type="hidden" name="action" value="abandon">
                    <button type="submit" class="btn-pixel">Abbandona</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="missione_dettaglio.php?id=<?php echo urlencode($id); ?>" style="display:inline;">
                    <input type="hidden" name="action" value="join">
                    <button type="submit" class="btn-pixel-yellow">Partecipa</button>
                  </form>
                <?php endif; ?>
              </div>

              <div class="missione-actions mt-2">
                <?php if ($requiresCheckin): ?>
                  <a class="btn-pixel" href="checkin.php?id=<?php echo urlencode($id); ?>">Vai al check-in</a>
                  <button type="button" class="btn-pixel" disabled>Completa</button>
                <?php else: ?>
                  <form method="post" action="missione_dettaglio.php?id=<?php echo urlencode($id); ?>" style="display:inline;">
                    <input type="hidden" name="action" value="complete">
                    <button type="submit" class="btn-pixel"
                      <?php echo ($status !== 'active') ? 'disabled' : ''; ?>
                    >Completa</button>
                  </form>
                  <button type="button" class="btn-pixel" disabled>Condividi</button>
                <?php endif; ?>
              </div>

              <p class="missione-note mt-3">
                <?php if ($requiresCheckin): ?>
                  Questa missione si completa tramite check-in (QR/codice).
                <?php else: ?>
                  Completamento “student-made”: premi Completa dopo aver svolto davvero l’attività.
                <?php endif; ?>
              </p>
            </div>

            <div class="missione-panel mt-3">
              <h3 class="font-8bit" style="font-size: 0.9rem; color: #fff;">RICOMPENSA</h3>
              <p class="missione-text mb-2">XP ottenuti completando la missione:</p>
              <span class="missione-pill xp">+<?php echo (int)$xp; ?> XP</span>
            </div>
          </div>
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
