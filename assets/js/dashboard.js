(() => {
  const focusMessageComposer = () => {
    const params = new URLSearchParams(window.location.search || "");
    if (params.get("tab") !== "messages") {
      return;
    }

    const recipient = params.get("message_to");
    if (!recipient) {
      return;
    }

    const field = document.querySelector("textarea[name='message']");
    if (!field) {
      return;
    }

    window.setTimeout(() => {
      field.focus();
    }, 120);
  };

  document.addEventListener("DOMContentLoaded", focusMessageComposer);
})();
