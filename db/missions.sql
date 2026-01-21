USE uniboquest;

CREATE TABLE IF NOT EXISTS missions (
  id VARCHAR(50) NOT NULL PRIMARY KEY,     -- intro, checkin, study, sport
  title VARCHAR(120) NOT NULL,
  subtitle VARCHAR(180) NULL,
  description TEXT NOT NULL,
  category ENUM('eventi','studio','social','sport') NOT NULL,
  difficulty ENUM('facile','media','difficile') NOT NULL,
  time_label VARCHAR(40) NULL,             -- es. "5 min", "QR/Codice"
  xp INT NOT NULL DEFAULT 0,
  requires_checkin TINYINT(1) NOT NULL DEFAULT 0,
  checkin_code VARCHAR(50) NULL,           -- solo per missioni check-in
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO missions
(id, title, subtitle, description, category, difficulty, time_label, xp, requires_checkin, checkin_code, active)
VALUES
('intro', 'Prima Quest: Benvenuto in UniBoQuest', 'Sblocca il tuo percorso e ottieni i primi XP.',
 'Completa il tutorial per capire come funzionano missioni, livelli e check-in.',
 'social', 'facile', '5 min', 20, 0, NULL, 1),

('checkin', 'Check-in Evento', 'Conferma la presenza con QR o codice fallback.',
 'Durante un evento, scansiona il QR mostrato dagli organizzatori. Se non puoi, inserisci un codice testuale equivalente.',
 'eventi', 'media', 'QR/Codice', 50, 1, 'UBQ-2026', 1),

('study', 'Missione Studio: 25 minuti focus', 'Una sessione Pomodoro per guadagnare XP.',
 'Imposta un timer da 25 minuti e studia senza interruzioni. A fine sessione, segna la missione come completata.',
 'studio', 'facile', '25 min', 30, 0, NULL, 1),

('sport', 'Allenamento Campus: 15 minuti', 'Mini circuito per XP extra.',
 'Completa un circuito leggero da 15 minuti. Nel prototipo, la prova è simulata.',
 'sport', 'difficile', '15 min', 70, 0, NULL, 1)
ON DUPLICATE KEY UPDATE
  title=VALUES(title),
  subtitle=VALUES(subtitle),
  description=VALUES(description),
  category=VALUES(category),
  difficulty=VALUES(difficulty),
  time_label=VALUES(time_label),
  xp=VALUES(xp),
  requires_checkin=VALUES(requires_checkin),
  checkin_code=VALUES(checkin_code),
  active=VALUES(active);
