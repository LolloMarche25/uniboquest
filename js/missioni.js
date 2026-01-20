(function () {
  function normalize(s) {
    return (s || "").toLowerCase().trim();
  }

  function applyFilters() {
    var qEl = document.getElementById("q");
    var catEl = document.getElementById("cat");
    var diffEl = document.getElementById("diff");

    if (!qEl || !catEl || !diffEl) return;

    var q = normalize(qEl.value);
    var cat = catEl.value;
    var diff = diffEl.value;

    var items = Array.prototype.slice.call(document.querySelectorAll("#missionList .mission-card"));
    var visible = 0;

    items.forEach(function (card) {
      var title = normalize(card.getAttribute("data-title"));
      var c = card.getAttribute("data-cat");
      var d = card.getAttribute("data-diff");

      var okQ = q === "" || title.indexOf(q) !== -1;
      var okC = cat === "all" || c === cat;
      var okD = diff === "all" || d === diff;

      var show = okQ && okC && okD;
      card.classList.toggle("d-none", !show);
      if (show) visible++;
    });

    var badge = document.getElementById("resultBadge");
    if (badge) {
      badge.textContent = visible + (visible === 1 ? " missione" : " missioni");
    }

    var empty = document.getElementById("emptyState");
    if (empty) {
      empty.classList.toggle("d-none", visible !== 0);
    }
  }

  function resetFilters() {
    var qEl = document.getElementById("q");
    var catEl = document.getElementById("cat");
    var diffEl = document.getElementById("diff");

    if (qEl) qEl.value = "";
    if (catEl) catEl.value = "all";
    if (diffEl) diffEl.value = "all";

    applyFilters();
  }

  document.addEventListener("DOMContentLoaded", function () {
    var qEl = document.getElementById("q");
    var catEl = document.getElementById("cat");
    var diffEl = document.getElementById("diff");
    var resetBtn = document.getElementById("resetBtn");

    if (qEl) qEl.addEventListener("input", applyFilters);
    if (catEl) catEl.addEventListener("change", applyFilters);
    if (diffEl) diffEl.addEventListener("change", applyFilters);
    if (resetBtn) resetBtn.addEventListener("click", resetFilters);

    applyFilters();
  });
})();
