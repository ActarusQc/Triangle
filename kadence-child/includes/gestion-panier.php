<?php

$user_id = get_current_user_id();

// Ajouter des données personnalisées lors de l'ajout au panier



function add_custom_cart_item_data($cart_item_data, $product_id, $variation_id) {
    $week_number = isset($_POST['week_number']) ? sanitize_text_field($_POST['week_number']) : '';
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    
    if (!empty($week_number) && !empty($start_date)) {
        $cart_item_data['custom_data'] = array(
            'week_number' => $week_number,
            'start_date' => $start_date
        );
    }
    
    return $cart_item_data;
}

add_filter('woocommerce_add_cart_item_data', 'add_custom_cart_item_data', 10, 3);
add_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 20, 3);



// Modifier le titre du produit dans le panier et la commande
add_filter('woocommerce_cart_item_name', 'custom_cart_item_name', 10, 3);
add_filter('woocommerce_order_item_name', 'custom_cart_item_name', 10, 2);
function custom_cart_item_name($name, $cart_item, $cart_item_key = null) {
    
    
    if (isset($cart_item['custom_data'])) {
        $week_number = $cart_item['custom_data']['week_number'];
        $start_date = $cart_item['custom_data']['start_date'];
        
        $date_obj = new DateTime($start_date);
        $formatted_date = $date_obj->format('j F Y');
        $day_name = $date_obj->format('l');
        
        $new_name = "Semaine {$week_number} - " . strtoupper($day_name) . " - {$name} ({$formatted_date})";
        
        return $new_name;
    }
   
    return $name;
}

// Sauvegarder les données personnalisées dans la commande
add_action('woocommerce_checkout_create_order_line_item', 'save_custom_order_item_meta', 10, 4);
function save_custom_order_item_meta($item, $cart_item_key, $values, $order) {
    if (isset($values['custom_data'])) {
        $item->update_meta_data('_custom_product_date', $values['custom_data']);
    }
}

// Cacher les boutons de quantité dans le mini panier
function custom_hide_quantity_buttons_css() {
    echo '<style>
        .woocommerce-mini-cart .quantity .minus, 
        .woocommerce-mini-cart .quantity .plus {
            display: none !important;
        }
    </style>';
}
add_action('wp_head', 'custom_hide_quantity_buttons_css');

function add_repas_option_to_cart_item($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['repas_option'])) {
        $cart_item_data['repas_option'] = sanitize_text_field($_POST['repas_option']);
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_repas_option_to_cart_item', 10, 3);

// Mettre à jour les options de paiement en fonction de l'option repas sélectionnée
add_filter('woocommerce_available_payment_gateways', 'conditional_payment_gateways');

function conditional_payment_gateways($available_gateways) {
    if (!is_admin()) {
        $total = WC()->cart->total;

        // Si le total est égal à 1 repas (supposons que c'est 7.99$)
        if ($total == 7.00) {
            // Garder seulement la méthode de paiement par points SUMO
            if (isset($available_gateways['sumo_points'])) {
                return array('sumo_points' => $available_gateways['sumo_points']);
            }
        } 
        // Si le total est supérieur à 1 repas
        elseif ($total < 7.00) {
            // Garder seulement la méthode de paiement par transfert bancaire
            if (isset($available_gateways['bank_transfer'])) {
                return array('bank_transfer' => $available_gateways['bank_transfer']);
            }
        }
    }
    return $available_gateways;
}

add_action('woocommerce_review_order_before_payment', 'refresh_payment_methods');

function refresh_payment_methods() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Mettre à jour les options de paiement lors de la sélection de l'option repas
        $('body').on('change', '.repas-options select', function() {
            $('body').trigger('update_checkout');
        });
    });
    </script>
    <?php
}



// Ajouter des données personnalisées au produit lors de l'ajout au panier
function ajouter_donnees_personnalisees_au_panier($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['children']) && is_array($_POST['children'])) {
        $cart_item_data['children'] = array_map('sanitize_text_field', $_POST['children']);
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'ajouter_donnees_personnalisees_au_panier', 10, 3);



// Modifier le prix du produit dans le panier
function modifier_prix_produit_panier($cart_object) {
    foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['repas_option']) && $cart_item['repas_option'] === 'argent') {
            $prix_repas_uniquement = get_option('prix_repas_uniquement', 0);
            $cart_item['data']->set_price($prix_repas_uniquement);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'modifier_prix_produit_panier', 10, 1);

