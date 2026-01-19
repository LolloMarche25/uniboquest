document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.querySelector(".ubq-transition");
  if (!overlay) return;

  document.querySelectorAll("a[data-ubq-go]").forEach((a) => {
    a.addEventListener("click", (e) => {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      e.preventDefault();

      const url = a.getAttribute("data-ubq-go") || a.getAttribute("href");
      overlay.classList.add("is-on");

      window.setTimeout(() => {
        window.location.href = url;
      }, 900);
    });
  });
});
