<?php
// Ne pas oublier d'ajouter les vérifications de sécurité appropriées si c'est un fichier séparé

add_filter('orderable_mini_cart_item', 'custom_orderable_mini_cart_item', 10, 3);
function custom_orderable_mini_cart_item($item_output, $cart_item, $cart_item_key) {
    $_product = $cart_item['data'];
    $product_id = $_product->get_id();
    $option = isset($cart_item['repas_option']) ? $cart_item['repas_option'] : '';
    $points = get_post_meta($product_id, '_wc_points_earned', true);
    
    $price_display = '';
    if ($option === 'repas_complet') {
        $price = get_option('prix_repas_complet', 0);
        $price_display = wc_price($price);
    } elseif ($option === 'assiette_seulement') {
        $price = get_option('prix_assiette_seulement', 0);
        $price_display = wc_price($price);
    } elseif ($option === 'carte_repas' || !empty($points)) {
        $points_value = $option === 'carte_repas' ? get_option('points_repas_carte', 0) : $points;
        $price_display = $points_value . ' ' . __('points', 'your-text-domain');
    } else {
        $price_display = WC()->cart->get_product_price($_product);
    }
    
    $quantity = $cart_item['quantity'];
    $item_output = str_replace('{{qty}}', $quantity, $item_output);
    $item_output = str_replace('{{price}}', $price_display, $item_output);
    
    return $item_output;
}

add_action('orderable_mini_cart_after', 'custom_orderable_mini_cart_after');
function custom_orderable_mini_cart_after() {
    $total_points = 0;
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $_product = $cart_item['data'];
        $option = isset($cart_item['repas_option']) ? $cart_item['repas_option'] : '';
        $points = get_post_meta($_product->get_id(), '_wc_points_earned', true);
        
        if ($option === 'carte_repas' || !empty($points)) {
            $points_value = $option === 'carte_repas' ? get_option('points_repas_carte', 0) : $points;
            $total_points += $points_value * $cart_item['quantity'];
        }
    }
    
    if ($total_points > 0) {
        echo '<div class="mini-cart-total-points">';
        echo '<strong>' . __('Total Points', 'your-text-domain') . ':</strong> ';
        echo $total_points . ' ' . __('Point(s) repas', 'your-text-domain');
        echo '</div>';
    }
}

add_action('wp_footer', 'custom_orderable_mini_cart_scripts');
function custom_orderable_mini_cart_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#place_order').on('click', function() {
            var childNames = [];
            $('#child-name-checkboxes-final input[type="checkbox"]:checked').each(function() {
                childNames.push($(this).val());
            });
            if (childNames.length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'child_names_hidden',
                    name: 'child_names',
                    value: childNames.join(',')
                }).appendTo('#order_review');
            }
        });

        $('#child-name-checkboxes-final input[type="checkbox"]').on('change', function() {
            var selectedChildren = [];
            $('#child-name-checkboxes-final input[type="checkbox"]:checked').each(function() {
                selectedChildren.push($(this).val());
            });
            $('.orderable-child-select').each(function() {
                $(this).val(selectedChildren.join(',')).trigger('change');
            });
        });
    });
    </script>
    <?php
}