# Dokumentasi Developer VD Marketplace

Dokumen ini untuk developer. Isinya menjelaskan struktur addon `VD Marketplace`.

## Peran plugin

`VD Marketplace` adalah addon di atas `VD Store`.

Addon ini tidak boleh menjadi core kedua.

Yang ditambah oleh addon:
- seller dashboard
- store profile publik
- shipping per seller
- status fulfillment per seller
- pesan buyer dan seller
- notifikasi seller
- metrik seller
- status `Star Seller`

## Star Seller

Fitur `Star Seller` ada di modul review dan dipusatkan di:
- `src/Modules/Review/StarSellerService.php`
- `src/Modules/Review/StarSellerAdmin.php`

### Rule otomatis

Seller otomatis lolos `Star Seller` jika memenuhi semua rule berikut:
- `completed_orders >= 10`
- `rating_count >= 5`
- `rating_average >= 4.7`
- `cancel_rate <= 5%`

Implementasi konstanta ada di:
- `MIN_COMPLETED_ORDERS = 10`
- `MIN_RATING_AVERAGE = 4.7`
- `MIN_RATING_COUNT = 5`
- `MAX_CANCEL_RATE = 5.0`

### Cara hitung order

Service membaca `store_order`, lalu hanya menghitung order yang memang punya item dari seller itu.

Status yang dipakai:
- `completed` → menambah completed order
- `cancelled` dan `refunded` → menambah cancelled order

Cancel rate dihitung dari:
- `(cancelled_orders / (completed_orders + cancelled_orders)) * 100`

### Cara hitung rating

Rating diambil dari `ReviewRepository::seller_rating_stats($seller_id)`.

Field yang dipakai:
- `rating_average`
- `rating_count`

### Override admin

Admin bisa override hasil otomatis dengan user meta:
- `vmp_star_seller_override = auto`
- `vmp_star_seller_override = force_on`
- `vmp_star_seller_override = force_off`

Meta hasil yang disimpan:
- `vmp_completed_order_count`
- `vmp_cancelled_order_count`
- `vmp_cancel_rate`
- `vmp_rating_average`
- `vmp_rating_count`
- `vmp_star_seller_auto`
- `vmp_is_star_seller`

### Kapan direfresh

Recalculate dipanggil saat:
- review seller / produk berubah
- order seller diperbarui
- admin melakukan override

## Dependency

Plugin ini wajib membutuhkan `VD Store` aktif.

Bootstrap dependency ada di:
- `velocity-marketplace.php`

## Identitas seller

Akun marketplace memakai:
- role: `vd_member`
- flag seller aktif: `_store_is_seller`

Prinsipnya:
- `vd_member` berarti akun ada di ekosistem marketplace
- hak jual tidak otomatis aktif
- hak jual ditentukan oleh `_store_is_seller = 1`

## Data inti yang mengikuti VD Store

Addon membaca kontrak inti dari `VD Store`, terutama:
- CPT produk: `store_product`
- CPT order: `store_order`
- CPT kupon: `store_coupon`
- taxonomy kategori: `store_product_cat`
- meta inti produk
- meta inti kupon
- cart dasar
- wishlist dasar

Jangan buat source utama kedua untuk domain-domain itu.

## File penting

### Bootstrap dan core
- `velocity-marketplace.php`
- `src/Core/Plugin.php`
- `src/Frontend/Assets.php`
- `src/Support/Settings.php`

Fungsi publik kecil yang memang sengaja dibuat untuk dipakai theme, snippet, atau builder diletakkan sebagai wrapper di:
- `velocity-marketplace.php`

Contoh:
- `vmp_is_premium_product($post_id)`
- `vmp_premium_badge_html($args = [])`

### Seller account dan profile
- `src/Modules/Account/Account.php`
- `src/Modules/Profile/ProfileService.php`
- `templates/seller/profile.php`
- `templates/seller/home.php`
- `templates/seller/products.php`

