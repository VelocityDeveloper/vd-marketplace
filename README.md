# Velocity Marketplace

Plugin marketplace WordPress berbasis REST API + Alpine.js.

Status saat ini:
- versi plugin publik masih `1.0.0`
- masih tahap pembuatan awal
- banyak bagian belum final
- belum dirilis
- dibuat dari 0, tanpa kebutuhan migrasi/legacy compatibility

Dokumen ini adalah catatan kerja untuk developer. Kalau ada perubahan struktur, alur, nama shortcode, atau file baru, README ini harus ikut diperbarui supaya orang berikutnya tidak menebak-nebak arsitektur plugin.

## Standar saat ini

- Prefix shortcode: `vmp_*`
- Post type produk: `vmp_product`
- Post type order: `vmp_order`
- Taxonomy kategori produk: `vmp_product_cat`
- Option settings: `vmp_settings`
- Option pages: `vmp_pages`
- Option db version: `vmp_db_version`
- Penyimpanan pesan: custom table `wp_vmp_messages`
- Penyimpanan ulasan: custom table `wp_vmp_reviews`
- Storage utama:
  - produk: CPT + post meta
  - order: CPT + post meta
  - kupon: CPT + post meta
  - pesan: custom table
  - ulasan: custom table
  - cart: cookie / user meta
  - wishlist: user meta
  - profil user umum: user meta
  - pengaturan kurir toko: user meta
  - role marketplace: `vmp_member`
  - badge star seller: user meta hasil evaluasi otomatis

- Skema profil member:
  - `first_name` / `display_name` / `nickname` (WordPress core)
  - `vmp_member_phone`
  - `vmp_member_address`
  - `vmp_member_province_id`
  - `vmp_member_province`
  - `vmp_member_city_id`
  - `vmp_member_city`
  - `vmp_member_subdistrict_id`
  - `vmp_member_subdistrict`
  - `vmp_member_postcode`
- Meta khusus seller:
  - `vmp_store_name`
  - `vmp_store_phone`
  - `vmp_store_whatsapp`
  - `vmp_store_address`
  - `vmp_store_province_id`
  - `vmp_store_province`
  - `vmp_store_city_id`
  - `vmp_store_city`
  - `vmp_store_subdistrict_id`
  - `vmp_store_subdistrict`
  - `vmp_store_postcode`
  - `vmp_store_description`
  - `vmp_store_avatar_id`
  - `vmp_couriers`
  - `vmp_cod_enabled`
  - `vmp_cod_city_ids`
  - `vmp_cod_city_names`

## Shortcode resmi

Pakai satu standar ini saja. Jangan tambah alias baru kecuali memang ada alasan kuat.

- `[vmp_catalog]`
- `[vmp_products]`
- `[vmp_product_card]`
- `[vmp_thumbnail]`
- `[vmp_price]`
- `[vmp_add_to_cart]`
- `[vmp_add_to_wishlist]`
- `[vmp_cart]`
- `[vmp_cart_page]`
- `[vmp_checkout]`
- `[vmp_profile]`
- `[vmp_tracking]`
- `[vmp_store_profile]`

## Halaman default

Installer akan membuat page ini jika belum ada:

- `catalog` -> `[vmp_catalog]`
- `cart` -> `[vmp_cart_page]`
- `checkout` -> `[vmp_checkout]`
- `account` -> `[vmp_profile]`
- `order-tracking` -> `[vmp_tracking]`
- `store` -> `[vmp_store_profile]`

## Struktur folder

### Root

- `velocity-marketplace.php`
  - bootstrap plugin
  - define konstanta `VMP_VERSION`, `VMP_PATH`, `VMP_URL`
  - autoload class dari `src`
  - activation / deactivation hook

- `README.md`
  - catatan struktur, shortcode, dan alur plugin
  - wajib ikut diupdate kalau struktur berubah

