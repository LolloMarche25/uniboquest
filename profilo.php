<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT * FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
  header('Location: edit_profile.php');
  exit;
}

$displayName =
  (!empty($profile['display_name'])) ? (string)$profile['display_name'] :
  ((!empty($profile['nickname'])) ? (string)$profile['nickname'] :
  ((string)($_SESSION['user_email'] ?? 'Player')));

function yesNo($v): string {
  return ((int)$v === 1) ? 'Sì' : 'No';
}

function avatarInfo(?string $avatar): array {
  $map = [
    'avatar1'     => ['file' => 'avatar1.png', 'alt' => 'Avatar 1 (femmina)'],
    'avatar2'     => ['file' => 'avatar2.png', 'alt' => 'Avatar 2 (ragazzo)'],
    'avatar3'     => ['file' => 'avatar3.png', 'alt' => 'Avatar 3 (cappuccio)'],

    'avatar1.png' => ['file' => 'avatar1.png', 'alt' => 'Avatar 1 (femmina)'],
    'avatar2.png' => ['file' => 'avatar2.png', 'alt' => 'Avatar 2 (ragazzo)'],
    'avatar3.png' => ['file' => 'avatar3.png', 'alt' => 'Avatar 3 (cappuccio)'],
  ];

  $key = trim((string)$avatar);
  if ($key === '' || !isset($map[$key])) {
    return ['src' => 'img/avatars/avatar2.png', 'alt' => 'Avatar predefinito'];
  }

  return ['src' => 'img/avatars/' . $map[$key]['file'], 'alt' => $map[$key]['alt']];
}

$av = avatarInfo($profile['avatar'] ?? null);

