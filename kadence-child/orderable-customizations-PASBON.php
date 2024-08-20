<?php
// Assurez-vous que ce fichier est appelé correctement
if (!defined('ABSPATH')) {
    exit; // Sortie si accédé directement
}

// Fonction pour afficher le bouton personnalisé
function afficher_bouton_personnalise() {
    echo '<div style="text-align: center; margin-top: 20px; margin-bottom: 40px;">
            <a href="/votre-lien/" style="background-color: black; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold; border-radius: 5px;">
                Cliquez ici pour votre action
            </a>
          </div>';
}

// Ajouter le bouton après le contenu principal de WooCommerce
add_action('woocommerce_after_main_content', 'afficher_bouton_personnalise');

// Ajouter le HTML pour le popup
function ajouter_html_popup() {
    ?>
    <div id="product-options-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:5px; z-index:10000;">
        <span class="close" style="position:absolute; top:10px; right:10px; cursor:pointer;">&times;</span>
        <h4>Choisissez votre option de repas</h4>
        <form id="product-options-form">
            <input type="hidden" id="product-id" name="product_id" value="">
            <select name="option_repas" id="option_repas" required>
                <option value="">Sélectionnez une option</option>
                <option value="repas_complet">Repas complet</option>
                <option value="assiette_uniquement">Assiette uniquement</option>
                <option value="carte_repas">Carte repas</option>
            </select>
            <button type="submit">Ajouter au panier</button>
        </form>
    </div>
    <?php
}
add_action('wp_footer', 'ajouter_html_popup');

// Scripts pour gérer le popup
function custom_orderable_mini_cart_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Vérifier si le script est chargé
        console.log("Script chargé");

        // Gestion des cases à cocher pour les noms d'enfants
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

        // Ouvrir le popup
        $(document).on('click', '.orderable-product__select-button', function(e) {
            e.preventDefault();
            console.log("Bouton cliqué");
            var productId = $(this).data('product-id');
            $('#product-id').val(productId);
            $('#product-options-modal').show();
        });

        // Fermer le popup avec le X
        $('#product-options-modal .close').click(function() {
            $('#product-options-modal').hide();
        });

        // Fermer le popup en cliquant en dehors
        $(window).click(function(event) {
            if ($(event.target).is('#product-options-modal')) {
                $('#product-options-modal').hide();
            }
        });

        // Soumettre le formulaire
        $('#product-options-form').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: orderable_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_to_cart_custom',
                    form_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        $('#product-options-modal').hide();
                        // Mettre à jour le mini-cart ici
                        if (typeof(updateMiniCart) === "function") {
                            updateMiniCart();
                        }
                    } else {
                        alert('Erreur lors de l\'ajout au panier');
                    }
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'custom_orderable_mini_cart_scripts');

// Fonction pour afficher les points totaux dans le mini-cart
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
add_action('orderable_mini_cart_after', 'custom_orderable_mini_cart_after');