- `README-FRONTEND.md`
  - peta file JavaScript frontend dan custom admin page
  - titik awal kalau ingin memahami asset `Alpine.js + REST API`

### `assets/`

- `assets/css/frontend.css`
  - styling frontend umum
  - katalog, single product, checkout, store profile, dll

- `assets/css/dashboard.css`
  - styling dashboard account / seller

- `assets/js/frontend-shared.js`
  - helper shared frontend
  - request REST, formatter, helper wilayah, helper cart

- `assets/js/frontend-catalog.js`
  - logic katalog dan advance filter

- `assets/js/frontend-cart.js`
  - logic halaman keranjang

- `assets/js/frontend-checkout.js`
  - logic checkout, shipping, coupon, dan submit order

- `assets/js/frontend-profile.js`
  - logic profil member dan profil toko

- `assets/js/frontend-ui.js`
  - helper UI lintas halaman
  - add to cart/wishlist global
  - galeri produk

- `assets/js/frontend.js`
  - placeholder legacy internal
  - tidak dipakai sebagai asset aktif

- `assets/js/dashboard.js`
  - behavior ringan dashboard
  - focus composer pesan
  - auto scroll thread pesan ke bawah

- `assets/js/media.js`
  - integrasi WordPress media library untuk frontend seller
  - featured image produk
  - gallery produk
  - avatar toko

- `assets/img/no-image.webp`
  - fallback image default

### `src/Core/`

- `Plugin.php`
  - pusat boot plugin
  - load core, API, dan frontend

- `Installer.php`
  - create role
  - create default pages
  - seed default settings

- `PostTypes.php`
  - register taxonomy `vmp_product_cat`
  - register CPT `vmp_product`
  - register CPT `vmp_order`

- `SettingsPage.php`
  - halaman pengaturan plugin di wp-admin
  - currency, payment method, status default, API key ongkir

- `Upgrade.php`
  - version gate upgrade
  - jalankan installer
  - create message table
  - create review table

### `src/Frontend/`

- `Assets.php`
  - enqueue CSS/JS
  - localize `vmpSettings`
  - deteksi shortcode page untuk memutuskan asset mana yang dimuat

- `Shortcode.php`
  - daftar semua shortcode resmi `vmp_*`
  - render halaman page-level
  - render blok produk reusable

- `Template.php`
  - helper locate dan render template
  - override archive/single produk default plugin

### `src/Modules/Account/`

- `Account.php`
  - login/register integration ke halaman bawaan WordPress
  - assign role tunggal `vmp_member` saat register
  - helper cek member marketplace dan akses jual
  - logout frontend

- `Actions.php`
  - router tipis untuk action frontend berbasis `POST`/`GET`
  - delegasi ke handler per domain

- `Handlers/`
  - `BaseActionHandler.php`
    - helper common redirect, notice, sanitasi dasar
  - `ProductActionHandler.php`
    - simpan/hapus produk member
  - `OrderActionHandler.php`
    - update order toko
    - upload bukti transfer
  - `ProfileActionHandler.php`
    - fallback submit profil via form klasik
  - `WishlistActionHandler.php`
    - hapus item wishlist
  - `NotificationActionHandler.php`
    - aksi notifikasi
  - `MessageActionHandler.php`
    - kirim pesan
  - `ReviewActionHandler.php`
    - submit ulasan dan upload foto review

### `src/Modules/Captcha/`

- `CaptchaBridge.php`
  - bridge ke plugin `velocity-addons`
  - dipakai untuk render / verify captcha

- `CaptchaController.php`
  - REST endpoint captcha jika dibutuhkan oleh frontend

### `src/Modules/Cart/`

- `CartController.php`
  - REST API cart

- `CartRepository.php`
  - persistence cart ke user meta / cookie
  - hydrate item cart

### `src/Modules/Checkout/`

- `CheckoutController.php`
  - REST API checkout
  - validasi payload
  - buat order `vmp_order`
  - validasi kupon
  - validasi COD per kota per toko

