(function () {
  const VALID_EMAIL_REGEX = /^[a-zA-Z0-9._%+-]+@etu\.uae\.ac\.ma$/;

  function ensureContainer() {
    let c = document.getElementById("toastContainer");
    if (!c) {
      c = document.createElement("div");
      c.id = "toastContainer";
      c.className = "toast-container";
      document.body.appendChild(c);
    }
    return c;
  }

  function toast(message, type) {
    const container = ensureContainer();
    const el = document.createElement("div");
    el.className = "toast " + (type || "info");
    el.textContent = message;
    container.appendChild(el);
    requestAnimationFrame(() => el.classList.add("show"));
    setTimeout(() => {
      el.classList.remove("show");
      setTimeout(() => el.remove(), 280);
    }, 3500);
  }

  window.toast = toast;
  window.toastSuccess = (m) => toast(m, "success");
  window.toastError = (m) => toast(m, "error");
  window.toastInfo = (m) => toast(m, "info");
  window.isValidUaeEmail = (mail) => VALID_EMAIL_REGEX.test(mail);
})();
