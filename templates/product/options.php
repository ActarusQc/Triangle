<?php
defined('ABSPATH') || exit;

global $orderable_single_product;
$orderable_single_product = true;
?>
va
<div class="orderable-product orderable-product--options orderable-product--image-cropped">
    <?php require Orderable_Helpers::get_template_path('templates/product/hero.php'); ?>

    <div class="orderable-sb-container" data-orderable-scroll-id="product">
        <?php do_action('orderable_side_menu_before_product_title', $product, $args); ?>

        <h2 class="orderable-product__title"><?php echo wp_kses_post($product->get_name()); ?></h2>

        <?php do_action('orderable_side_menu_before_product_options_wrapper', $product, $args); ?>

        <div class="orderable-product__options-wrap">
            <?php do_action('orderable_side_menu_before_product_options', $product, $args); ?>

            <table class="orderable-product__options" cellspacing="0" cellpadding="0">
                <?php if ($product->is_type('variable')) : ?>
                    <?php foreach ($product->get_variation_attributes() as $attribute_name => $options) : ?>
                        <tr class="orderable-product__option">
                            <th class="orderable-product__option-label">
                                <label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"><?php echo wc_attribute_label($attribute_name); ?></label>
                            </th>
                            <td class="orderable-product__option-select">
                                <?php
                                wc_dropdown_variation_attribute_options(array(
                                    'options'   => $options,
                                    'attribute' => $attribute_name,
                                    'product'   => $product,
                                    'class'     => 'orderable-input orderable-input--select orderable-input--validate',
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <tr class="orderable-product__option">
                    <th class="orderable-product__option-label">
                        <label><?php echo esc_html__('Choisir un ou plusieurs enfants', 'your-text-domain'); ?></label>
                    </th>
                    <td class="orderable-product__option-select">
                        <?php
                        $user_id = get_current_user_id();
                        $enfants = get_user_meta($user_id, 'enfants', true);
                        if (is_array($enfants)) {
                            foreach ($enfants as $enfant) {
                                $free_meals = intval(get_user_meta($user_id, 'points_repas_' . sanitize_key($enfant['nom']), true));
                                echo '<label><input type="checkbox" name="child_name[]" value="' . esc_attr($enfant['nom']) . '" data-free-meals="' . esc_attr($free_meals) . '"> ' . esc_html($enfant['nom']) . ' (' . $free_meals . ' repas gratuits)</label><br>';
                            }
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <?php do_action('orderable_side_menu_after_product_options', $product, $args); ?>

            <div class="orderable-product__messages"></div>

            <?php if ($product->is_type('variable')) : ?>
                <div class="orderable-product__variations" style="display: none;">
                    <?php
                    $available_variations = $product->get_available_variations();
                    echo wp_json_encode($available_variations, JSON_HEX_APOS | JSON_HEX_QUOT);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <?php do_action('orderable_side_menu_after_product_options_wrapper', $product, $args); ?>

        <div class="orderable-product__actions">
            <div class="orderable-product__actions-price" data-base-price="<?php echo esc_attr($product->get_price()); ?>">
                <?php echo $product->get_price_html(); ?>
            </div>
            <div class="orderable-product__actions-button">
                <button class="orderable-product__add-to-order custom-add-to-cart" data-orderable-product-id="<?php echo esc_attr($product->get_id()); ?>" data-orderable-trigger="add-to-cart">
                    <?php echo esc_html__('Ajouter au panier', 'your-text-domain'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function updateButtonAndPrice() {
        var $container = $('.orderable-product');
        var selectedChildren = $container.find('input[name="child_name[]"]:checked').length;
        var basePrice = parseFloat($('.orderable-product__actions-price').data('base-price'));
        var totalPrice = basePrice * selectedChildren;
        var freeMeals = 0;

        $container.find('input[name="child_name[]"]:checked').each(function() {
            freeMeals += Math.min(1, parseInt($(this).data('free-meals')));
        });

        $('.orderable-product__actions-price').html('€' + totalPrice.toFixed(2));
        
        var buttonText = selectedChildren > 0 ? 
            'Ajouter (' + selectedChildren + ' enfant' + (selectedChildren > 1 ? 's' : '') + ')' : 
            'Ajouter au panier';
        
        if (freeMeals > 0) {
            buttonText += ' (' + freeMeals + ' repas gratuit' + (freeMeals > 1 ? 's' : '') + ')';
        }
        
        $('.orderable-product__add-to-order').text(buttonText);
        $('.orderable-product__add-to-order').prop('disabled', selectedChildren === 0);
    }

    $(document).on('change', 'input[name="child_name[]"]', updateButtonAndPrice);

 $('.orderable-product__add-to-order').on('click', function(e) {
    e.preventDefault();
    var $button = $(this);
    var productId = $button.data('orderable-product-id');
    var variationId = $button.data('orderable-variation-id');
    var selectedChildren = $('input[name="child_name[]"]:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedChildren.length === 0) {
        alert('Veuillez sélectionner au moins un enfant.');
        return;
    }

    $button.prop('disabled', true);

    $.ajax({
        url: orderable_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'custom_add_to_cart',
            product_id: productId,
            variation_id: variationId,
            children: selectedChildren
        },
        success: function(response) {
            if (response.success) {
                $(document.body).trigger('wc_fragment_refresh');
                $(document.body).on('wc_fragments_refreshed', function() {
                    $(document.body).trigger('orderable-drawer.open', { action: 'show-cart' });
                    $(document.body).off('wc_fragments_refreshed');
                });
            } else {
                alert('Erreur lors de l\'ajout au panier : ' + response.data);
            }
            $button.prop('disabled', false);
        },
        error: function() {
            alert('Erreur lors de l\'ajout au panier');
            $button.prop('disabled', false);
        }
    });
});
</script>