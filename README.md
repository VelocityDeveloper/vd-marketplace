# VD Marketplace

Versi: `1.0.10`

`VD Marketplace` adalah addon marketplace untuk `VD Store`.

Plugin ini dipakai jika toko online ingin punya:
- seller
- toko publik per seller
- dashboard seller
- order per seller
- pengiriman per toko
- pesan buyer dan seller
- notifikasi seller

## Syarat utama

`VD Marketplace` wajib dipakai bersama `VD Store`.

Kalau `VD Store` tidak aktif:
- addon tidak akan berjalan

## Fungsi utama

- dashboard seller di halaman profil customer
- profil toko seller
- halaman toko publik seller
- checkout multi-seller
- shipping per toko
- kompatibilitas mode ongkir VD Store: normal, gratis ongkir, dan nonaktif
- COD per seller berbasis area kota/kabupaten
- status order seller
- pesan buyer dan seller
- notifikasi seller
- template email marketplace untuk admin dan pembeli
- badge dan filter `Star Seller`

## COD per seller

COD tersedia sebagai pilihan pengiriman pada kartu seller jika seller aktif melayani kota tujuan. Satu checkout dapat menggabungkan seller COD dan seller prepaid.

- `Total Pesanan` tetap mencakup seluruh grup seller
- `Bayar Sekarang` hanya mencakup grup non-COD
- `Bayar saat diterima` mencakup grup dengan pengiriman COD
- payment method global hanya digunakan untuk nilai `Bayar Sekarang`

## Star Seller

Fitur `Star Seller` sudah tersedia.

Status ini dihitung otomatis dari performa seller, lalu bisa dioverride manual oleh admin.

### Syarat lolos otomatis

Seller harus memenuhi semua syarat ini:
- minimal `10` order selesai
- minimal `5` rating
- rata-rata rating minimal `4.7`
- rasio order `cancelled + refunded` maksimal `5%`

### Cara hitung

- order selesai dihitung dari status `completed`
- order gagal dihitung dari status `cancelled` dan `refunded`
- cancel rate dihitung dari total order final:
  - `completed + cancelled + refunded`
- rating seller diambil dari review seller yang tersimpan di marketplace

### Override admin

Admin bisa mengatur mode:
- `auto`
- `force_on`
- `force_off`

### Lokasi tampilan

Badge `Star Seller` dipakai di:
- kartu seller pada produk
- halaman toko publik seller
- dashboard seller
- filter produk berdasarkan tipe toko

## Cara pakai singkat

1. Aktifkan `VD Store`
2. Aktifkan `VD Marketplace`
3. Atur halaman marketplace yang dibutuhkan
4. Aktifkan akun seller dari `Profil Toko`
5. Tambahkan produk seller dari dashboard seller

## Perilaku penting

- role member marketplace: `vd_member`
- seller aktif ditentukan oleh meta:
  - `_store_is_seller`
- URL toko publik seller memakai `user_login`, contoh:
  - `/store/namauser/`
- setelah checkout, customer diarahkan ke tracking publik:
  - `/tracking-order/?order=INVOICE`

## Customer checkout

- cart digital-only tidak meminta ongkir
- cart campuran fisik + digital tetap didukung
- ringkasan checkout menampilkan thumbnail produk
- ringkasan checkout menampilkan opsi yang dipilih di bawah nama setiap produk
- blok pengiriman per toko memakai nama toko, bukan nama user

## Shortcode utama

- `[vmp_products]`
- `[vmp_product_card]`
- `[vmp_product_gallery]`
- `[vmp_product_reviews]`
- `[vmp_product_seller_card]`
- `[vmp_premium_badge]`
- `[vmp_recently_viewed]`
- `[vmp_product_filter]`
- `[vmp_rating]`
- `[vmp_add_to_cart]`
- `[vmp_add_to_wishlist]`
- `[vmp_cart]`
- `[vmp_checkout]`
- `[vmp_profile]`
- `[vmp_tracking]`
- `[vmp_store_profile]`

