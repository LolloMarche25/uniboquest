(function () {
  var VALID_CODE = "UBQ-2026";
  var XP_REWARD = 50;

  function getEl(id) {
    return document.getElementById(id);
  }

  function setMsg(type, text) {
    var box = getEl("msgBox");
    if (!box) return;
    box.classList.remove("d-none", "ok", "err");
    box.classList.add(type === "ok" ? "ok" : "err");
    box.textContent = text;
  }

  function setStatus(completed) {
    var badge = getEl("statusBadge");
    if (!badge) return;
    badge.textContent = completed ? "Completato" : "Non completato";
  }

  function loadCompleted() {
    try {
      return localStorage.getItem("ubq_checkin_completed") === "1";
    } catch (e) {
      return false;
    }
  }

  function saveCompleted() {
    try {
      localStorage.setItem("ubq_checkin_completed", "1");
    } catch (e) {
      // ignore
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

  function loadMissionState() {
    try {
      var raw = localStorage.getItem("ubq_mission_state");
      return raw ? JSON.parse(raw) : {};
    } catch (e) {
      return {};
    }
  }

  function saveMissionState(state) {
    try {
      localStorage.setItem("ubq_mission_state", JSON.stringify(state));
    } catch (e) {
      // ignore
    }
  }

  function markCheckinJoinedIfNeeded() {
    var state = loadMissionState();
    var m = state.checkin;

    // Se già completata, non tocchiamo nulla
    if (m && m.completed === true) return;

    // Se non esiste o non è joined, segna come "in corso"
    if (!m || m.joined !== true) {
      state.checkin = { joined: true, completed: false };
      saveMissionState(state);
    }
  }

  function markCheckinCompleted() {
    var state = loadMissionState();
    state.checkin = { joined: true, completed: true };
    saveMissionState(state);
  }

  document.addEventListener("DOMContentLoaded", function () {
    var form = getEl("checkinForm");
    var code = getEl("code");
    var btn = getEl("submitBtn");

    var already = loadCompleted();
    setStatus(already);

    // Se non è già completato, la missione checkin diventa "attiva" quando entri qui
    if (!already) {
      markCheckinJoinedIfNeeded();
    }

    if (already) {
      // allinea anche lo stato missione (utile se qualcuno resetta solo un key)
      markCheckinCompleted();

      setMsg("ok", "Check-in già completato (demo). XP già assegnati in UI.");
      if (btn) btn.disabled = true;
      if (code) code.disabled = true;
    }

    if (!form) return;

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!code || !code.checkValidity()) {
        code.focus();
        code.reportValidity();
        return;
      }

      var value = (code.value || "").trim().toUpperCase();

      if (value === VALID_CODE) {
        saveCompleted();
        addXP(XP_REWARD);
        markCheckinCompleted();

        setStatus(true);
        setMsg("ok", "Check-in confermato! +" + XP_REWARD + " XP (demo).");

        if (btn) btn.disabled = true;
        if (code) code.disabled = true;
        return;
      }

      setMsg("err", "Codice non valido. Riprova (demo: usa " + VALID_CODE + ").");
      code.focus();
      code.select();
    });
  });
})();
