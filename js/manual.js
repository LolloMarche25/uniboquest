// Manuale UniBoQuest: desktop flip, mobile pagine singole
document.addEventListener("DOMContentLoaded", () => {
  const book = document.getElementById("manuale");
  const pages = Array.from(document.querySelectorAll(".page"));
  if (!book || pages.length === 0) return;

  // ---- DESKTOP (md e su): click = flip
  const mqDesktop = window.matchMedia("(min-width: 768px)");

  function setupDesktopFlip() {
    pages.forEach((page, index) => {
      page.style.zIndex = String(pages.length - index);

      page.onclick = () => {
        const flipped = page.classList.contains("flipped");
        if (!flipped) {
          page.classList.add("flipped");
          if (index === 0) book.classList.remove("closed");
          setTimeout(() => (page.style.zIndex = String(index + 1)), 350);
        } else {
          page.classList.remove("flipped");
          if (index === 0) book.classList.add("closed");
          setTimeout(() => (page.style.zIndex = String(pages.length - index)), 350);
        }
      };
    });
  }

  // ---- MOBILE: mostra una pagina per volta (front/back = 2 pagine logiche)
  const btnPrev = document.querySelector("[data-manual-prev]");
  const btnNext = document.querySelector("[data-manual-next]");
  const indicator = document.querySelector("[data-manual-indicator]");

  // ogni ".page" ha 2 facce: front/back => totale "pagine logiche" = pages.length * 2
  let current = 0; // 0..(pages.length*2 -1)

  function renderMobile() {
    const total = pages.length * 2;

    pages.forEach((page) => {
      page.classList.add("manual-mobile");
      page.dataset.active = "false";
      page.classList.remove("flipped"); // mobile non usa flip
    });

    const pageIndex = Math.floor(current / 2);
    const face = current % 2; // 0 front, 1 back
    const activePage = pages[pageIndex];

    activePage.dataset.active = "true";
    activePage.dataset.face = String(face);

    if (indicator) indicator.textContent = `Pagina ${current + 1} / ${total}`;

    if (btnPrev) btnPrev.disabled = current === 0;
    if (btnNext) btnNext.disabled = current === total - 1;
  }

  function setupMobilePager() {
    renderMobile();

    // IMPORTANT: uso onclick per evitare duplicazioni quando cambia viewport
    if (btnPrev) {
      btnPrev.onclick = () => {
        current = Math.max(0, current - 1);
        renderMobile();
      };
    }

    if (btnNext) {
      btnNext.onclick = () => {
        const total = pages.length * 2;
        current = Math.min(total - 1, current + 1);
        renderMobile();
      };
    }
  }

  function cleanupForDesktop() {
    pages.forEach((p) => {
      p.classList.remove("manual-mobile");
      delete p.dataset.active;
      delete p.dataset.face;
    });

    // rimuovo i click mobile se esistono
    if (btnPrev) btnPrev.onclick = null;
    if (btnNext) btnNext.onclick = null;
  }

  function cleanupForMobile() {
    // rimuovo i click desktop
    pages.forEach((p) => (p.onclick = null));
    // stato libro “chiuso” torna gestibile dal flip desktop solo
    book.classList.add("closed");
  }

  function init() {
    if (mqDesktop.matches) {
      cleanupForDesktop();
      setupDesktopFlip();
    } else {
      cleanupForMobile();
      setupMobilePager();
    }
  }

  init();

  // se ruoti lo schermo / cambi viewport
  mqDesktop.addEventListener("change", () => {
    // reset “soft” e riparto
    pages.forEach((p) => (p.onclick = null));
    if (btnPrev) btnPrev.onclick = null;
    if (btnNext) btnNext.onclick = null;

    init();
  });
});