// Fonction pour mettre à jour les points disponibles lorsque le produit est ajouté au panier
function update_points_on_add_to_cart($cart_item_data, $product_id, $variation_id) {
   

    $user_id = get_current_user_id();
    $current_points = get_user_points();
    
   
    
    if ($current_points > 0) {
        // Réduire les points de 1
        $new_points = $current_points - 1;
        
        // Utiliser la méthode update_meta de RS_Points_Data
        $points_data = new RS_Points_Data($user_id);
        $result = $points_data->update_meta($user_id, 'points', $new_points);
        
        
        
        // Vérifier si les points ont été mis à jour
        $updated_points = get_user_points();
        
        
        if ($updated_points == $new_points) {
          
        } else {
           
        }
    } else {
      
    }

    return $cart_item_data;
}

function log_rs_points_data_methods() {
    if (class_exists('RS_Points_Data')) {
        $user_id = get_current_user_id();
        $points_data = new RS_Points_Data($user_id);
        $methods = get_class_methods($points_data);
       
    }
}

function manually_reduce_points($user_id, $points_to_reduce) {
    if (class_exists('RS_Points_Data')) {
        $points_data = new RS_Points_Data($user_id);
        $current_points = $points_data->total_available_points();
        $new_points = max(0, $current_points - $points_to_reduce);
        
        // Utilisez update_user_meta directement
        $result = update_user_meta($user_id, 'points', $new_points);
        
        
        
        return $result;
    }
    return false;
}

// Utilisez cette fonction dans update_points_on_add_to_cart
if (manually_reduce_points($user_id, 1)) {
   
} else {
    
}

// Appelez cette fonction au début de update_points_on_add_to_cart
log_rs_points_data_methods();

remove_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10);
add_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10, 3);

// Fonction pour mettre à jour les points disponibles lorsque le produit est retiré du panier
function update_points_on_remove_from_cart($cart_item_key) {
   
    $cart = WC()->cart->get_cart();
    if (isset($cart[$cart_item_key])) {
        $product_id = $cart[$cart_item_key]['product_id'];
        // Assurez-vous que le produit fait partie de la catégorie "menu" ou vérifiez le produit approprié
        if (has_term('menu', 'product_cat', $product_id)) {
           
            $user_id = get_current_user_id();
           
            if (class_exists('RS_Points_Data')) {
               
                $points_data = new RS_Points_Data($user_id);
                if (method_exists($points_data, 'total_available_points')) {
                    $points = $points_data->total_available_points();
                    $new_points = $points + 1;
                    update_user_meta($user_id, 'rsrewards_points', $new_points);
                    
                } else {
                   
                }
            } else {
               
            }
        }
    }
}
add_action('woocommerce_cart_item_removed', 'update_points_on_remove_from_cart', 10, 1);

// Fonction pour mettre à jour les points Sumo
function update_sumo_points() {
    if (!class_exists('RS_Points_Data')) {
        wp_send_json_error(array('message' => 'Sumo Rewards not active'));
        return;
    }

    $user_id = get_current_user_id();
    $points_data = new RS_Points_Data($user_id);
    $current_points = $points_data->total_available_points();

    $update_type = $_POST['update_type'];
    
    if ($update_type === 'decrease') {
        $new_points = max(0, $current_points - 1);
        $points_data->set_points($new_points); // Assurez-vous que cette méthode existe dans Sumo Rewards
    } elseif ($update_type === 'increase') {
        $new_points = $current_points + 1;
        $points_data->set_points($new_points); // Assurez-vous que cette méthode existe dans Sumo Rewards
    }

    wp_send_json_success(array('points' => $new_points));
}
add_action('wp_ajax_update_sumo_points', 'update_sumo_points');
add_action('wp_ajax_nopriv_update_sumo_points', 'update_sumo_points'); 

