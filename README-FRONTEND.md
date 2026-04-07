# Frontend JS Map

Dokumen ini fokus ke struktur JavaScript frontend dan custom admin page yang memakai pola `Alpine.js + REST API`.

Tujuannya:
- developer baru cepat tahu file mana dipakai di halaman mana
- perubahan tidak lagi dilakukan dengan menebak-nebak entry point
- helper shared dipakai ulang secara konsisten

## Prinsip Saat Ini

- `frontend-shared.js` adalah fondasi bersama
- file lain hanya memuat concern spesifik per halaman/fitur
- state UI ditulis di Alpine component
- request data dilakukan via REST API
- validasi dan penyimpanan final tetap di PHP

## Entry Points

### Frontend user-facing

- `assets/js/frontend-shared.js`
  - dimuat lebih dulu
  - menyediakan `window.VMPFrontend`
  - berisi helper request, format uang, helper cart, helper wilayah, helper captcha

- `assets/js/frontend-cart.js`
  - untuk halaman keranjang
  - register Alpine component `vmpCart`

- `assets/js/frontend-checkout.js`
  - untuk halaman checkout
  - register Alpine component `vmpCheckout`

- `assets/js/frontend-profile.js`
  - untuk profil member dan profil toko
  - register:
    - `vmpStoreProfileLocation`
    - `vmpMemberProfileForm`
    - `vmpStoreProfileForm`

- `assets/js/frontend-ui.js`
  - helper UI ringan lintas halaman
  - tombol add to cart global
  - toggle wishlist global
  - galeri produk + lightbox

  - helper kecil dashboard account
  - fokus textarea chat
  - scroll thread chat ke bawah

- `assets/js/media.js`
  - integrasi WordPress media library untuk field gambar custom

### Backend custom admin page

- `assets/js/admin-settings.js`
  - hanya untuk halaman custom `Pengaturan Marketplace`
  - register Alpine component `vmpAdminSettingsPage`
  - tidak dipakai di editor CPT WordPress bawaan

## Mapping Halaman ke Component

- `templates/cart.php`
  - `x-data="vmpCart()"`
  - file JS utama: `frontend-cart.js`

- `templates/checkout.php`
  - `x-data="vmpCheckout()"`
  - file JS utama: `frontend-checkout.js`

- `templates/account/profile.php`
  - `x-data="vmpMemberProfileForm(...)"`
  - file JS utama: `frontend-profile.js`

- `templates/seller/profile.php`
  - `x-data="vmpStoreProfileForm(...)"`
  - file JS utama: `frontend-profile.js`

- `src/Core/SettingsPage.php`
  - `x-data="vmpAdminSettingsPage()"`
  - file JS utama: `admin-settings.js`

## Shared Helper

Semua helper global frontend disimpan di `window.VMPFrontend`.

Yang utama:

- `api(path)`
  - membentuk URL endpoint REST

- `request(path, options)`
  - wrapper fetch + nonce WordPress + error handling standar

- `money(value)`
  - format uang mengikuti currency plugin

- `flashButtonLabel(button, nextLabel)`
  - helper label status singkat untuk tombol

- `mapProvince(row)`
- `mapCity(row)`
- `mapSubdistrict(row)`
  - normalisasi response API wilayah

- `fetchShippingList(path)`
  - wrapper GET untuk daftar wilayah

- `gatherCaptcha(formNode)`
  - ambil field captcha dari form bila ada

- `formDataToObject(formNode)`
  - ubah `FormData` ke object biasa

- `cartHelpers`
  - helper tampilan yang dipakai ulang di cart dan checkout

Kalau helper baru dibutuhkan lintas file, taruh di `frontend-shared.js`.
Kalau helper hanya dipakai satu file, taruh lokal di file itu saja.

## Tanggung Jawab Per File

### `frontend-cart.js`

Tanggung jawab:
- load isi cart
- update qty
- hapus item
- kosongkan keranjang

Jangan taruh:
- coupon
- shipping
- logic daftar produk

### `frontend-checkout.js`

Tanggung jawab:
- prefill profil member ke checkout
- load provinsi, kota, kecamatan tujuan
- load shipping context per toko
- pilih layanan kirim
- validasi COD
- preview/apply kupon
- submit order

Jangan taruh:
- logic media library
- galeri produk
- dashboard chat

### `frontend-profile.js`

Tanggung jawab:
- state lokasi profil
- submit profil member
- submit profil toko
- sync dropdown wilayah
- sync multi-select kota COD

Jangan taruh:
- order update
- review
- checkout

### `frontend-ui.js`

Tanggung jawab:
- event delegation tombol add to cart global
- event delegation wishlist global
- galeri produk
- lightbox produk

Jangan taruh:
- state Alpine halaman besar

### `admin-settings.js`

Tanggung jawab:
- tab admin settings
- state form settings custom
- save via REST
- repeater rekening bank

Jangan taruh:
- editor CPT
- logic wp-admin native lain

## Pola Maintenance

Kalau menambah fitur baru, urutannya:

1. tentukan concern fiturnya
2. pakai file yang sudah ada jika memang concern-nya sama
3. kalau concern baru, buat file baru daripada menumpuk file yang tidak relevan
4. helper lintas halaman masuk `frontend-shared.js`
5. logic save tetap berakhir di REST controller + service PHP

## Aturan Praktis

- Jangan panggil endpoint REST langsung dengan `fetch` mentah kalau `request()` sudah cukup
- Jangan membuat helper global baru di `window` kecuali memang entry point Alpine
- Jangan campur logic dua halaman besar ke file yang sama hanya karena sama-sama pakai Alpine
- Tambahkan komentar singkat per fungsi/method yang tidak self-explanatory

## Checklist Saat Mengubah JS

1. pastikan file concern-nya benar
2. cek apakah helper yang dibutuhkan sudah ada di `frontend-shared.js`
3. cek apakah template memang memakai component Alpine yang diubah
4. jalankan `node --check assets/js/<file>.js`
5. update dokumen ini kalau ada file/component baru atau entry point berubah
