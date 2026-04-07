<?php
$title = isset($title) ? (string) $title : __('Fitur Seller', 'velocity-marketplace');
$message = isset($message) ? (string) $message : __('Aktifkan seller terlebih dahulu di Profil Toko untuk memakai fitur ini.', 'velocity-marketplace');
$profile_url = isset($profile_url) ? (string) $profile_url : '';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h6 mb-2"><?php echo esc_html($title); ?></h3>
        <p class="text-muted mb-3"><?php echo esc_html($message); ?></p>
        <div class="small text-muted mb-3"><?php echo esc_html__('Jika Anda baru saja mengaktifkan seller di Profil Toko, silakan refresh halaman ini untuk memuat panel seller.', 'velocity-marketplace'); ?></div>
        <?php if ($profile_url !== '') : ?>
            <a href="<?php echo esc_url($profile_url); ?>" class="btn btn-dark btn-sm"><?php echo esc_html__('Buka Profil Toko', 'velocity-marketplace'); ?></a>
        <?php endif; ?>
    </div>
</div>
