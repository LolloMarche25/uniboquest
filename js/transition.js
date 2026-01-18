document.addEventListener("DOMContentLoaded", () => {
  const scene = document.querySelector(".hero-ubq-scene");
  const overlay = document.querySelector(".ubq-transition");

  if (!scene || !overlay) {
    return;
  }

  const links = scene.querySelectorAll("a[data-ubq-go]");

  links.forEach((a) => {
    a.addEventListener("click", (e) => {
      // se l'utente apre in nuova scheda o usa tasti speciali, non bloccare
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
        return;
      }

      e.preventDefault();

      const url = a.getAttribute("data-ubq-go") || a.getAttribute("href");
      overlay.classList.add("is-on");

      // durata coerente con CSS (900ms)
      window.setTimeout(() => {
        window.location.href = url;
      }, 900);
    });
  });
});
