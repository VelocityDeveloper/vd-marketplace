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
- Storage utama:
  - produk: CPT + post meta
  - order: CPT + post meta
  - pesan: custom table
  - cart: cookie / user meta
  - wishlist: user meta
  - profil user umum: user meta
  - pengaturan kurir toko: user meta
  - role marketplace: `vmp_member`

- Skema profil user umum:
  - `vmp_name`
  - `vmp_phone`
  - `vmp_whatsapp`
  - `vmp_address`
  - `vmp_province_id`
  - `vmp_province`
  - `vmp_city_id`
  - `vmp_city`
  - `vmp_subdistrict_id`
  - `vmp_subdistrict`
  - `vmp_postcode`
  - `vmp_description`
  - `vmp_avatar_id`
- Meta khusus seller:
  - `vmp_couriers`

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
- `[vmp_checkout]`
- `[vmp_profile]`
- `[vmp_tracking]`
- `[vmp_store_profile]`

## Halaman default

Installer akan membuat page ini jika belum ada:

- `katalog` -> `[vmp_catalog]`
- `keranjang` -> `[vmp_cart]`
- `checkout` -> `[vmp_checkout]`
- `myaccount` -> `[vmp_profile]`
- `tracking` -> `[vmp_tracking]`
- `toko` -> `[vmp_store_profile]`

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

### `assets/`

- `assets/css/frontend.css`
  - styling frontend umum
  - katalog, single product, checkout, store profile, dll

- `assets/css/dashboard.css`
  - styling dashboard account / seller

- `assets/js/frontend.js`
  - logic Alpine frontend:
  - katalog
  - cart
  - checkout
  - lokasi seller/customer
  - gallery product
  - tombol add to cart / wishlist

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
  - semua action form frontend yang berbasis `POST`/`GET`
  - simpan produk member
  - update order toko
  - upload bukti transfer
  - simpan profil user umum dari form akun/toko
  - wishlist remove
  - notification action
  - kirim pesan

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

- `cart.php`
  - halaman keranjang

- `checkout.php`
  - halaman checkout
  - shipping multi toko
  - prefill dari profil akun

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

- `store-profile.php`
  - profil toko publik
  - tombol pesan
  - daftar produk member

### `templates/account/`

- `orders.php`
  - riwayat belanja member
  - detail invoice
  - upload bukti transfer
  - tracking per toko

- `profile.php`
  - profil akun umum
  - alamat default checkout dan data toko publik

- `wishlist.php`
  - daftar wishlist

- `tracking.php`
  - tracking di dashboard login

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
4. user pilih service ongkir per seller
5. `CheckoutController.php` buat `vmp_order`

### Pesan

1. User masuk dari tombol pesan produk/order/profil toko
2. tab `Pesan` buka thread berdasarkan `message_to`
3. `MessageRepository.php` ambil thread
4. unread thread di-clear saat thread dibuka
5. kirim pesan diproses oleh `Actions.php`

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
