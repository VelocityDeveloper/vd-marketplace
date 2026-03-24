<?php
use VelocityMarketplace\Modules\Account\Account;
use VelocityMarketplace\Modules\Message\MessageRepository;
use VelocityMarketplace\Modules\Notification\NotificationRepository;
use VelocityMarketplace\Modules\Order\OrderData;
use VelocityMarketplace\Modules\Wishlist\WishlistRepository;
use VelocityMarketplace\Frontend\Template;

$notice = isset($_GET['vmp_notice']) ? sanitize_text_field((string) wp_unslash($_GET['vmp_notice'])) : '';
$error = isset($_GET['vmp_error']) ? sanitize_text_field((string) wp_unslash($_GET['vmp_error'])) : '';
$tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : '';

if (!is_user_logged_in()) {
    $profile_redirect = \VelocityMarketplace\Support\Settings::profile_url();
    $login_url = wp_login_url($profile_redirect);
    $register_customer_url = add_query_arg('vmp_role_type', 'customer', wp_registration_url());
    $register_seller_url = add_query_arg('vmp_role_type', 'seller', wp_registration_url());
    ?>
    <div class="container py-4 vmp-wrap">
        <?php if ($notice !== '') : ?><div class="alert alert-success py-2"><?php echo esc_html($notice); ?></div><?php endif; ?>
        <?php if ($error !== '') : ?><div class="alert alert-danger py-2"><?php echo esc_html($error); ?></div><?php endif; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="h5 mb-2">Akses Akun Marketplace</h3>
                <p class="text-muted mb-3">Login dan registrasi sekarang memakai halaman bawaan WordPress. Captcha akan mengikuti integrasi dari plugin Velocity Addons di halaman tersebut.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo esc_url($login_url); ?>" class="btn btn-dark">Login</a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo esc_url($register_customer_url); ?>" class="btn btn-outline-dark">Daftar Customer</a>
                        <a href="<?php echo esc_url($register_seller_url); ?>" class="btn btn-primary">Daftar Seller</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
    return;
}

$current_user_id = get_current_user_id();
$is_seller = Account::is_seller($current_user_id);
if ($tab === '') {
    $tab = $is_seller ? 'seller_home' : 'orders';
}
if ($tab === 'seller') {
    $tab = 'seller_products';
}

$status_labels = OrderData::statuses();
$notification_repo = new NotificationRepository();
$notifications = $notification_repo->all($current_user_id);
$unread_count = $notification_repo->unread_count($current_user_id);
$message_repo = new MessageRepository();
$messages = $message_repo->all($current_user_id, 120);
$message_unread_count = $message_repo->unread_count($current_user_id);
$message_contacts = $message_repo->contacts($current_user_id);
$selected_message_to = isset($_GET['message_to']) ? (int) wp_unslash($_GET['message_to']) : 0;
$selected_message_order = isset($_GET['message_order']) ? (int) wp_unslash($_GET['message_order']) : 0;
$selected_message_invoice = $selected_message_order > 0 ? (string) get_post_meta($selected_message_order, 'vmp_invoice', true) : '';
$selected_contact_exists = false;
foreach ($message_contacts as $contact_row) {
    if ((int) ($contact_row['id'] ?? 0) === $selected_message_to) {
        $selected_contact_exists = true;
        break;
    }
}
if ($selected_message_to > 0 && !$selected_contact_exists) {
    $selected_user = get_userdata($selected_message_to);
    if ($selected_user && (current_user_can('manage_options') || $message_repo->can_contact($current_user_id, $selected_message_to))) {
        $message_contacts[] = [
            'id' => $selected_message_to,
            'name' => $selected_user->display_name !== '' ? $selected_user->display_name : $selected_user->user_login,
            'role' => in_array('vmp_seller', (array) $selected_user->roles, true) ? 'Seller' : (in_array('administrator', (array) $selected_user->roles, true) ? 'Admin' : 'Customer'),
        ];
    }
}
$wishlist_repo = new WishlistRepository();
$wishlist_ids = $wishlist_repo->get_ids($current_user_id);

$money = static function ($value) {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
};

$logout_url = add_query_arg([
    'vmp_logout' => 1,
    'vmp_nonce' => wp_create_nonce('vmp_logout'),
]);

$store_name = (string) get_user_meta($current_user_id, 'vmp_store_name', true);
$store_address = (string) get_user_meta($current_user_id, 'vmp_store_address', true);
$profile_complete = !$is_seller || ($store_name !== '' && $store_address !== '');
$is_star_seller = !empty(get_user_meta($current_user_id, 'vmp_is_star_seller', true));

$tabs = [
    ['key' => 'orders', 'label' => 'Riwayat Belanja'],
    ['key' => 'wishlist', 'label' => 'Wishlist'],
    ['key' => 'tracking', 'label' => 'Tracking'],
    ['key' => 'messages', 'label' => 'Pesan (' . $message_unread_count . ')'],
    ['key' => 'notifications', 'label' => 'Notifikasi (' . $unread_count . ')'],
];
if ($is_seller) {
    $tabs[] = ['key' => 'seller_home', 'label' => 'Beranda Toko'];
    $tabs[] = ['key' => 'seller_report', 'label' => 'Laporan'];
    $tabs[] = ['key' => 'seller_products', 'label' => 'Produk'];
    $tabs[] = ['key' => 'seller_profile', 'label' => 'Edit Profil'];
}
?>
<div class="container py-4 vmp-wrap">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 mb-0">Dashboard Marketplace</h2>
            <small class="text-muted">Order, produk, profil toko, wishlist, notifikasi, tracking</small>
        </div>
        <a href="<?php echo esc_url($logout_url); ?>" class="btn btn-sm btn-outline-dark">Logout</a>
    </div>

    <?php if ($notice !== '') : ?><div class="alert alert-success py-2"><?php echo esc_html($notice); ?></div><?php endif; ?>
    <?php if ($error !== '') : ?><div class="alert alert-danger py-2"><?php echo esc_html($error); ?></div><?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($tabs as $it) : ?>
            <a class="btn btn-sm <?php echo $tab === $it['key'] ? 'btn-dark' : 'btn-outline-dark'; ?>" href="<?php echo esc_url(add_query_arg(['tab' => $it['key']])); ?>"><?php echo esc_html($it['label']); ?></a>
        <?php endforeach; ?>
    </div>
    <?php
    $view_data = get_defined_vars();
    if ($tab === 'orders') {
        echo Template::render('account/orders', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'wishlist') {
        echo Template::render('account/wishlist', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'tracking') {
        echo Template::render('account/tracking', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'messages') {
        echo Template::render('account/messages', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'notifications') {
        echo Template::render('account/notifications', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'seller_home' && $is_seller) {
        echo Template::render('seller/home', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'seller_report' && $is_seller) {
        echo Template::render('seller/report', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'seller_products' && $is_seller) {
        echo Template::render('seller/products', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } elseif ($tab === 'seller_profile' && $is_seller) {
        echo Template::render('seller/profile', $view_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        echo '<div class="alert alert-warning mb-0">Menu tidak tersedia.</div>';
    }
    ?>
</div>