Catatan: `[vmp_add_to_cart]` otomatis memakai tombol VD Store jika plugin VD Store aktif. Jadi opsi produk, minimal order, dan modal pilihan tetap memakai jalur cart yang sama.

## Helper dan shortcode tambahan

### Badge produk premium

Kalau produk sudah disetujui sebagai premium, kamu bisa tampilkan badge dengan:

- fungsi PHP:
  - `vmp_is_premium_product($post_id)`
  - `vmp_premium_badge_html([...])`
- shortcode:
  - `[vmp_premium_badge]`

Contoh fungsi PHP:

```php
if (vmp_is_premium_product($post_id)) {
    echo vmp_premium_badge_html([
        'post_id' => $post_id,
        'text' => 'Premium',
        'class' => 'badge bg-warning text-dark',
    ]);
}
```

Contoh shortcode:

```text
[vmp_premium_badge post_id="123" text="Produk Premium" class="badge bg-warning text-dark"]
```

Parameter yang didukung:
- `post_id`
- `text`
- `class`

## Catatan

- `VD Marketplace` tidak menggantikan `VD Store`
- data inti produk, order, cart, wishlist, dan kupon tetap mengikuti core `VD Store`
- pengaturan email marketplace tersedia di menu `Pengaturan Marketplace`
- dokumentasi teknis tersedia di bagian [Dokumentasi developer](#dokumentasi-developer) di bawah.

## Dokumentasi developer

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

Dashboard seller memisahkan daftar dan form produk:
- tab `Produk` menampilkan daftar, edit, dan hapus produk
- tab `Tambah Produk` menampilkan form tambah atau edit produk
- setelah simpan, seller kembali ke tab `Produk`; validasi gagal tetap kembali ke form

Artinya:
- field tidak didefinisikan ulang manual di addon
- validasi server tetap mengikuti `ProductSchema` dan `ProductFields` dari core
- popup validasi di frontend hanya lapisan UX
- field **Jenis Harga** dari VD Store otomatis tersedia pada form seller
- **Tambahan Harga** menjumlahkan angka pilihan dengan harga produk
- **Harga Tetap** memakai angka pilihan sebagai harga akhir
- keranjang marketplace menyerahkan kalkulasi harga pilihan kepada VD Store agar subtotal dan total per seller tetap konsisten

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

- plugin version: `1.0.10`
- constant: `VMP_VERSION`

## Contoh hook field checkout

Contoh ini langsung menambahkan **Nama Perusahaan**, **Jenis Pembeli**, dan
**Konfirmasi Data** di bagian checkout yang sesuai. Nilainya otomatis disimpan dan
bisa dilihat pada editor pesanan WordPress. Tidak perlu menambah kode JavaScript
atau kode penyimpanan sendiri.

### Pasang dalam 3 langkah

1. Buat folder `checkout-tambahan` di `wp-content/plugins/`.
2. Buat file `checkout-tambahan.php` di dalam folder tersebut, lalu salin **seluruh kode** berikut.
3. Buka **WordPress Admin ? Plugin**, lalu aktifkan **Checkout Tambahan**.

Fitur field tambahan beserta pilihan section tersedia mulai **VD Store 1.4.7**
dan **Velocity Marketplace 1.0.6**. Jika memakai marketplace, gunakan kedua versi
tersebut atau yang lebih baru. Contoh cukup dipasang sekali
untuk checkout VD Store maupun marketplace.

```php
<?php
/**
 * Plugin Name: Checkout Tambahan
 * Description: Menambahkan field checkout VD Store dan Velocity Marketplace.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_store_checkout_fields', function ($fields) {
    // Isian teks, boleh dikosongkan.
    $fields['nama_perusahaan'] = [
        'type'        => 'text',
        'label'       => 'Nama Perusahaan',
        'placeholder' => 'Contoh: PT Maju Bersama',
        'section'     => 'customer',
        'required'    => false,
        'priority'    => 10,
    ];

    // Pilihan yang wajib diisi pembeli.
    $fields['jenis_pembeli'] = [
        'type'        => 'select',
        'label'       => 'Jenis Pembeli',
        'placeholder' => 'Pilih jenis pembeli',
        'section'     => 'customer',
        'required'    => true,
        'priority'    => 20,
        'options'     => [
            'pribadi'    => 'Pribadi',
            'perusahaan' => 'Perusahaan',
        ],
    ];

    // Kotak centang yang wajib dicentang sebelum memesan.
    $fields['konfirmasi_data'] = [
        'type'     => 'checkbox',
        'label'    => 'Saya sudah memeriksa data pesanan',
        'section'  => 'before_submit',
        'required' => true,
        'priority' => 30,
    ];

    return $fields;
});
```

### Hasil setelah dipasang

Buka halaman checkout: Nama Perusahaan dan Jenis Pembeli muncul setelah data
pembeli, sedangkan Konfirmasi Data muncul sebelum tombol Buat Pesanan. Pilih Jenis Pembeli,
centang Konfirmasi Data, lalu selesaikan pesanan uji. Buka pesanan tersebut di
admin WordPress dan lihat box **Field Tambahan Checkout**.

Jika field wajib belum diisi, checkout akan menampilkan pesan kesalahan.
Nilai belum ditampilkan otomatis pada email atau dashboard seller marketplace.
Menonaktifkan plugin contoh menghilangkan field dari checkout; nilai pesanan lama
tetap tersimpan.

### Cara menyesuaikan contoh

Edit kode di file `checkout-tambahan.php` yang baru dibuat:

| Yang ingin diubah | Yang perlu diedit |
| --- | --- |
| Nama yang tampil | Ubah isi `label`, misalnya menjadi `Nama Usaha`. |
| Teks petunjuk | Ubah isi `placeholder`. |
| Wajib diisi | Pakai `'required' => true`. Untuk opsional, pakai `false`. |
| Lokasi field | Ubah `section` sesuai tabel lokasi di bawah. |
| Urutan field | Ubah angka `priority`. Angka lebih kecil tampil lebih awal dalam section yang sama. |
| Pilihan dropdown | Ubah pasangan nilai dan label di `options`. |
| Hapus field dari contoh | Hapus satu blok `$fields['nama_field'] = [...];` yang tidak diperlukan. |
| Tambah field teks lain | Salin blok `nama_perusahaan`, lalu ganti key dan labelnya. |

Key seperti `nama_perusahaan` harus unik, diawali huruf kecil, dan hanya berisi
huruf kecil, angka, atau underscore. Pertahankan key agar data pesanan lama mudah
dibaca. Tipe yang tersedia: `text`, `textarea`, `email`, `tel`, `select`, `checkbox`.
Hook ini mengatur field tambahan; field bawaan seperti nama penerima, alamat,
ongkir, dan pembayaran belum termasuk.

### Pilih lokasi field

Tambahkan properti `section` pada definisi field, misalnya `'section' => 'customer'`.

| Nilai `section` | Tempat field muncul | Contoh kebutuhan klien |
| --- | --- | --- |
| `customer` | Setelah data pembeli. | Nama perusahaan, nomor pelanggan. |
| `address` | Setelah data alamat pengiriman, hanya ketika alamat diminta. | Nama gedung, patokan alamat. |
| `notes` | Setelah Catatan pesanan. Ini lokasi default. | Pesan hadiah, instruksi pengemasan. |
| `before_submit` | Sebelum tombol Buat Pesanan. | Checkbox konfirmasi data pesanan. |

Jika `section` tidak diisi atau nilainya tidak dikenali, field tetap muncul pada
`notes`, sehingga kode lama tetap bekerja. `priority` mengatur urutan di dalam
section, bukan memindahkan field ke section lain.

Untuk field khusus alamat, ganti `section` pada salah satu field teks menjadi
`address`, misalnya untuk Nama Gedung. Field tersebut hanya tampil jika checkout
meminta alamat. Ketika alamat tidak diminta (misalnya seluruh produk digital),
field dinonaktifkan, tidak dikirim, tidak diwajibkan, dan nilainya tidak disimpan.
Aturan server mengikuti isi keranjang dan pengaturan toko, bukan nilai kiriman pembeli.

### Catatan untuk integrasi lanjutan

Hook `wp_store_checkout_fields` menerima array definisi field dan harus
mengembalikan array tersebut. Callback dengan priority lebih akhir bisa mengubah
definisi yang sudah ada atau menghapusnya dengan `unset($fields['nama_field'])`.

Kode contoh juga bisa ditempatkan di `functions.php` child theme: salin bagian
`add_filter(...)` saja, tanpa tag `<?php` dan header plugin. Pilih satu lokasi
pemasangan. Jangan membungkus hook dengan `is_page()` karena definisi field juga
diperlukan ketika REST API memvalidasi pesanan.

Kedua checkout mengirim objek JSON `checkout_fields`. Server hanya memproses
field terdaftar, membersihkan teks, dan memeriksa field wajib, email, serta pilihan
dropdown. Data tidak valid ditolak dengan HTTP 400 sebelum pesanan dibuat.
Checkbox disimpan sebagai string `1` atau `0`.

Nilai tersimpan dalam metadata `_store_order_checkout_fields` pada pesanan core
`store_order`. Jika membuat template sendiri, baca dengan
`get_post_meta($order_id, '_store_order_checkout_fields', true)`, pastikan hasilnya
array, lalu ambil key field yang dibutuhkan. `$order_id` adalah ID pesanan core,
bukan nomor invoice atau ID seller. Periksa hak akses pesanan dan gunakan
`esc_html()` saat menampilkan nilai.

Marketplace memakai template dan endpoint sendiri, tetapi menggunakan definisi
field dan penyimpanan core yang sama. Hook controller VD Store
`wp_store_before_create_order`, `wp_store_order_created`, dan
`wp_store_after_create_order` tidak otomatis dipanggil oleh endpoint marketplace.

## Membatasi checkout untuk pengguna login

Filter `wp_store_checkout_requires_login` dapat dipakai ketika klien hanya
mengizinkan pengguna yang sudah mempunyai akun dan sedang login untuk checkout.
Aturan ini berlaku pada checkout keranjang dan checkout langsung di VD Store
maupun Velocity Marketplace. Halaman checkout dan REST API sama-sama dilindungi.

### Pasang sebagai custom plugin

1. Buat folder `checkout-wajib-login` di `wp-content/plugins/`.
2. Buat file `checkout-wajib-login.php` di dalam folder tersebut.
3. Salin seluruh kode berikut, lalu aktifkan **Checkout Wajib Login** melalui admin WordPress.

```php
<?php
/**
 * Plugin Name: Checkout Wajib Login
 * Description: Membatasi checkout VD Store dan Velocity Marketplace untuk pengguna login.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_store_checkout_requires_login', '__return_true');
```

Setelah plugin aktif, tamu yang menekan **Beli Sekarang** diarahkan ke halaman
login dan kembali ke produk sesudah berhasil masuk. Jika tamu membuka halaman
checkout secara langsung, halaman menampilkan tombol **Masuk / Daftar**. Permintaan
checkout langsung ke REST API juga ditolak sampai pengguna login.

Pendaftaran akun mengikuti pengaturan WordPress. Aktifkan **Keanggotaan: Setiap
orang dapat mendaftar** pada **Pengaturan > Umum** jika klien mengizinkan pembeli
membuat akun sendiri. Menonaktifkan custom plugin mengembalikan checkout tamu.