### Cart, shipping, checkout
- `src/Modules/Cart/CartRepository.php`
- `src/Modules/Shipping/ShippingController.php`
- `src/Modules/Checkout/CheckoutController.php`
- `templates/cart.php`
- `templates/checkout.php`
- checkout membaca mode shipping dari core:
  - `normal` = ongkir dihitung seperti biasa
  - `free` = layanan tetap muncul, biaya dipaksa `0`
  - `off` = ongkir disembunyikan
- alamat checkout beserta dropdown provinsi, kota, dan kecamatan tetap dikumpulkan jika `collect_address` aktif di core
- COD hanya ditampilkan jika `shippingAllowCod` aktif dan mode shipping bukan `off`
- frontend menerima `shippingMode`, `shippingCollectAddress`, `shippingAllowCod`, dan `shippingDisabled` lewat `vmpSettings`

COD marketplace:
- profil seller menyimpan `vmp_cod_enabled`, `vmp_cod_city_ids`, `vmp_cod_city_names`
- `ShippingController` membawa metadata COD seller ke checkout context
- frontend menambahkan `courier = cod` ke pilihan pengiriman masing-masing seller yang melayani kota tujuan
- buyer dapat memilih COD untuk satu seller dan kurir reguler untuk seller lain dalam checkout yang sama
- `CheckoutController` memvalidasi kelayakan COD per shipping group dan tidak mempercayai biaya dari payload
- COD tetap boleh dipakai saat ongkir gratis, tetapi tidak saat mode shipping `off`
- `vmp_total` tetap menyimpan nilai seluruh order; `vmp_pay_now_total` menyimpan nominal pembayaran global dan `vmp_cod_due_total` menyimpan nominal yang dibayar saat diterima
- diskon produk dan ongkir dialokasikan proporsional ke shipping group agar `vmp_total = vmp_pay_now_total + vmp_cod_due_total`
- status awal grup COD adalah `processing`; grup prepaid mengikuti status pembayaran global
- stok grup COD dikurangi saat order dibuat, sedangkan stok grup prepaid dikurangi setelah pembayaran terverifikasi

### Coupon, order, payment
- `src/Modules/Coupon/CouponService.php`
- `src/Modules/Order/OrderData.php`
- `src/Modules/Payment/DuitkuCallbackListener.php`
- `src/Modules/Email/EmailTemplateService.php`
- `src/Core/SettingsPage.php`
- `src/Core/SettingsService.php`

### Produk premium
- `src/Modules/Product/PremiumBadge.php`
- `src/Modules/Product/PremiumRequestAdmin.php`

Fungsi file:
- `PremiumBadge.php`
  - sumber utama untuk cek apakah produk premium
  - sumber utama untuk render badge premium
  - dipakai oleh fungsi global dan shortcode
- `PremiumRequestAdmin.php`
  - halaman admin untuk review pengajuan premium seller
  - aksi setuju/tolak pengajuan premium
  - menampilkan count pengajuan di submenu admin

### Email marketplace
- `EmailTemplateService.php`
  - render email admin dan pembeli
  - wrapper HTML email
  - header `From` dan `Reply-To`
  - tabel detail order, pembeli, dan rekening
- `SettingsPage.php`
  - halaman pengaturan email marketplace
- `SettingsService.php`
  - sanitize dan payload setting email marketplace

### Pesan dan notifikasi
- `src/Modules/Message/MessageController.php`
- `src/Modules/Notification/NotificationController.php`

## Frontend JS map

### `assets/js/frontend-shared.js`
Helper umum frontend:
- request REST
- format uang
- helper cart
- helper wilayah
- helper captcha

### `assets/js/frontend-cart.js`
Untuk halaman cart marketplace.

### `assets/js/frontend-checkout.js`
Untuk checkout marketplace.

Tanggung jawab utamanya:
- alamat tujuan
- shipping per toko
- kupon
- cart digital-only vs fisik
- submit order

### `assets/js/frontend-profile.js`
Untuk profil member dan profil toko.

