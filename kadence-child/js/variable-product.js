jQuery(document).ready(function($) {
    var $form = $('form.cart');
    var $addToCartButton = $form.find('.single_add_to_cart_button');

    $('.child-checkbox').on('change', function() {
        var selectedChildren = $('.child-checkbox:checked').length;
        if (selectedChildren > 0) {
            $addToCartButton.text('Ajouter au panier (' + selectedChildren + ')');
        } else {
            $addToCartButton.text('Ajouter au panier');
        }
    });

    $form.on('submit', function(e) {
        e.preventDefault();

        var selectedChildren = $('.child-checkbox:checked');
        if (selectedChildren.length === 0) {
            alert('Veuillez sélectionner au moins un enfant.');
            return;
        }

        var variationId = $('input[name="variation_id"]').val();
        var quantity = $('input[name="quantity"]').val();

        selectedChildren.each(function() {
            var childId = $(this).val();
            var data = {
                action: 'add_to_cart_variable_product',
                product_id: wc_add_to_cart_variation_params.product_id,
                variation_id: variationId,
                quantity: quantity,
                child_id: childId
            };

            $.post(wc_add_to_cart_params.ajax_url, data, function(response) {
                if (response.success) {
                    $(document.body).trigger('added_to_cart');
                }
            });
        });
    });
});