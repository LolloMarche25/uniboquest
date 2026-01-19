document.addEventListener("DOMContentLoaded", () => {
  const book = document.getElementById("manuale");
  const pages = Array.from(document.querySelectorAll(".page"));
  if (!book || pages.length === 0) return;

  const mqDesktop = window.matchMedia("(min-width: 768px)");

  function setupDesktopFlip() {
    pages.forEach((page, index) => {
      page.style.zIndex = String(pages.length - index);

      page.onclick = () => {
        const flipped = page.classList.contains("flipped");
        
        if (!flipped) {
          page.classList.add("flipped");
          if (index === 0) book.classList.remove("closed");
          
          setTimeout(() => {
            page.style.zIndex = String(index + 1);
          }, 600);
        } else {
          page.classList.remove("flipped");
          if (index === 0) book.classList.add("closed");
          
          setTimeout(() => {
            page.style.zIndex = String(pages.length - index);
          }, 600);
        }
      };
    });
  }

  // --- MOBILE ---
  let current = 0;
  const btnPrev = document.querySelector("[data-manual-prev]");
  const btnNext = document.querySelector("[data-manual-next]");
  const indicator = document.querySelector("[data-manual-indicator]");

  function renderMobile() {
    const total = pages.length * 2;
    pages.forEach(p => {
      p.classList.add("manual-mobile");
      p.dataset.active = "false";
    });
    const pageIndex = Math.floor(current / 2);
    const face = current % 2;
    if (pages[pageIndex]) {
      pages[pageIndex].dataset.active = "true";
      pages[pageIndex].dataset.face = String(face);
    }
    if (indicator) indicator.textContent = `Pagina ${current + 1} / ${total}`;
  }

  function init() {
    pages.forEach(p => { p.onclick = null; p.classList.remove("manual-mobile"); });
    if (mqDesktop.matches) {
      setupDesktopFlip();
    } else {
      renderMobile();
      if (btnPrev) btnPrev.onclick = () => { current = Math.max(0, current - 1); renderMobile(); };
      if (btnNext) btnNext.onclick = () => { current = Math.min(pages.length * 2 - 1, current + 1); renderMobile(); };
    }
  }

  init();
  mqDesktop.addEventListener("change", init);
});