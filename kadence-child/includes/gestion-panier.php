<?php
/**
 * Gestion du panier et des cartes repas
 *
 * Ce fichier gère les fonctionnalités liées au panier WooCommerce,
 * notamment l'utilisation des cartes repas et l'ajustement des prix.
 */

// Assurez-vous que ce fichier n'est pas accessible directement
if (!defined('ABSPATH')) {
    exit;
}

function orderable_add_to_cart() {
    error_log("Debug - orderable_add_to_cart - Données reçues: " . print_r($_POST, true));

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $child_name = isset($_POST['child_name']) ? sanitize_text_field($_POST['child_name']) : '';
    $type_repas = isset($_POST['type_repas']) ? sanitize_text_field($_POST['type_repas']) : '';

    if (!$product_id) {
        error_log("Erreur: product_id manquant");
        wp_send_json_error('product_id manquant');
        return;
    }
    if (empty($child_name)) {
        error_log("Erreur: child_name manquant");
        wp_send_json_error('child_name manquant');
        return;
    }
    if (empty($type_repas)) {
        error_log("Erreur: type_repas manquant");
        wp_send_json_error('type_repas manquant');
        return;
    }

    $user_id = get_current_user_id();
    $free_meals = intval(get_user_meta($user_id, 'points_repas_' . sanitize_key($child_name), true));
    
    $cart_item_data = array(
        'child_name' => $child_name,
        'free_meal' => $free_meals > 0,
        'type_repas' => $type_repas
    );

    error_log("Debug - Données de l'article à ajouter au panier : " . print_r($cart_item_data, true));

    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, array(), $cart_item_data);

    if ($cart_item_key) {
        if ($free_meals > 0) {
            update_user_meta($user_id, 'points_repas_' . sanitize_key($child_name), $free_meals - 1);
        }
        $cart_item = WC()->cart->get_cart_item($cart_item_key);
        error_log("Debug - Article ajouté au panier : " . print_r($cart_item, true));
        wp_send_json_success('Produit ajouté au panier.');
    } else {
        error_log("Erreur: Impossible d'ajouter le produit au panier");
        wp_send_json_error('Erreur lors de l\'ajout au panier');
    }
}

add_action('wp_ajax_orderable_add_to_cart', 'orderable_add_to_cart');
add_action('wp_ajax_nopriv_orderable_add_to_cart', 'orderable_add_to_cart');

function update_points_on_add_to_cart($cart_item_data, $product_id, $variation_id) {
    $user_id = get_current_user_id();
    if (isset($_POST['child_name']) && is_array($_POST['child_name'])) {
        foreach ($_POST['child_name'] as $child_name) {
            $points_key = 'points_repas_' . sanitize_key($child_name);
            $current_points = intval(get_user_meta($user_id, $points_key, true));
            if ($current_points > 0) {
                update_user_meta($user_id, $points_key, $current_points - 1);
                $cart_item_data['carte_repas_used'] = true;
                $cart_item_data['points_used'] = 1;
            }
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10, 3);

function add_child_name_to_cart_item($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['child_name'])) {
        $cart_item_data['nom_enfant'] = sanitize_text_field($_POST['child_name']);
        error_log("Nom de l'enfant ajouté pour le produit $product_id : " . $cart_item_data['nom_enfant']);
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_child_name_to_cart_item', 10, 3);

function add_custom_price_data($cart_item_data, $product_id) {
    $product = wc_get_product($product_id);
    if ($product->is_type('variation')) {
        $attributes = $product->get_attributes();
        if (isset($attributes['pa_type-de-repas'])) {
            $type_repas = $attributes['pa_type-de-repas'];
            if ($type_repas === 'repas-complet') {
                $cart_item_data['custom_price'] = get_option('prix_repas_complet');
            } elseif ($type_repas === 'assiette-seulement') {
                $cart_item_data['custom_price'] = get_option('prix_assiette_seulement');
            }
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_custom_price_data', 10, 2);



function display_cart_item_custom_price_data($item_data, $cart_item) {
    if (isset($cart_item['carte_repas_used']) && $cart_item['carte_repas_used']) {
        $item_data[] = array(
            'key' => 'Repas',
            'value' => 'Utilisée (Repas gratuit)'
        );
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_cart_item_custom_price_data', 10, 2);

function display_total_cartes_repas_used() {
    $cart = WC()->cart;
    $total_cartes_used = 0;

    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['carte_repas_used']) && $cart_item['carte_repas_used']) {
            $total_cartes_used++;
        }
    }

    if ($total_cartes_used > 0) {
        echo '<tr class="cart-subtotal">
            <th>Cartes repas utilisées</th>
            <td data-title="Cartes repas utilisées">' . $total_cartes_used . '</td>
        </tr>';
    }
}
add_action('woocommerce_review_order_before_order_total', 'display_total_cartes_repas_used');
add_action('woocommerce_cart_totals_before_order_total', 'display_total_cartes_repas_used');

function add_custom_cart_css() {
    echo '<style>
        .carte-repas-info {
            font-size: 0.9em;
            color: #4CAF50;
            font-weight: bold;
        }
    </style>';
}
add_action('wp_head', 'add_custom_cart_css');

function conditional_payment_gateways($available_gateways) {
    if (!is_admin()) {
        $total = WC()->cart->total;

        // Toujours autoriser le paiement par points SUMO et le paiement sur réception
        $allowed_gateways = array('reward_gateway', 'cod');

        if ($total > 0) {
            // Ajouter d'autres passerelles de paiement si le total est supérieur à 0
            $allowed_gateways[] = 'bacs';
            // Ajoutez ici d'autres passerelles si nécessaire
        }

        foreach ($available_gateways as $gateway_id => $gateway) {
            if (!in_array($gateway_id, $allowed_gateways)) {
                unset($available_gateways[$gateway_id]);
            }
        }
    }
    return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'conditional_payment_gateways');

function refresh_payment_methods() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('body').on('updated_cart_totals', function() {
            $('body').trigger('update_checkout');
        });
    });
    </script>
    <?php
}
add_action('woocommerce_review_order_before_payment', 'refresh_payment_methods');

// Fonction de débogage
function debug_cart_contents() {
    $cart = WC()->cart;
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $price = $product->get_price();
        $type_repas = $product->is_type('variation') ? $product->get_attribute('pa_type-de-repas') : 'N/A';
        error_log("Panier - Produit: {$product->get_name()}, Type: $type_repas, Prix: $price");
    }
}
add_action('woocommerce_before_calculate_totals', 'debug_cart_contents', 99);

?>