### `assets/js/frontend-ui.js`
Untuk helper UI global:
- add to cart
- wishlist
- galeri produk
- lightbox

### `assets/js/media.js`
Untuk form seller:
- media library
- field produk kondisional fisik/digital
- popup validasi field wajib

## URL customer-facing order

URL order customer sekarang dipusatkan ke tracking publik.

Helper utama:
- `Settings::customer_order_url($invoice)`

Format default:
- `/tracking-order/?order=INVOICE`

Helper ini dipakai untuk:
- redirect selesai checkout
- notifikasi customer
- email order
- link order customer

## Aturan shipping penting

### Cart digital-only
- tidak meminta ongkir
- tidak membentuk shipping group

### Cart fisik-only
- wajib alamat tujuan
- wajib layanan pengiriman

### Cart campuran
- item digital tetap ikut order
- shipping hanya dihitung dari item fisik

### Mode shipping
- `normal` mengikuti kalkulasi ongkir seller
- `free` tetap membentuk shipping group tapi semua biaya jadi `0`
- `off` menghilangkan shipping group dan hanya menyisakan data alamat plus dropdown lokasi jika core meminta alamat
- `collect_address` tidak mematikan alamat pada mode `normal`; produk fisik tetap wajib alamat dan lokasi tujuan

## Form seller produk

Form seller produk mengikuti schema dari core `VD Store`.

Artinya:
- field tidak didefinisikan ulang manual di addon
- validasi server tetap mengikuti `ProductSchema` dan `ProductFields` dari core
- popup validasi di frontend hanya lapisan UX

## Shortcode yang penting

### Produk dan tampilan produk
- `vmp_products`
  - grid produk marketplace
- `vmp_product_card`
  - card satu produk
- `vmp_product_gallery`
  - galeri produk, delegasi ke core
- `vmp_product_reviews`
  - ulasan produk, delegasi ke core
- `vmp_product_seller_card`
  - info seller pada single produk
- `vmp_premium_badge`
  - badge produk premium
  - atribut:
    - `post_id`
    - `text`
    - `class`

### Interaksi produk
- `vmp_add_to_cart`
  - tombol tambah ke keranjang
  - jika VD Store aktif, tombol ini mendelegasikan render ke `wp_store_add_to_cart_button()` supaya opsi produk, minimal order, dan modal add-to-cart tetap satu jalur
- `vmp_add_to_wishlist`
  - tombol tambah ke wishlist
- `vmp_rating`
  - ringkasan rating
- `vmp_review_count`
  - jumlah ulasan
- `vmp_sold_count`
  - jumlah terjual

### Cart, checkout, account
- `vmp_cart`
- `vmp_cart_page`
- `vmp_checkout`
- `vmp_profile`
- `vmp_tracking`
- `vmp_store_profile`

## Fungsi publik yang penting

### `vmp_is_premium_product($post_id = 0)`
Fungsinya:
- cek apakah produk premium
- return `true` atau `false`

Dipakai saat:
- theme ingin menentukan style produk premium
- builder/snippet ingin menampilkan elemen berbeda untuk produk premium

### `vmp_premium_badge_html($args = [])`
Fungsinya:
- render HTML badge premium yang siap dipakai di template

Argumen:
- `post_id`
  - id produk
- `text`
  - teks badge
- `class`
  - class HTML badge

Contoh:

```php
echo vmp_premium_badge_html([
    'post_id' => $post_id,
    'text' => 'Premium',
    'class' => 'badge bg-warning text-dark',
]);
```

Kalau produk bukan premium:
- output kosong

## Area yang paling sensitif saat diubah

Kalau mengubah area ini, tes ulang end-to-end:
- aktivasi seller
- tambah/edit produk seller
- cart campuran fisik + digital
- checkout multi-seller
- shipping per toko
- kupon produk dan ongkir
- status order seller
- redirect ke tracking order
- pesan dan notifikasi seller

## Versi saat ini

- plugin version: `1.0.2`
- constant: `VMP_VERSION`
