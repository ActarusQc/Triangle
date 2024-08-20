<?php


add_action('woocommerce_before_add_to_cart_button', 'ajouter_options_repas_au_formulaire');

function ajouter_options_repas_au_formulaire() {
    $options = array(
        'repas_complet' => __('Repas complet', 'woocommerce'),
        'assiette_uniquement' => __('Assiette uniquement', 'woocommerce'),
        'carte_repas' => __('Carte repas', 'woocommerce')
    );
    
    echo '<div class="repas-options">';
    echo '<label for="option_repas">' . __('Choisissez votre option de repas', 'woocommerce') . '</label>';
    echo '<select name="option_repas" id="option_repas" required>';
    echo '<option value="">' . __('Sélectionnez une option', 'woocommerce') . '</option>';
    foreach ($options as $key => $label) {
        echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</div>';
}

add_action('wp_footer', 'script_ajustement_prix');

function script_ajustement_prix() {
    if (!is_product()) return;
    
    
    ?>
    <script>
    function script_ajustement_prix() {
    if (!is_product()) return;
    
    // Récupérer les prix depuis les options
    $prix_repas_complet = get_option('prix_repas_complet', '7');
    $prix_assiette_seulement = get_option('prix_assiette_seulement', '5');
    ?>
    <script>
    jQuery(document).ready(function($) {
        var prixRepasComplet = <?php echo json_encode(floatval($prix_repas_complet)); ?>;
        var prixAssietteSeulement = <?php echo json_encode(floatval($prix_assiette_seulement)); ?>;

        $('#option_repas').on('change', function() {
            var option = $(this).val();
            var price = 0;
            
            switch(option) {
                case 'repas_complet':
                    price = prixRepasComplet;
                    break;
                case 'assiette_uniquement':
                    price = prixAssietteSeulement;
                    break;
                case 'carte_repas':
                    // Gérer le cas de la carte repas si nécessaire
                    // Vous pouvez ajouter une logique spécifique ici si besoin
                    break;
            }
            
            $('.price .amount').text('$' + price.toFixed(2));

            // Mettre à jour le prix du formulaire d'ajout au panier si nécessaire
            $('input[name="variation_id"]').val(option);
            $('input[name="product_id"]').val($('input[name="product_id"]').val());
            $('input[name="add-to-cart"]').val($('input[name="product_id"]').val());
        });
    });
    </script>
    <?php
}
    });
    </script>
    <?php
}


// Ajouter un champ personnalisé à l'éditeur de produit
add_action('woocommerce_product_options_general_product_data', 'ajouter_champ_variations_personnalisees');

function ajouter_champ_variations_personnalisees() {
    woocommerce_wp_select(
        array(
            'id' => '_variations_personnalisees',
            'label' => __('Options de repas', 'woocommerce'),
            'options' => array(
                'repas_complet' => __('Repas complet', 'woocommerce'),
                'assiette_uniquement' => __('Assiette uniquement', 'woocommerce'),
                'carte_repas' => __('Carte repas', 'woocommerce')
            ),
            'value' => 'repas_complet,assiette_uniquement,carte_repas', // Toutes les options sélectionnées par défaut
            'desc_tip' => true,
            'description' => __('Ces options seront disponibles pour ce repas.', 'woocommerce')
        )
    );
}

// Sauvegarder le champ personnalisé
add_action('woocommerce_process_product_meta', 'sauvegarder_champ_variations_personnalisees');

function sauvegarder_champ_variations_personnalisees($post_id) {
    $variations_personnalisees = isset($_POST['_variations_personnalisees']) ? wc_clean($_POST['_variations_personnalisees']) : '';
    update_post_meta($post_id, '_variations_personnalisees', $variations_personnalisees);
}

add_action('woocommerce_before_add_to_cart_button', 'afficher_options_repas');

function afficher_options_repas() {
    $options = array(
        'repas_complet' => __('Repas complet', 'woocommerce'),
        'assiette_uniquement' => __('Assiette uniquement', 'woocommerce'),
        'carte_repas' => __('Carte repas', 'woocommerce')
    );
    
    echo '<div id="repas-options-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999;">
        <div id="repas-options-popup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:5px;">
            <h4>' . __('Choisissez votre option de repas', 'woocommerce') . '</h4>
            <select name="option_repas" id="option_repas">';
    foreach ($options as $key => $label) {
        echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
    }
    echo '</select>
            <button id="confirm-repas-option">' . __('Confirmer', 'woocommerce') . '</button>
        </div>
    </div>';
}





add_filter('woocommerce_product_get_price', 'definir_prix_par_defaut', 10, 2);
add_filter('woocommerce_product_get_regular_price', 'definir_prix_par_defaut', 10, 2);

function definir_prix_par_defaut($price, $product) {
    if ('' === $price || 0 === $price) {
        return floatval(get_option('prix_repas_complet', '7')); // Prix par défaut pour 'repas_complet'
    }
    return $price;
}

add_filter('woocommerce_add_to_cart_validation', 'valider_option_repas', 10, 3);

add_filter('woocommerce_add_to_cart_validation', 'valider_option_repas', 10, 3);

function valider_option_repas($passed, $product_id, $quantity) {
    if (empty($_POST['option_repas'])) {
        wc_add_notice(__('Veuillez sélectionner une option de repas.', 'woocommerce'), 'error');
        return false;
    }
    return $passed;
}

add_filter('woocommerce_add_cart_item_data', 'ajouter_option_repas_aux_donnees_panier', 10, 3);

function ajouter_option_repas_aux_donnees_panier($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['option_repas'])) {
        $cart_item_data['option_repas'] = sanitize_text_field($_POST['option_repas']);
    }
    return $cart_item_data;
}

add_filter('woocommerce_get_item_data', 'afficher_option_repas_dans_panier', 10, 2);

function afficher_option_repas_dans_panier($item_data, $cart_item) {
    if (isset($cart_item['option_repas'])) {
        $item_data[] = array(
            'key' => __('Option de repas', 'woocommerce'),
            'value' => ucfirst(str_replace('_', ' ', $cart_item['option_repas']))
        );
    }
    return $item_data;
}

add_action('woocommerce_before_calculate_totals', 'ajuster_prix_panier', 10, 1);

function ajuster_prix_panier($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    // Récupérer les prix depuis les options
    $prix_repas_complet = floatval(get_option('prix_repas_complet', '7'));
    $prix_assiette_seulement = floatval(get_option('prix_assiette_seulement', '5'));

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if ($product->is_type('variation')) {
            $variation_id = $cart_item['variation_id'];
            $variation = wc_get_product($variation_id);
            
            if ($variation) {
                $attributes = $variation->get_attributes();
                if (isset($attributes['pa_type-de-repas'])) {
                    switch ($attributes['pa_type-de-repas']) {
                        case 'repas-complet':
                            $product->set_price($prix_repas_complet);
                            break;
                        case 'assiette-seulement':
                            $product->set_price($prix_assiette_seulement);
                            break;
                        // Vous pouvez ajouter d'autres cas ici si nécessaire
                    }
                }
            }
        }
    }
}