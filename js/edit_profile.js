(function () {
  function syncAvatarSelection() {
    var cards = document.querySelectorAll(".profile-avatar-card");
    cards.forEach(function (card) {
      var radio = card.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        card.classList.add("is-selected");
      } else {
        card.classList.remove("is-selected");
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    syncAvatarSelection();

    var grid = document.getElementById("avatarGrid");
    if (!grid) return;

    grid.addEventListener("change", function (e) {
      var t = e.target;
      if (t && t.matches && t.matches('input[type="radio"][name="avatar"]')) {
        syncAvatarSelection();
      }
    });

    grid.addEventListener("click", function (e) {
      var card = e.target && e.target.closest ? e.target.closest(".profile-avatar-card") : null;
      if (card) {
        window.setTimeout(syncAvatarSelection, 0);
      }
    });
  });
})();
