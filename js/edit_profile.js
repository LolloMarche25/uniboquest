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

  function isNicknameValid(value) {
    // non solo spazi, minimo 3 caratteri (puoi cambiare la soglia)
    var v = (value || "").trim();
    return v.length >= 3;
  }

  document.addEventListener("DOMContentLoaded", function () {
    // --- Avatar selection UI ---
    syncAvatarSelection();

    var grid = document.getElementById("avatarGrid");
    if (grid) {
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
    }

    // --- Submit handler: blocca action e redirige solo se valido ---
    var form = document.getElementById("profileForm");
    if (!form) return;

    var nicknameInput = document.getElementById("nickname");

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      // 1) Validazione HTML5 base
      if (!form.checkValidity()) {
        // Fa vedere i messaggi nativi del browser
        form.reportValidity();
        return;
      }

      // 2) Validazione “student-made” extra sul nickname
      if (nicknameInput && !isNicknameValid(nicknameInput.value)) {
        nicknameInput.focus();
        nicknameInput.setCustomValidity("Inserisci un nickname valido (min 3 caratteri, non solo spazi).");
        nicknameInput.reportValidity();
        nicknameInput.setCustomValidity("");
        return;
      }

      // 3) (Opzionale) assicurati che un avatar sia selezionato
      var selectedAvatar = form.querySelector('input[name="avatar"]:checked');
      if (!selectedAvatar) {
        alert("Seleziona un avatar per continuare.");
        return;
      }

      // 4) Tutto ok -> redirect (in futuro qui farete submit PHP)
      window.location.href = "dashboard.html";
    });
  });
})();
