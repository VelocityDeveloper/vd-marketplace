(() => {
  const renderMediaPreview = (preview, items, multiple, emptyText) => {
    if (!preview) return;

    if (!Array.isArray(items) || items.length === 0) {
      preview.innerHTML = `<div class="vmp-media-field__empty text-muted small">${emptyText}</div>`;
      return;
    }

    const gridClass = multiple
      ? "vmp-media-field__grid"
      : "vmp-media-field__grid vmp-media-field__grid--single";

    preview.innerHTML = `
      <div class="${gridClass}">
        ${items
          .map(
            (item) => `
              <div class="vmp-media-field__item" data-id="${Number(item.id || 0)}">
                <img src="${String(item.url || "")}" alt="${String(item.title || "")}" class="vmp-media-field__image">
                <button type="button" class="btn-close vmp-media-field__remove" aria-label="Hapus gambar"></button>
              </div>
            `,
          )
          .join("")}
      </div>
    `;
  };

  const initMediaFields = () => {
    if (!window.wp || !wp.media) return;

    document.querySelectorAll(".vmp-media-field").forEach((field) => {
      const input = field.querySelector(".vmp-media-field__input");
      const preview = field.querySelector(".vmp-media-field__preview");
      const openBtn = field.querySelector(".vmp-media-field__open");
      const clearBtn = field.querySelector(".vmp-media-field__clear");
      const multiple = field.dataset.multiple === "1";
      const emptyText =
        preview && preview.dataset.placeholder
          ? preview.dataset.placeholder
          : "Belum ada gambar dipilih.";

      if (!input || !preview || !openBtn || !clearBtn) return;

      const syncButtons = () => {
        clearBtn.disabled = String(input.value || "").trim() === "";
      };

      openBtn.addEventListener("click", (event) => {
        event.preventDefault();

        const frame = wp.media({
          title: openBtn.dataset.title || "Pilih Media",
          button: {
            text: openBtn.dataset.button || "Gunakan file ini",
          },
          multiple: multiple ? "add" : false,
          library: {
            type: "image",
          },
        });

        frame.on("select", () => {
          const selection = frame.state().get("selection");
          const items = [];
          const ids = [];

          selection.each((attachment) => {
            const data = attachment.toJSON();
            const imageUrl =
              (data.sizes &&
                (data.sizes.medium?.url ||
                  data.sizes.thumbnail?.url ||
                  data.sizes.full?.url)) ||
              data.url ||
              "";

            if (!data.id || !imageUrl) return;

            ids.push(Number(data.id));
            items.push({
              id: Number(data.id),
              url: imageUrl,
              title: data.title || "",
            });
          });

          input.value = multiple ? ids.join(",") : String(ids[0] || "");
          renderMediaPreview(preview, multiple ? items : items.slice(0, 1), multiple, emptyText);
          syncButtons();
        });

        frame.open();
      });

      clearBtn.addEventListener("click", (event) => {
        event.preventDefault();
        input.value = "";
        renderMediaPreview(preview, [], multiple, emptyText);
        syncButtons();
      });

      preview.addEventListener("click", (event) => {
        const removeButton = event.target.closest(".vmp-media-field__remove");
        if (!removeButton) return;

        event.preventDefault();

        const item = removeButton.closest(".vmp-media-field__item");
        if (!item) return;

        const itemId = Number(item.dataset.id || 0);
        if (multiple) {
          const ids = String(input.value || "")
            .split(",")
            .map((value) => Number(value.trim()))
            .filter((value) => value > 0 && value !== itemId);
          input.value = ids.join(",");
        } else {
          input.value = "";
        }

        item.remove();
        if (!preview.querySelector(".vmp-media-field__item")) {
          renderMediaPreview(preview, [], multiple, emptyText);
        }
        syncButtons();
      });

      syncButtons();
    });
  };

  document.addEventListener("DOMContentLoaded", initMediaFields);
})();
