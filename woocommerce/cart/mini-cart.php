<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_mini_cart'); ?>





<?php if (isset(WC()->cart) && !WC()->cart->is_empty()) : ?>
    <div class="orderable-sb-container" data-orderable-scroll-id="cart" style="max-height: 400px; overflow-y: auto;">
        <div class="orderable-mini-cart-footer" style="padding: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border: 1px solid #ccc;">
            <p class="woocommerce-mini-cart__total total" style="margin: 0;">
                <?php
                /**
                 * Hook: woocommerce_widget_shopping_cart_total.
                 *
                 * @hooked woocommerce_widget_shopping_cart_subtotal - 10
                 */
                do_action('woocommerce_widget_shopping_cart_total'); ?>
            </p>

            <?php do_action('woocommerce_widget_shopping_cart_before_buttons'); ?>

            <!-- Bouton "Commander" -->
            <p class="woocommerce-mini-cart__buttons buttons" style="margin: 0;">
                <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="button checkout wc-forward">
                    <?php esc_html_e('Commander', 'woocommerce'); ?>
                </a>
            </p>

            <?php do_action('woocommerce_widget_shopping_cart_after_buttons'); ?>
        </div>

        <ul class="orderable-mini-cart <?php echo esc_attr($args['list_class']); ?>" style="max-height: 300px; overflow-y: auto;">
            <?php
            do_action('woocommerce_before_mini_cart_contents');

            $total_points = 0; // Variable pour calculer les points totaux

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key)) {
                    $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                    $product_price = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);

                    // Ajuster le texte affiché pour les points ajustés
                   
                    ?>
                    
                    
                    
                    
                    
                    
                    
                 <li class="woocommerce-mini-cart-item <?php echo esc_attr(apply_filters('woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key)); ?>">
    <!-- Icône de suppression -->
    <a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>" class="remove" aria-label="<?php esc_attr_e('Remove this item', 'woocommerce'); ?>" data-product_id="<?php echo esc_attr($cart_item['product_id']); ?>" data-cart_item_key="<?php echo esc_attr($cart_item_key); ?>" data-product_sku="<?php echo esc_attr($_product->get_sku()); ?>">
        <i class="fa fa-trash" aria-hidden="true"></i>
    </a>
    
    <!-- Nom du produit et de l'enfant -->
    <div class="product-info">
        <?php
        $product_name = $_product->get_name();
        $child_name = isset($cart_item['nom_enfant']) ? ' - ' . esc_html($cart_item['nom_enfant']) : '';
        echo $product_name . $child_name;
        ?>
    </div>
    
    <!-- Prix ou "1 repas" -->
    <div class="product-price">
        <?php
        $is_free_meal = isset($cart_item['free_meal']) && $cart_item['free_meal'];
        echo $is_free_meal ? '1 repas' : wc_price($_product->get_price());
        ?>
    </div>
</li>






                    <?php
                }
            }

            do_action('woocommerce_mini_cart_contents');
            ?>
        </ul>

        <!-- Affichage des points totaux dans le mini-panier -->
        <div class="mini-cart-total-points">
            <strong><?php _e('Total Points', 'your-text-domain'); ?>:</strong> <?php echo $total_points . ' ' . __('Point(s) repas', 'your-text-domain'); ?>
        </div>
    </div>
<?php else : ?>

    <p class="orderable-mini-cart__empty-message"><?php esc_html_e('No products in the cart.', 'woocommerce'); ?></p>

<?php endif; ?>

<?php do_action('woocommerce_after_mini_cart'); ?>

<script>
jQuery(document).ready(function($) {
    // Ajoutez les noms des enfants à tous les produits du panier avant de passer la commande
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

    // Mettre à jour les entrées nom_enfant dans le panier
    $('#child-name-checkboxes-final input[type="checkbox"]').on('change', function() {
        var selectedChildren = [];
        $('#child-name-checkboxes-final input[type="checkbox"]:checked').each(function() {
            selectedChildren.push($(this).val());
        });
        $('.orderable-child-select').each(function() {
            $(this).val(selectedChildren.join(',')).trigger('change');
        });
    });

    // Supprimer un article du panier sans fermer le mini-panier et avec une réponse rapide
    $('.orderable-mini-cart').on('click', '.remove', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $item = $this.closest('li.woocommerce-mini-cart-item');

        $.ajax({
            type: 'GET',
            url: $this.attr('href'),
            success: function(response) {
                $item.remove();
                $(document.body).trigger('wc_fragment_refresh');
            }
        });
    });
});
</script>

<script>
jQuery(document).ready(function($) {
    // Ajoutez ceci pour mettre à jour le mini-panier
    $(document.body).on('updated_cart_totals', function() {
        $('div.widget_shopping_cart_content').empty().append('<div class="loading-overlay"></div>');
        $('div.widget_shopping_cart_content').load(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'));
    });
});

<script>
jQuery(document).ready(function($) {
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
        console.log('Fragments reçus:', fragments);
        if (typeof fragments !== 'undefined') {
            $.each(fragments, function(key, value) {
                $(key).replaceWith(value);
                console.log('Fragment appliqué:', key, value);
            });
            console.log('Tous les fragments ont été appliqués.');
        } else {
            console.log('Pas de fragments reçus.');
        }
    });
});


</script>