function enqueue_lightbox2() {
    wp_enqueue_style('lightbox2-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
    wp_enqueue_script('lightbox2-js', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js', array('jquery'), '2.11.3', true);
}
add_action('wp_enqueue_scripts', 'enqueue_lightbox2');

function afficher_bouton_procedure() {
    echo '<div style="text-align: center; margin-top: 20px; margin-bottom: 40px;">
            <a href="/procedure/" style="background-color: black; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold; border-radius: 5px;">
                Si c\'est votre première connexion, cliquez ici pour voir la procédure
            </a>
          </div>';
}
add_action('woocommerce_account_edit-account_endpoint', 'afficher_bouton_procedure', 5);

function apply_custom_discount_for_bank_transfer( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // Vérifiez si le mode de paiement est Virement bancaire
    if ( isset( $_POST['payment_method'] ) && $_POST['payment_method'] === 'bacs' ) {
        $discount = 0;

        // Parcourez les articles du panier pour vérifier la catégorie
        foreach ( $cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];
            $product = wc_get_product( $product_id );

            if ( has_term( '10-repas', 'product_cat', $product_id ) ) {
                $discount += 1;
                break; // Appliquer le rabais une seule fois
            }
            if ( has_term( '20-repas', 'product_cat', $product_id ) ) {
                $discount += 3;
                break; // Appliquer le rabais une seule fois
            }
            if ( has_term( '30-repas', 'product_cat', $product_id ) ) {
                $discount += 5;
                break; // Appliquer le rabais une seule fois
            }
            if ( has_term( '40-repas', 'product_cat', $product_id ) ) {
                $discount += 10;
                break; // Appliquer le rabais une seule fois
            }
            if ( has_term( '50-repas', 'product_cat', $product_id ) ) {
                $discount += 15;
                break; // Appliquer le rabais une seule fois
            }
        }

        if ( $discount > 0 ) {
            // Ajoutez le rabais au panier
            $cart->add_fee( __( 'Rabais Virement Interac', 'woocommerce' ), -$discount );
        }
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'apply_custom_discount_for_bank_transfer' );

function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

function register_my_account_menu() {
    register_nav_menu('mon-compte',__( 'Menu Mon Compte' ));
}
add_action( 'init', 'register_my_account_menu' );

function register_my_account_sidebar() {
    register_sidebar( array(
        'name'          => __( 'Mon Compte', 'kadence' ),
        'id'            => 'mon-compte',
        'description'   => __( 'Widgets in this area will be shown on the My Account page.', 'kadence' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'register_my_account_sidebar' );

function adjust_account_content() {
    if (is_account_page()) {
        remove_action('woocommerce_account_content', 'woocommerce_account_content');
        add_action('woocommerce_account_content', 'custom_account_content');
    }
}
add_action('template_redirect', 'adjust_account_content');

function custom_account_content() {
    woocommerce_output_all_notices();
    if (isset($_GET['view-order'])) {
        wc_get_template('myaccount/view-order.php', array(
            'order_id' => $_GET['view-order']
        ));
    } else {
        woocommerce_account_content();
    }
}

// Ajouter le nom de l'enfant aux méta-données du panier
function add_child_name_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
    if( isset($_POST['child_name']) ) {
        $cart_item_data['nom_enfant'] = sanitize_text_field($_POST['child_name']);
    }
    return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'add_child_name_to_cart_item', 10, 3 );

// Enregistrer les méta-données de l'article de la commande
function save_child_names_order_item_meta( $item_id, $values, $cart_item_key ) {
    if ( isset( $values['nom_enfant'] ) ) {
        wc_add_order_item_meta( $item_id, 'nom_enfant', $values['nom_enfant'] );
    }
}
add_action( 'woocommerce_add_order_item_meta', 'save_child_names_order_item_meta', 10, 3 );

// Sauvegarder le nom des enfants dans les méta-données de l'article de la commande
function save_cart_item_custom_data_in_order( $item_id, $values, $cart_item_key ) {
    if ( isset( $values['nom_enfant'] ) ) {
        wc_add_order_item_meta( $item_id, 'nom_enfant', $values['nom_enfant'] );
    }
}
add_action( 'woocommerce_checkout_create_order_line_item', 'save_cart_item_custom_data_in_order', 10, 3 );

// Fonction pour réduire les points
function reduce_points($user_id, $points_to_reduce) {
    if (class_exists('RS_Points_Data')) {
        $points_data = new RS_Points_Data($user_id);
        $current_points = $points_data->total_available_points();
        $new_points = max(0, $current_points - $points_to_reduce);
        
        $result = $points_data->update_meta($user_id, 'points', $new_points);
        
        
        
        return $result;
    }
    return false;
}


// Assurez-vous de supprimer l'ancien filtre avant d'ajouter le nouveau
remove_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10);
add_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10, 3);