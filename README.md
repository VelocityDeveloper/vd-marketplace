# VD Marketplace

Versi: `1.0.1`

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
- dokumentasi teknis developer ada di file:
  - `DOKUMENTASI-DEVELOPER.md`
