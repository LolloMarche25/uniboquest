(function () {
  // Dataset demo (poi arriverà dal DB via PHP)
  var MISSIONS = {
    intro: {
      id: "intro",
      title: "Prima Quest: Benvenuto in UniBoQuest",
      subtitle: "Sblocca il tuo percorso e ottieni i primi XP.",
      desc: "Completa il tutorial per capire come funzionano missioni, livelli e check-in.",
      cat: "Social",
      diff: "Facile",
      time: "5 min",
      xp: 20,
      goal: "Completare il tutorial e confermare l'avvio dell'avventura.",
      steps: [
        "Leggi la pagina 'Il Gioco' e capisci le regole base.",
        "Apri la sezione Missioni e scegli una quest.",
        "Conferma che hai completato il tutorial (demo)."
      ],
      note: "Nota: nel prototipo, alcuni passaggi sono simulati."
    },
    checkin: {
      id: "checkin",
      title: "Check-in Evento (demo)",
      subtitle: "Conferma la presenza con QR o codice fallback.",
      desc: "Durante un evento, scansiona il QR mostrato dagli organizzatori. Se non puoi, inserisci un codice testuale equivalente.",
      cat: "Eventi",
      diff: "Media",
      time: "QR/Codice",
      xp: 50,
      goal: "Registrare la tua presenza all'evento tramite QR o codice.",
      steps: [
        "Apri la pagina Check-in.",
        "Scansiona il QR o inserisci il codice.",
        "Ricevi conferma e ottieni XP."
      ],
      note: "Nota: la validazione QR/codice sarà lato server in PHP."
    },
    study: {
      id: "study",
      title: "Missione Studio: 25 minuti focus",
      subtitle: "Una sessione Pomodoro per guadagnare XP.",
      desc: "Imposta un timer da 25 minuti e studia senza interruzioni. A fine sessione, segna la missione come completata.",
      cat: "Studio",
      diff: "Facile",
      time: "25 min",
      xp: 30,
      goal: "Completare una sessione di studio concentrato da 25 minuti.",
      steps: [
        "Scegli un argomento di studio.",
        "Avvia un timer da 25 minuti (Pomodoro).",
        "Alla fine, segna la missione come completata (demo)."
      ],
      note: "Consiglio: spegni notifiche e modalità distrazioni."
    },
    sport: {
      id: "sport",
      title: "Allenamento Campus: 15 minuti",
      subtitle: "Mini circuito per XP extra.",
      desc: "Completa un circuito leggero da 15 minuti. Nel prototipo, la prova è simulata.",
      cat: "Sport",
      diff: "Difficile",
      time: "15 min",
      xp: 70,
      goal: "Concludere un mini circuito di 15 minuti.",
      steps: [
        "Riscaldamento 2 minuti.",
        "Circuito 10 minuti (es. squat/push-up/plank).",
        "Defaticamento 3 minuti."
      ],
      note: "Nel prototipo non raccogliamo prove reali."
    }
  };

  function qs(name) {
    var params = new URLSearchParams(window.location.search);
    return params.get(name);
  }

  function el(id) {
    return document.getElementById(id);
  }

  function pill(text, extraClass) {
    var span = document.createElement("span");
    span.className = "missione-pill" + (extraClass ? " " + extraClass : "");
    span.textContent = text;
    return span;
  }

  function setMission(m) {
    el("mTitle").textContent = m.title.toUpperCase();
    el("mSubtitle").textContent = m.subtitle;
    el("mDesc").textContent = m.desc;
    el("mGoal").textContent = m.goal;
    el("mNote").textContent = m.note;
    el("mXP").textContent = "+" + m.xp + " XP";

    var meta = el("mMeta");
    meta.innerHTML = "";
    meta.appendChild(pill("Categoria: " + m.cat));
    meta.appendChild(pill("Difficoltà: " + m.diff));
    meta.appendChild(pill("Tempo: " + m.time));
    meta.appendChild(pill("Ricompensa: +" + m.xp + " XP", "xp"));

    var steps = el("mSteps");
    steps.innerHTML = "";
    m.steps.forEach(function (s) {
      var li = document.createElement("li");
      li.textContent = s;
      steps.appendChild(li);
    });
  }

  function loadState(missionId) {
    try {
      var raw = localStorage.getItem("ubq_mission_state");
      var state = raw ? JSON.parse(raw) : {};
      return state[missionId] || { joined: false, completed: false };
    } catch (e) {
      return { joined: false, completed: false };
    }
  }

  function saveState(missionId, data) {
    try {
      var raw = localStorage.getItem("ubq_mission_state");
      var state = raw ? JSON.parse(raw) : {};
      state[missionId] = data;
      localStorage.setItem("ubq_mission_state", JSON.stringify(state));
    } catch (e) {
      // ignore (storage disabilitato)
    }
  }

  function renderStatus(state) {
    var status = el("mStatus");
    var alert = el("mAlert");
    var joinBtn = el("joinBtn");
    var completeBtn = el("completeBtn");

    if (state.completed) {
      status.textContent = "Completata";
      alert.classList.remove("d-none");
      alert.textContent = "Missione completata (demo). XP assegnati in UI.";
      joinBtn.textContent = "Partecipa";
      joinBtn.disabled = true;
      completeBtn.disabled = true;
      return;
    }

    if (state.joined) {
      status.textContent = "In corso";
      alert.classList.remove("d-none");
      alert.textContent = "Sei iscritto a questa missione (demo).";
      joinBtn.textContent = "Abbandona";
      joinBtn.disabled = false;
      completeBtn.disabled = false;
    } else {
      status.textContent = "Disponibile";
      alert.classList.add("d-none");
      alert.textContent = "";
      joinBtn.textContent = "Partecipa";
      joinBtn.disabled = false;
      completeBtn.disabled = true;
    }
  }

  function addXP(amount) {
    try {
      var raw = localStorage.getItem("ubq_xp_total");
      var xp = raw ? parseInt(raw, 10) : 0;
      if (isNaN(xp)) xp = 0;
      xp += amount;
      localStorage.setItem("ubq_xp_total", String(xp));
    } catch (e) {
      // ignore
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var id = qs("id") || "intro";
    var m = MISSIONS[id] || MISSIONS.intro;

    setMission(m);

    var state = loadState(m.id);
    renderStatus(state);

    var joinBtn = el("joinBtn");
    var completeBtn = el("completeBtn");
    var shareBtn = el("shareBtn");

    if (joinBtn) {
      joinBtn.addEventListener("click", function () {
        if (state.completed) return;

        state.joined = !state.joined;
        if (!state.joined) state.completed = false;

        saveState(m.id, state);
        renderStatus(state);
      });
    }

    if (completeBtn) {
      completeBtn.addEventListener("click", function () {
        if (!state.joined || state.completed) return;

        state.completed = true;
        state.joined = true;

        saveState(m.id, state);
        addXP(m.xp);
        renderStatus(state);
      });
    }

    if (shareBtn) {
      shareBtn.addEventListener("click", function () {
        alert("Condivisione (demo): qui in futuro creeremo un link o una card da condividere.");
      });
    }
  });
})();
