/* Interaksi UI kecil yang tidak bergantung pada satu halaman tertentu. */
(() => {
  const shared = window.VMPFrontend;
  if (!shared) {
    return;
  }

  const { cfg, request, flashButtonLabel } = shared;

  // Mengikat tombol global tambah ke keranjang dan toggle wishlist.
  const initActionButtons = () => {
    document.addEventListener('click', async (event) => {
      const cartButton = event.target.closest('.vmp-action-add-to-cart');
      if (cartButton) {
        event.preventDefault();

        const productId = Number(cartButton.dataset.productId || 0);
        const basic = String(cartButton.dataset.basic || '').trim();
        const advanced = String(cartButton.dataset.advanced || '').trim();
        const options = {};
        if (basic) options.basic = basic;
        if (advanced) options.advanced = advanced;
        if (productId <= 0) return;

        cartButton.disabled = true;
        try {
          await request('cart', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id: productId,
              qty: 1,
              options,
            }),
          });
          flashButtonLabel(cartButton, 'Ditambahkan');
          window.dispatchEvent(new CustomEvent('vmp:cart-updated'));
        } catch (e) {
          flashButtonLabel(cartButton, 'Gagal');
        } finally {
          window.setTimeout(() => {
            cartButton.disabled = false;
          }, 500);
        }
        return;
      }

      const wishlistButton = event.target.closest('.vmp-action-toggle-wishlist');
      if (!wishlistButton) {
        return;
      }

      event.preventDefault();
      if (!cfg.isLoggedIn) {
        flashButtonLabel(wishlistButton, 'Masuk');
        return;
      }

      const productId = Number(wishlistButton.dataset.productId || 0);
      if (productId <= 0) return;

      wishlistButton.disabled = true;
      try {
        const data = await request('wishlist', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: productId }),
        });
        const active = !!data.active;
        wishlistButton.classList.toggle('btn-danger', active);
        wishlistButton.classList.toggle('btn-outline-secondary', !active);
        wishlistButton.setAttribute('aria-pressed', active ? 'true' : 'false');
        flashButtonLabel(wishlistButton, active ? 'Tersimpan' : 'Dihapus');
      } catch (e) {
        flashButtonLabel(wishlistButton, 'Coba Lagi');
      } finally {
        window.setTimeout(() => {
          wishlistButton.disabled = false;
        }, 500);
      }
    });
  };

  // Mengaktifkan galeri produk, carousel thumbnail, dan lightbox image.
  const initProductGallery = () => {
    const galleries = document.querySelectorAll('.vmp-product-gallery');
    if (!galleries.length) return;

    let lightbox = null;
    let lightboxImage = null;
    let lightboxCounter = null;
    let activeGallery = null;
    let activeIndex = 0;

    // Membuat elemen lightbox sekali lalu dipakai ulang oleh semua galeri.
    const ensureLightbox = () => {
      if (lightbox) return;

      const wrapper = document.createElement('div');
      wrapper.className = 'vmp-lightbox';
      wrapper.innerHTML = `
        <div class="vmp-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Galeri Produk">
          <button type="button" class="vmp-lightbox__close" data-lightbox-close aria-label="Tutup">&times;</button>
          <button type="button" class="vmp-lightbox__nav vmp-lightbox__nav--prev" data-lightbox-prev aria-label="Gambar sebelumnya">&#8249;</button>
          <div class="vmp-lightbox__viewport">
            <img src="" alt="" class="vmp-lightbox__image" data-lightbox-image>
          </div>
          <button type="button" class="vmp-lightbox__nav vmp-lightbox__nav--next" data-lightbox-next aria-label="Gambar berikutnya">&#8250;</button>
          <div class="vmp-lightbox__counter" data-lightbox-counter></div>
        </div>
      `;

      document.body.appendChild(wrapper);
      lightbox = wrapper;
      lightboxImage = wrapper.querySelector('[data-lightbox-image]');
      lightboxCounter = wrapper.querySelector('[data-lightbox-counter]');

      wrapper.addEventListener('click', (event) => {
        if (event.target === wrapper || event.target.closest('[data-lightbox-close]')) {
          closeLightbox();
          return;
        }
        if (event.target.closest('[data-lightbox-prev]')) {
          event.preventDefault();
          stepLightbox(-1);
          return;
        }
        if (event.target.closest('[data-lightbox-next]')) {
          event.preventDefault();
          stepLightbox(1);
        }
      });

      document.addEventListener('keydown', (event) => {
        if (!lightbox || !lightbox.classList.contains('is-open')) return;

        if (event.key === 'Escape') {
          closeLightbox();
        } else if (event.key === 'ArrowLeft') {
          stepLightbox(-1);
        } else if (event.key === 'ArrowRight') {
          stepLightbox(1);
        }
      });
    };

    // Mengambil semua gambar dari satu galeri sebagai sumber stage dan lightbox.
    const getImages = (gallery) =>
      Array.from(gallery.querySelectorAll('[data-gallery-link]')).map((link) => ({
        href: String(link.getAttribute('href') || ''),
        title: String(link.textContent || '').trim(),
      }));

    // Menandai thumbnail aktif dan memastikan item aktif tetap terlihat di track.
    const renderActiveThumb = (gallery, index) => {
      gallery.querySelectorAll('[data-gallery-thumb]').forEach((button) => {
        const isActive = Number(button.dataset.index || 0) === index;
        button.classList.toggle('is-active', isActive);
        if (isActive) {
          button.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center',
          });
        }
      });
    };

    // Mengganti gambar utama galeri berdasarkan index thumbnail yang dipilih.
    const setGalleryImage = (gallery, index) => {
      const images = getImages(gallery);
      if (!images.length) return;

      const normalizedIndex = Math.max(0, Math.min(index, images.length - 1));
      const main = gallery.querySelector('[data-gallery-main]');
      if (main) {
        main.src = images[normalizedIndex].href;
        main.alt =
          images[normalizedIndex].title ||
          gallery.dataset.galleryTitle ||
          'Gallery image';
      }

      gallery.dataset.activeIndex = String(normalizedIndex);
      renderActiveThumb(gallery, normalizedIndex);
    };

    // Menyalakan atau mematikan tombol panah thumbnail sesuai posisi scroll.
    const updateThumbNav = (gallery) => {
      const track = gallery.querySelector('[data-gallery-track]');
      const prev = gallery.querySelector('[data-gallery-prev]');
      const next = gallery.querySelector('[data-gallery-next]');
      if (!track || !prev || !next) return;

      const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
      prev.disabled = track.scrollLeft <= 4;
      next.disabled = track.scrollLeft >= maxScroll - 4;
    };

    // Membuka lightbox untuk galeri aktif pada index gambar tertentu.
    const openLightbox = (gallery, index) => {
      ensureLightbox();
      activeGallery = gallery;
      activeIndex = index;
      syncLightbox();
      lightbox.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    };

    // Menutup lightbox dan mengembalikan scroll body normal.
    const closeLightbox = () => {
      if (!lightbox) return;
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    // Menyinkronkan isi lightbox dengan gallery aktif dan index saat ini.
    const syncLightbox = () => {
      if (!activeGallery || !lightboxImage) return;

      const images = getImages(activeGallery);
      if (!images.length) return;

      if (activeIndex < 0) activeIndex = images.length - 1;
      if (activeIndex >= images.length) activeIndex = 0;

      const current = images[activeIndex];
      lightboxImage.src = current.href;
      lightboxImage.alt =
        current.title || activeGallery.dataset.galleryTitle || 'Gallery image';
      if (lightboxCounter) {
        lightboxCounter.textContent = `${activeIndex + 1} / ${images.length}`;
      }
      setGalleryImage(activeGallery, activeIndex);
    };

    // Berpindah ke gambar berikutnya atau sebelumnya di lightbox.
    const stepLightbox = (step) => {
      if (!activeGallery) return;
      activeIndex += step;
      syncLightbox();
    };

    galleries.forEach((gallery) => {
      const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');
      const stage = gallery.querySelector('[data-gallery-open]');
      const track = gallery.querySelector('[data-gallery-track]');
      const prev = gallery.querySelector('[data-gallery-prev]');
      const next = gallery.querySelector('[data-gallery-next]');

      gallery.dataset.activeIndex = gallery.dataset.activeIndex || '0';

      thumbs.forEach((button) => {
        button.addEventListener('click', (event) => {
          event.preventDefault();
          const nextIndex = Number(button.dataset.index || 0);
          setGalleryImage(gallery, nextIndex);
        });
      });

      if (stage) {
        stage.addEventListener('click', (event) => {
          event.preventDefault();
          openLightbox(gallery, Number(gallery.dataset.activeIndex || 0));
        });
      }

      if (track && prev && next) {
        // Menggeser track thumbnail per langkah agar navigasi terasa seperti carousel.
        const moveThumbs = (direction) => {
          const amount = Math.max(180, Math.floor(track.clientWidth * 0.7));
          track.scrollBy({
            left: direction * amount,
            behavior: 'smooth',
          });
        };

        prev.addEventListener('click', (event) => {
          event.preventDefault();
          moveThumbs(-1);
        });

        next.addEventListener('click', (event) => {
          event.preventDefault();
          moveThumbs(1);
        });

        track.addEventListener('scroll', () => updateThumbNav(gallery), {
          passive: true,
        });
        window.addEventListener('resize', () => updateThumbNav(gallery));
        updateThumbNav(gallery);
      }

      setGalleryImage(gallery, Number(gallery.dataset.activeIndex || 0));
    });
  };

  // Menjalankan helper UI ringan setelah DOM siap dipakai.
  document.addEventListener('DOMContentLoaded', () => {
    initActionButtons();
    initProductGallery();
  });
})();