$prefs = [];
if ((int)$profile['pref_events'] === 1) $prefs[] = 'Eventi';
if ((int)$profile['pref_study'] === 1)  $prefs[] = 'Studio';
if ((int)$profile['pref_sport'] === 1)  $prefs[] = 'Sport';
if ((int)$profile['pref_social'] === 1) $prefs[] = 'Social';
$prefsLabel = $prefs ? implode(', ', $prefs) : 'Nessuna preferenza selezionata';
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Profilo UniBoQuest (area privata)." />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="css/main.css" />

    <title>UniBoQuest - Profilo</title>
  </head>

  <body class="manual-bg profile-page">
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
              <li class="nav-item"><a class="nav-link" href="missioni.php">MISSIONI</a></li>
              <li class="nav-item"><a class="nav-link active" href="profilo.php" aria-current="page">PROFILO</a></li>
              <?php if (($_SESSION['user_role'] ?? 'user') === 'admin'): ?>
                <li class="nav-item">
                  <a class="nav-link" href="admin_missions.php">ADMIN</a>
                </li>
              <?php endif; ?>
            </ul>

            <div class="d-flex gap-2 ubq-nav-right">
              <a class="btn-pixel-yellow" href="logout.php">Esci</a>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <main id="contenuto" class="container">
      <div class="profile-card">
        <div class="profile-header">
          <h1 class="profile-title font-8bit mb-0" style="font-size: 1.15rem;">
            PROFILO PLAYER
          </h1>
          <span class="profile-step-badge">
            <?php echo htmlspecialchars($displayName); ?>
          </span>
        </div>

        <p class="profile-hint">
          Qui trovi il riepilogo del tuo profilo. Puoi modificarlo quando vuoi.
        </p>

        <section class="profile-section">
          <h2 class="profile-section-title h5">AVATAR</h2>

          <div class="d-flex align-items-center gap-3">
            <img
              src="<?php echo htmlspecialchars($av['src']); ?>"
              alt="<?php echo htmlspecialchars($av['alt']); ?>"
              width="72"
              height="72"
              style="image-rendering: pixelated; border: 1px solid rgba(255,255,255,.18); background: rgba(0,0,0,.25); padding: 6px;"
            />
            <div class="profile-hint mb-0" style="text-align:left;">
              <?php echo htmlspecialchars($av['alt']); ?>
            </div>
          </div>
        </section>

        <section class="profile-section">
          <h2 class="profile-section-title h5">DATI BASE</h2>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <div class="form-label">Nickname</div>
              <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
                <?php echo htmlspecialchars((string)$profile['nickname']); ?>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <div class="form-label">Nome Studente</div>
              <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
                <?php echo htmlspecialchars((string)($profile['display_name'] ?? '')); ?>
                <?php if (empty($profile['display_name'])): ?>
                  <span style="opacity:.7;">(non impostato)</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <section class="profile-section">
          <h2 class="profile-section-title h5">DETTAGLI UNIBO</h2>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <div class="form-label">Corso di laurea</div>
              <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
                <?php echo htmlspecialchars((string)($profile['course'] ?? '')); ?>
                <?php if (empty($profile['course'])): ?><span style="opacity:.7;">(non impostato)</span><?php endif; ?>
              </div>
            </div>

            <div class="col-6 col-md-3">
              <div class="form-label">Anno</div>
              <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
                <?php echo htmlspecialchars((string)($profile['year_label'] ?? '')); ?>
                <?php if (empty($profile['year_label'])): ?><span style="opacity:.7;">(—)</span><?php endif; ?>
              </div>
            </div>

            <div class="col-6 col-md-3">
              <div class="form-label">Campus</div>
              <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
                <?php echo htmlspecialchars((string)($profile['campus'] ?? '')); ?>
                <?php if (empty($profile['campus'])): ?><span style="opacity:.7;">(—)</span><?php endif; ?>
              </div>
            </div>

            <div class="col-12">
              <div class="form-label">Bio</div>
              <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
                <?php echo htmlspecialchars((string)($profile['bio'] ?? '')); ?>
                <?php if (empty($profile['bio'])): ?><span style="opacity:.7;">(nessuna bio)</span><?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <section class="profile-section">
          <h2 class="profile-section-title h5">PREFERENZE MISSIONI</h2>

          <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
            <?php echo htmlspecialchars($prefsLabel); ?>
          </div>
        </section>

        <section class="profile-section">
          <h2 class="profile-section-title h5">PRIVACY</h2>

          <div class="text-white" style="font-family:'Share Tech Mono', monospace;">
            Profilo pubblico: <strong><?php echo htmlspecialchars(yesNo($profile['privacy_public'] ?? 0)); ?></strong>
          </div>
        </section>

        <div class="profile-actions">
          <a class="btn-pixel" href="dashboard.php">Torna alla dashboard</a>
          <a class="btn-pixel-yellow" href="edit_profile.php?from=profile">Modifica profilo</a>
        </div>
      </div>
    </main>

    <footer class="footer-ubq mt-5">
      <div class="container py-4">
        <div class="row gy-3 align-items-start">
          <div class="col-md-4">
            <h2 class="h5 fw-bold mb-2 text-white">UniBoQuest</h2>
            <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
            <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Bologna.</p>
          </div>

          <div class="col-md-3">
            <h2 class="h6 fw-bold mb-2 text-white">Navigazione</h2>
            <ul class="list-unstyled small mb-0">
              <li><a href="gioco.html" class="footer-link text-white text-decoration-none">Il Gioco</a></li>
              <li><a href="faq.html" class="footer-link text-white text-decoration-none">FAQ</a></li>
            </ul>
          </div>

          <div class="col-md-3">
            <h2 class="h6 fw-bold mb-2 text-white">Seguici</h2>
            <nav class="footer-social" aria-label="Social">
              <a href="#" class="social-icon" aria-label="Instagram">
                <span class="bi bi-instagram" aria-hidden="true"></span>
              </a>
              <a href="#" class="social-icon" aria-label="Discord">
                <span class="bi bi-discord" aria-hidden="true"></span>
              </a>
              <a href="https://github.com/LolloMarche25/uniboquest.git" class="social-icon" aria-label="GitHub">
                <span class="bi bi-github" aria-hidden="true"></span>
              </a>
            </nav>
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