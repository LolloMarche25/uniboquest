<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
  header("Location: login.php");
  exit;
}

$errors = [];

$data = [
  'nickname' => '',
  'display_name' => '',
  'course' => '',
  'year_label' => '',
  'campus' => '',
  'bio' => '',
  'pref_events' => 0,
  'pref_study' => 0,
  'pref_sport' => 0,
  'pref_social' => 0,
  'avatar' => 'avatar3',
  'privacy_public' => 0,
];

$allowedAvatars = ['avatar1', 'avatar2', 'avatar3'];

$hasProfile = false;

$stmt = $mysqli->prepare("SELECT * FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $hasProfile = true;
  foreach ($data as $k => $v) {
    if (array_key_exists($k, $row) && $row[$k] !== null) {
      $data[$k] = $row[$k];
    }
  }
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data['nickname'] = trim((string)($_POST['nickname'] ?? ''));
  $data['display_name'] = trim((string)($_POST['display_name'] ?? ''));
  $data['course'] = trim((string)($_POST['course'] ?? ''));
  $data['year_label'] = trim((string)($_POST['year'] ?? ''));
  $data['campus'] = trim((string)($_POST['campus'] ?? ''));
  $data['bio'] = trim((string)($_POST['bio'] ?? ''));

  $avatar = trim((string)($_POST['avatar'] ?? 'avatar3'));
  $data['avatar'] = in_array($avatar, $allowedAvatars, true) ? $avatar : 'avatar3';

  $data['pref_events'] = isset($_POST['pref_events']) ? 1 : 0;
  $data['pref_study']  = isset($_POST['pref_study'])  ? 1 : 0;
  $data['pref_sport']  = isset($_POST['pref_sport'])  ? 1 : 0;
  $data['pref_social'] = isset($_POST['pref_social']) ? 1 : 0;
  $data['privacy_public'] = isset($_POST['privacy_public']) ? 1 : 0;

  if ($data['nickname'] === '' || mb_strlen($data['nickname']) < 3) {
    $errors[] = "Nickname obbligatorio (min 3 caratteri).";
  }
  if (mb_strlen($data['nickname']) > 32) {
    $errors[] = "Nickname troppo lungo (max 32).";
  }
  if (mb_strlen($data['display_name']) > 60) {
    $errors[] = "Nome visualizzato troppo lungo (max 60).";
  }
  if (mb_strlen($data['course']) > 80) {
    $errors[] = "Corso di laurea troppo lungo (max 80).";
  }
  if (mb_strlen($data['year_label']) > 20) {
    $errors[] = "Anno troppo lungo (max 20).";
  }
  if (mb_strlen($data['campus']) > 40) {
    $errors[] = "Campus troppo lungo (max 40).";
  }
  if (mb_strlen($data['bio']) > 255) {
    $errors[] = "Bio troppo lunga (max 255).";
  }

  if (!$errors) {
    $stmt = $mysqli->prepare("SELECT user_id FROM profiles WHERE nickname = ? AND user_id <> ? LIMIT 1");
    $stmt->bind_param("si", $data['nickname'], $userId);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($dup) {
      $errors[] = "Nickname già in uso. Scegline un altro.";
    }
  }

  if (!$errors) {
    $sql = "
      INSERT INTO profiles
        (user_id, nickname, display_name, course, year_label, campus, bio,
         pref_events, pref_study, pref_sport, pref_social, avatar, privacy_public)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        nickname=VALUES(nickname),
        display_name=VALUES(display_name),
        course=VALUES(course),
        year_label=VALUES(year_label),
        campus=VALUES(campus),
        bio=VALUES(bio),
        pref_events=VALUES(pref_events),
        pref_study=VALUES(pref_study),
        pref_sport=VALUES(pref_sport),
        pref_social=VALUES(pref_social),
        avatar=VALUES(avatar),
        privacy_public=VALUES(privacy_public)
    ";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
      "issssssiiiisi",
      $userId,
      $data['nickname'],
      $data['display_name'],
      $data['course'],
      $data['year_label'],
      $data['campus'],
      $data['bio'],
      $data['pref_events'],
      $data['pref_study'],
      $data['pref_sport'],
      $data['pref_social'],
      $data['avatar'],
      $data['privacy_public']
    );

    if ($stmt->execute()) {
      $stmt->close();

      header("Location: dashboard.php");
      exit;
    }

    $stmt->close();
    $errors[] = "Errore nel salvataggio profilo. Riprova.";
  }
}
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Completa il profilo UniBoQuest e inizia l'avventura." />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="css/main.css" />

        <title>UniBoQuest - Completa Profilo</title>
    </head>

    <body class="manual-bg profile-page">
        <a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

        <header class="header-glass">
            <nav class="navbar navbar-expand-md navbar-dark">
                <div class="container-fluid">
                    <a class="navbar-brand font-8bit" href="dashboard.php" aria-label="UniBoQuest Dashboard">UniBoQuest</a>

                    <div class="mx-auto d-none d-md-block">
                        <span class="font-8bit text-white" style="font-size: 0.9rem; opacity: 0.95;">
                            COMPLETA IL TUO PROFILO
                        </span>
                    </div>

                    <button
                        class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#nav"
                        aria-controls="nav"
                        aria-expanded="false"
                        aria-label="Apri menu"
                    >
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="nav">
                        <div class="d-md-none pt-3 pb-2 text-center">
                            <span class="font-8bit text-white" style="font-size: 0.85rem; opacity: 0.95;">
                                COMPLETA IL TUO PROFILO
                            </span>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main id="contenuto" class="container">
            <div class="profile-card">
                <div class="profile-header">
                    <h2 class="profile-title font-8bit mb-0" style="font-size: 1.15rem;">PROFILO PLAYER</h2>
                    <span class="profile-step-badge">STEP 2/2</span>
                </div>

                <p class="profile-hint">
                    Ultimo passo: scegli come comparirai in UniBoQuest.</p>

                <form action="edit_profile.php" method="post" id="profileForm">
                    <section class="profile-section">
                        <h3 class="profile-section-title">DATI BASE</h3>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="nickname" class="form-label">Nickname *</label>
                                <input
                                    type="text"
                                    id="nickname"
                                    name="nickname"
                                    class="form-control p-3"
                                    placeholder="Username pubblico"
                                    required
                                    minlength="3"
                                    pattern=".*\S.*"
                                    autocomplete="nickname"
                                    value="<?php echo htmlspecialchars((string)$data['nickname']); ?>"
                                />
                                <div class="form-text">Questo sarà visibile agli altri.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="display_name" class="form-label">Nome visualizzato</label>
                                <input
                                    type="text"
                                    id="display_name"
                                    name="display_name"
                                    class="form-control p-3"
                                    placeholder="Es. Lorenzo M."
                                    autocomplete="name"
                                    value="<?php echo htmlspecialchars((string)$data['display_name']); ?>"
                                />
                                <div class="form-text">Se lo lasci vuoto, useremo il nickname.</div>
                            </div>
                        </div>
                    </section>

                    <section class="profile-section">
                        <h3 class="profile-section-title">DETTAGLI UNIBO</h3>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="course" class="form-label">Corso di laurea</label>
                                <select id="course" name="course" class="form-select p-3">
                                    <option value="" <?php echo ($data['course']==='' ? 'selected' : ''); ?>>Seleziona…</option>
                                    <option value="Informatica" <?php echo ($data['course']==='Informatica' ? 'selected' : ''); ?>>Informatica</option>
                                    <option value="Ingegneria"  <?php echo ($data['course']==='Ingegneria'  ? 'selected' : ''); ?>>Ingegneria</option>
                                    <option value="Economia"    <?php echo ($data['course']==='Economia'    ? 'selected' : ''); ?>>Economia</option>
                                    <option value="Medicina"    <?php echo ($data['course']==='Medicina'    ? 'selected' : ''); ?>>Medicina</option>
                                    <option value="Giurisprudenza" <?php echo ($data['course']==='Giurisprudenza' ? 'selected' : ''); ?>>Giurisprudenza</option>
                                    <option value="Altro"       <?php echo ($data['course']==='Altro'       ? 'selected' : ''); ?>>Altro</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-3">
                                <label for="year" class="form-label">Anno</label>
                                <select id="year" name="year" class="form-select p-3">
                                    <option value="" <?php echo ($data['year_label']==='' ? 'selected' : ''); ?>>—</option>
                                    <option value="1" <?php echo ($data['year_label']==='1' ? 'selected' : ''); ?>>1</option>
                                    <option value="2" <?php echo ($data['year_label']==='2' ? 'selected' : ''); ?>>2</option>
                                    <option value="3" <?php echo ($data['year_label']==='3' ? 'selected' : ''); ?>>3</option>
                                    <option value="4" <?php echo ($data['year_label']==='4' ? 'selected' : ''); ?>>4</option>
                                    <option value="5" <?php echo ($data['year_label']==='5' ? 'selected' : ''); ?>>5</option>
                                    <option value="Fuori corso" <?php echo ($data['year_label']==='Fuori corso' ? 'selected' : ''); ?>>Fuori corso</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-3">
                                <label for="campus" class="form-label">Campus</label>
                                <select id="campus" name="campus" class="form-select p-3">
                                    <option value="" <?php echo ($data['campus']==='' ? 'selected' : ''); ?>>—</option>
                                    <option value="Bologna" <?php echo ($data['campus']==='Bologna' ? 'selected' : ''); ?>>Bologna</option>
                                    <option value="Cesena"  <?php echo ($data['campus']==='Cesena'  ? 'selected' : ''); ?>>Cesena</option>
                                    <option value="Forlì"   <?php echo ($data['campus']==='Forlì'   ? 'selected' : ''); ?>>Forlì</option>
                                    <option value="Ravenna" <?php echo ($data['campus']==='Ravenna' ? 'selected' : ''); ?>>Ravenna</option>
                                    <option value="Rimini"  <?php echo ($data['campus']==='Rimini'  ? 'selected' : ''); ?>>Rimini</option>
                                </select>
                            </div>


                            <div class="col-12">
                                <label for="bio" class="form-label">Bio (breve)</label>
                                <textarea
                                    id="bio"
                                    name="bio"
                                    class="form-control p-3"
                                    rows="3"
                                    placeholder="Es. 'Cacciatore di XP tra una lezione e l'altra'"
                                ><?php echo htmlspecialchars((string)$data['bio']); ?></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="profile-section">
                        <h3 class="profile-section-title">PREFERENZE MISSIONI</h3>

                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pref_events" name="pref_events" 
                                <?php echo ((int)$data['pref_events'] === 1) ? 'checked' : ''; ?>
                                />
                                <label class="form-check-label" for="pref_events">Eventi</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pref_study" name="pref_study" 
                                 <?php echo ((int)$data['pref_study'] === 1) ? 'checked' : ''; ?>
                                />
                                <label class="form-check-label" for="pref_study">Studio</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pref_sport" name="pref_sport" 
                                 <?php echo ((int)$data['pref_sport'] === 1) ? 'checked' : ''; ?>
                                />
                                <label class="form-check-label" for="pref_sport">Sport</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pref_social" name="pref_social" 
                                 <?php echo ((int)$data['pref_social'] === 1) ? 'checked' : ''; ?>
                                />
                                <label class="form-check-label" for="pref_social">Social</label>
                            </div>
                        </div>

                        <div class="form-text mt-2">
                            (Sono preferenze iniziali: poi le useremo per personalizzare le missioni in dashboard.)
                        </div>
                    </section>

                    <section class="profile-section">
                    <h3 class="profile-section-title">SCEGLI AVATAR</h3>

                    <div class="profile-avatar-grid" id="avatarGrid">
                        <?php
                        $avatars = [
                            ['id' => 'avatar1', 'label' => 'Avatar 1', 'file' => 'img/avatars/avatar1.png'],
                            ['id' => 'avatar2', 'label' => 'Avatar 2', 'file' => 'img/avatars/avatar2.png'],
                            ['id' => 'avatar3', 'label' => 'Avatar 3', 'file' => 'img/avatars/avatar3.png'],
                        ];
                        $current = (string)($data['avatar'] ?? 'avatar1');
                        ?>

                        <?php foreach ($avatars as $a): ?>
                        <label class="profile-avatar-card position-relative">
                            <input
                            class="profile-avatar-radio"
                            type="radio"
                            name="avatar"
                            value="<?php echo htmlspecialchars($a['id']); ?>"
                            <?php echo ($current === $a['id']) ? 'checked' : ''; ?>
                            required
                            />

                            <img
                            src="<?php echo htmlspecialchars($a['file']); ?>"
                            alt="<?php echo htmlspecialchars($a['label']); ?>"
                            class="profile-avatar-img"
                            loading="lazy"
                            />

                            <div class="profile-avatar-name"><?php echo htmlspecialchars($a['label']); ?></div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-text mt-2">
                        Scegli un avatar: verrà salvato nel tuo profilo.
                    </div>
                    </section>

                    <section class="profile-section">
                        <h3 class="profile-section-title">PRIVACY</h3>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="privacy_public" name="privacy_public" 
                             <?php echo ((int)$data['privacy_public'] === 1) ? 'checked' : ''; ?>
                            />
                            <label class="form-check-label" for="privacy_public">
                                Rendi visibile il mio profilo (nickname + livello) agli altri studenti
                            </label>
                        </div>
                    </section>

                    <div class="profile-actions">
                        <a class="btn-pixel" href="<?php echo (($_GET['from'] ?? '') === 'profile') ? 'profilo.php' : 'registrazione.php'; ?>">Indietro</a>
                        <button type="submit" class="btn-pixel-yellow">Salva e vai alla dashboard</button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="footer-ubq mt-5">
            <div class="container py-4">
                <div class="row gy-3 align-items-start">
                    <div class="col-md-4">
                        <h5 class="fw-bold mb-2 text-white">UniBoQuest</h5>
                        <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
                        <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Cesena.</p>
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
        <script src="js/edit_profile.js"></script>
    </body>
</html>