### `src/Modules/Coupon/`

- `CouponAdmin.php`
  - metabox dan kolom admin untuk kupon/voucher
  - kupon disimpan sebagai CPT `vmp_coupon`

- `CouponController.php`
  - REST preview kupon saat checkout

- `CouponService.php`
  - cari kupon berdasarkan kode
  - validasi minimal belanja, periode aktif, dan batas penggunaan
  - hitung diskon nominal / persen
  - simpan shipping groups
  - reduce stock
  - notifikasi order

### `src/Modules/Message/`

- `MessageRepository.php`
  - simpan pesan
  - ambil daftar kontak
  - ambil thread per kontak
  - unread per kontak
  - mark thread read
  - validasi bisa chat atau tidak

- `MessageTable.php`
  - create table `wp_vmp_messages`

### `src/Modules/Notification/`

- `NotificationRepository.php`
  - notifikasi internal selain chat
  - order, pembayaran, premium, dll

Catatan:
- pesan baru sengaja tidak lagi masuk notification repository untuk meringankan sistem

### `src/Modules/Review/`

- `ReviewAdmin.php`
  - halaman wp-admin untuk moderasi ulasan
  - setujui, sembunyikan, hapus ulasan

- `ReviewRepository.php`
  - simpan ulasan produk
  - validasi verified purchase
  - agregat rating produk
  - agregat rating seller
  - simpan foto review

- `ReviewTable.php`
  - create table `wp_vmp_reviews`

- `StarSellerAdmin.php`
  - override manual star seller di edit user wp-admin
  - mode: otomatis / paksa aktif / paksa nonaktif

- `StarSellerService.php`
  - hitung badge star seller dari:
  - order selesai
  - rating rata-rata
  - jumlah ulasan minimum
  - cancel rate

### `src/Modules/Order/`

- `OrderAdmin.php`
  - wp-admin UI untuk `vmp_order`
  - list column
  - metabox detail order
  - pengiriman per seller
  - edit kurir/resi per seller

- `OrderData.php`
  - helper status order
  - helper query order seller
  - helper shipping groups
  - helper seller items

### `src/Modules/Product/`

- `ProductController.php`
  - REST API daftar / detail produk
  - filter produk:
  - nama
  - kategori
  - label
  - rentang harga
  - jenis toko
  - urutan harga / nama / populer

- `ProductData.php`
  - mapper data produk untuk frontend/API
  - fallback image
  - gallery
  - harga aktif
  - opsi produk

- `ProductFields.php`
  - schema field produk
  - register meta produk
  - render/save field reusable

- `ProductMetaBox.php`
  - admin metabox produk
  - memakai schema dari `ProductFields`

### `src/Modules/Shipping/`

- `ShippingController.php`
  - API lokasi: provinsi, kota, kecamatan
  - hitung ongkir
  - waybill / tracking resi
  - shipping context multi seller
  - integrasi layanan wilayah, ongkir, dan pelacakan pengiriman
  - expose data COD per kota dari toko

### `src/Modules/Wishlist/`

- `WishlistController.php`
  - REST API wishlist

- `WishlistRepository.php`
  - simpan / ambil wishlist user

### `src/Support/`

- `Settings.php`
  - helper baca settings plugin
  - helper URL profile dan store profile
  - courier label

## Struktur template

### `templates/`

- `catalog.php`
  - halaman katalog utama
  - advance filter produk

- `cart.php`
  - halaman keranjang

- `checkout.php`
  - halaman checkout
  - shipping multi toko
  - prefill dari profil akun
  - kupon / voucher
  - COD per kota per toko

- `profile.php`
  - router dashboard account
  - menentukan tab yang dirender

- `tracking.php`
  - tracking publik via invoice

- `archive-product.php`
  - archive default `vmp_product`

