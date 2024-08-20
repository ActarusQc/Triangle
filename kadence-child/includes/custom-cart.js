jQuery(document).ready(function($) {
    function updateButtonState($container) {
        var $button = $container.find('.custom-add-to-cart');
        var selectedChildren = $container.find('input[name="child_name[]"]:checked').length;
        $button.prop('disabled', selectedChildren === 0);
        
        var buttonText = selectedChildren > 0 ? 
            'Ajouter (' + selectedChildren + ' enfant' + (selectedChildren > 1 ? 's' : '') + ')' : 
            'Ajouter au panier';
        $button.text(buttonText);
    }

    $(document).on('change', 'input[name="child_name[]"]', function() {
        updateButtonState($(this).closest('.product-container'));
    });

    $(document).on('click', '.custom-add-to-cart', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $container = $button.closest('.product-container');
        var productId = $button.data('product-id');
        var selectedChildren = $container.find('input[name="child_name[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedChildren.length === 0) {
            alert('Veuillez sélectionner au moins un enfant.');
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: custom_cart_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_add_to_cart',
                product_id: productId,
                variation_id: variationId,
                child_name: childName,
                children: selectedChildren
                type_repas: typeRepas
            },
            success: function(response) {
                if (response.success) {
                    alert('Produit(s) ajouté(s) au panier avec succès !');
                    $(document.body).trigger('wc_fragment_refresh');
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
});