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

  const scrollMessageThreadToBottom = () => {
    const params = new URLSearchParams(window.location.search || "");
    if (params.get("tab") !== "messages" || !params.get("message_to")) {
      return;
    }

    const thread = document.querySelector("[data-message-thread]");
    if (!thread) {
      return;
    }

    window.setTimeout(() => {
      thread.scrollTop = thread.scrollHeight;
    }, 120);
  };

  document.addEventListener("DOMContentLoaded", () => {
    scrollMessageThreadToBottom();
    focusMessageComposer();
  });
})();
