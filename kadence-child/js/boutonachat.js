jQuery(document).ready(function($) {
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();

        var productId = $(this).data('product-id');
        var childName = $(this).data('child-name');

        console.log('Button clicked');
        console.log('Product ID:', productId);
        console.log('Child Name:', childName);

        $.ajax({
            type: 'POST',
            url: wc_add_to_cart_params.ajax_url,
            data: {
                action: 'custom_add_to_cart',
                product_id: productId,
                child_name: childName
            },
            success: function(response) {
                console.log('AJAX response:', response);

                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ajouté au panier',
                        text: 'Le produit a été ajouté au panier.',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Déjà acheté',
                        text: response.data.message,
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    });
});