- `single-product.php`
  - single product default
  - gallery product
  - tombol add to cart / wishlist
  - tombol profil toko / pesan seller
  - ringkasan rating produk
  - daftar ulasan produk

- `store-profile.php`
  - profil toko publik
  - tombol pesan
  - info toko:
  - alamat pengiriman
  - kota COD
  - ulasan toko
  - terakhir aktif
  - total produk
  - tanggal bergabung
  - daftar produk member

### `templates/account/`

- `orders.php`
  - riwayat belanja member
  - detail invoice
  - upload bukti transfer
  - tracking per toko
  - form ulasan produk setelah order selesai
  - upload foto review

- `profile.php`
  - profil akun umum
  - alamat default checkout dan data toko publik

- `wishlist.php`
  - daftar wishlist

- `tracking.php`
  - tracking di dashboard login
  - detail pembayaran
  - upload bukti transfer dari menu tracking

- `messages.php`
  - inbox pesan per kontak/thread

- `notifications.php`
  - daftar notifikasi sistem

### `templates/seller/`

- `home.php`
  - dashboard order toko
  - update status, resi, catatan toko

- `products.php`
  - tambah/edit/hapus produk member

- `profile.php`
  - pengaturan toko
  - avatar, kurir, deskripsi

- `report.php`
  - laporan toko

## Alur sistem ringkas

### Produk

1. Member input produk dari dashboard
2. `Actions.php` simpan post `vmp_product`
3. `ProductFields.php` simpan meta produk
4. `ProductController.php` expose produk ke katalog/frontend

### Cart

1. User klik add to cart
2. `frontend.js` hit REST cart
3. `CartController.php` proses request
4. `CartRepository.php` simpan item

### Checkout

1. User buka checkout
2. `frontend.js` load cart + shipping context
3. alamat default diisi dari profil customer
4. user bisa pakai kupon bila valid
5. user pilih service ongkir per seller atau COD jika tersedia di kota tujuan
6. `CheckoutController.php` buat `vmp_order`

### Pesan

1. User masuk dari tombol pesan produk/order/profil toko
2. tab `Pesan` buka thread berdasarkan `message_to`
3. `MessageRepository.php` ambil thread
4. unread thread di-clear saat thread dibuka
5. kirim pesan diproses oleh `Actions.php`

### Review dan Star Seller

1. Member hanya bisa memberi ulasan dari order miliknya yang statusnya `completed`
2. Satu produk hanya punya satu ulasan per user per order
3. Ulasan masuk ke table `wp_vmp_reviews`
4. Setelah ulasan masuk, meta agregat produk diperbarui:
   - `vmp_review_count`
   - `vmp_rating_average`
5. Ulasan bisa menyimpan sampai 3 foto review
6. Setelah ulasan masuk atau status order berubah, `StarSellerService` hitung ulang badge seller
7. Admin bisa override hasil star seller tanpa mematikan hitung otomatis

## Catatan maintenance

- Jangan tambah alias shortcode baru tanpa alasan kuat.
- Kalau ada file baru atau modul baru, update README ini.
- Kalau ada rename folder/file, update bagian struktur di README.
- Kalau ada perubahan alur checkout, shipping, message, atau dashboard, update bagian `Alur sistem ringkas`.
- Kalau storage berubah, misalnya order pindah ke custom table, update bagian `Standar saat ini`.

## Catatan perubahan yang perlu diperhatikan developer berikutnya

- Prefix shortcode final saat ini: `vmp_*`
- Role marketplace final saat ini: `vmp_member`
- Pesan tidak lagi membuat notifikasi internal
- Tracking publik tersedia lewat page tracking
- Message memakai custom table, bukan CPT
- Order title baru tidak lagi memakai prefix kata `Order`
- Meta profil user sekarang disatukan:
  - semua member membaca key yang sama
  - hanya `vmp_couriers` yang khusus toko
- Meta WordPress inti seperti `first_name` dan `display_name` tetap dipakai apa adanya, tidak diprefix ulang
