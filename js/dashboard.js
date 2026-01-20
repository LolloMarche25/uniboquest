(function () {
  var XP_PER_LEVEL = 100;

  function getXP() {
    try {
      var raw = localStorage.getItem("ubq_xp_total");
      var xp = raw ? parseInt(raw, 10) : 0;
      return isNaN(xp) ? 0 : xp;
    } catch (e) {
      return 0;
    }
  }

  function computeLevel(xp) {
    return Math.floor(xp / XP_PER_LEVEL) + 1;
  }

  function inLevelXP(xp) {
    return xp % XP_PER_LEVEL;
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function setText(id, text) {
    var el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  function setProgress(id, percent) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.width = percent + "%";
    el.setAttribute("aria-valuenow", String(percent));
  }

  function countActiveMissions() {
        try {
            var raw = localStorage.getItem("ubq_mission_state");
            var state = raw ? JSON.parse(raw) : {};
            var count = 0;

            Object.keys(state).forEach(function (k) {
            var m = state[k];
            if (m && m.joined === true && m.completed !== true) count++;
            });

            return count;
        } catch (e) {
            return 0;
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

function countActiveMissionsFromState(state) {
  var count = 0;
  Object.keys(state).forEach(function (k) {
    var m = state[k];
    if (m && m.joined === true && m.completed !== true) count++;
  });
  return count;
}

function missionTitleById(id) {
  // coerente con missione_dettaglio.js
  var map = {
    intro: "Prima Quest: Benvenuto in UniBoQuest",
    checkin: "Check-in Evento (demo)",
    study: "Missione Studio: 25 minuti focus",
    sport: "Allenamento Campus: 15 minuti"
  };
  return map[id] || ("Missione: " + id);
}

function missionLinkById(id) {
  // checkin ha anche pagina dedicata, ma teniamo il dettaglio come “hub”
  return "missione_dettaglio.html?id=" + encodeURIComponent(id);
}

function renderActiveList(state) {
  var list = document.getElementById("dashActiveList");
  var empty = document.getElementById("dashActiveEmpty");
  if (!list || !empty) return;

  list.innerHTML = "";

  var activeIds = Object.keys(state).filter(function (k) {
    var m = state[k];
    return m && m.joined === true && m.completed !== true;
  });

  if (activeIds.length === 0) {
    empty.classList.remove("d-none");
    return;
  }

  empty.classList.add("d-none");

  // mostriamo max 3 per non riempire la dashboard
  activeIds.slice(0, 3).forEach(function (id) {
    var wrapper = document.createElement("div");
    wrapper.className = "dashboard-mission";

    var left = document.createElement("div");
    var title = document.createElement("p");
    title.className = "dashboard-mission-title";
    title.textContent = missionTitleById(id);

    var meta = document.createElement("div");
    meta.className = "dashboard-mission-meta";
    meta.textContent = "Stato: In corso • (demo)";

    left.appendChild(title);
    left.appendChild(meta);

    var a = document.createElement("a");
    a.className = "btn-pixel";
    a.href = missionLinkById(id);
    a.textContent = "Apri";

    wrapper.appendChild(left);
    wrapper.appendChild(a);

    list.appendChild(wrapper);
  });
}

  function render() {
  var xp = getXP();
  var level = computeLevel(xp);
  var curr = inLevelXP(xp);
  var pct = clamp(Math.round((curr / XP_PER_LEVEL) * 100), 0, 100);

  setText("dashBadge", "LIV " + level + " • " + xp + " XP");
  setText("dashLevel", String(level));
  setText("dashXP", xp + " / " + (level * XP_PER_LEVEL));
  setText("dashXPInLevel", curr + " / " + XP_PER_LEVEL);
  setProgress("dashXPBar", pct);

  // Missioni attive + lista
  var state = loadMissionState();
  setText("dashActiveMissions", String(countActiveMissionsFromState(state)));
  renderActiveList(state);
}

  document.addEventListener("DOMContentLoaded", function () {
    render();

    var resetBtn = document.getElementById("resetDemoBtn");
    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        var ok = confirm("Reset demo: azzerare XP e stato missioni/check-in?");
        if (!ok) return;

        resetDemoData();
        render();
        alert("Demo resettata. XP e stati azzerati.");
      });
    }

    // Facoltativo: aggiorna se in futuro userete più tab o eventi
    window.addEventListener("storage", function (e) {
        if (!e) return;
        if (e && e.key === "ubq_xp_total" || e.key === "ubq_mission_state") render();
    });
  });
})();
