<?php
/**
 * My Account page
 *
 * @package WooCommerce\Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

// Afficher le menu personnalisé
wp_nav_menu( array(
    'theme_location' => 'mon-compte',
    'container' => 'nav',
    'container_class' => 'woocommerce-MyAccount-navigation',
    'menu_class' => 'woocommerce-MyAccount-navigation-list',
) ); ?>

<div class="woocommerce-MyAccount-content">
    <?php
    /**
     * My Account content.
     * @since 2.6.0
     */
    do_action( 'woocommerce_account_content' );
    ?>
</div>
