<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main">
    <?php
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            echo do_shortcode('[vmp_cart_page]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    } else {
        echo do_shortcode('[vmp_cart_page]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    ?>
</main>
<?php
get_footer();
