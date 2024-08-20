<?php
/**
 * Kadence Child Theme functions and definitions
 *
 * @package kadence-child
 */


require_once get_stylesheet_directory() . '/includes/admin-menus.php';
require_once get_stylesheet_directory() . '/includes/gestion-compte.php';
require_once get_stylesheet_directory() . '/includes/menu-custom.php';
require_once get_stylesheet_directory() . '/includes/gestion-panier.php';


function set_fixed_prices_for_menu_products($price, $product) {
    // Vérifiez si le produit appartient à la catégorie "menu"
    if (has_term('menu', 'product_cat', $product->get_id())) {
        // Vérifiez si c'est un produit variable
        if ($product->is_type('variation')) {
            $variation_attributes = $product->get_variation_attributes();
            // Vérifiez l'attribut 'pa_type-de-repas' (assurez-vous que c'est le bon slug)
            if (isset($variation_attributes['attribute_pa_type-de-repas'])) {
                switch ($variation_attributes['attribute_pa_type-de-repas']) {
                    case 'repas-complet':
                        return 8.00;
                    case 'assiette-seulement':
                        return 6.00;
                }
            }
        }
    }
    return $price;
}
add_filter('woocommerce_product_variation_get_price', 'set_fixed_prices_for_menu_products', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'set_fixed_prices_for_menu_products', 10, 2);



function apply_fixed_prices_to_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if (has_term('menu', 'product_cat', $product->get_parent_id())) {
            $variation_attributes = $product->get_variation_attributes();
            if (isset($variation_attributes['attribute_pa_type-de-repas'])) {
                switch ($variation_attributes['attribute_pa_type-de-repas']) {
                    case 'repas-complet':
                        $cart_item['data']->set_price(8.00);
                        break;
                    case 'assiette-seulement':
                        $cart_item['data']->set_price(6.00);
                        break;
                }
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'apply_fixed_prices_to_cart', 10, 1);


function display_fixed_prices_on_product_page($price, $product) {
    if (has_term('menu', 'product_cat', $product->get_id()) && $product->is_type('variable')) {
        $variation_prices = $product->get_variation_prices(true);
        if (!empty($variation_prices['price'])) {
            $min_price = min($variation_prices['price']);
            $max_price = max($variation_prices['price']);
            if ($min_price !== $max_price) {
                return wc_price($min_price) . ' - ' . wc_price($max_price);
            } else {
                return wc_price($min_price);
            }
        }
    }
    return $price;
}
add_filter('woocommerce_variable_price_html', 'display_fixed_prices_on_product_page', 10, 2);






add_action('woocommerce_before_calculate_totals', 'debug_price_hooks', 1);
function debug_price_hooks() {
    global $wp_filter;
    if (isset($wp_filter['woocommerce_before_calculate_totals'])) {
        foreach ($wp_filter['woocommerce_before_calculate_totals'] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callback_name = '';
                if (is_array($callback['function'])) {
                    if (is_object($callback['function'][0])) {
                        $callback_name = get_class($callback['function'][0]) . '->' . $callback['function'][1];
                    } else {
                        $callback_name = $callback['function'][0] . '::' . $callback['function'][1];
                    }
                } elseif (is_string($callback['function'])) {
                    $callback_name = $callback['function'];
                } else {
                    $callback_name = 'Closure';
                }
                error_log("Hook: woocommerce_before_calculate_totals, Priority: $priority, Function: " . $callback_name);
            }
        }
    }
}


function debug_cart_item_prices($cart) {
    error_log("Début du débogage des prix du panier");
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
        
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $type_repas = $variation->get_attribute('pa_type-de-repas');
        } else {
            $type_repas = $product->get_attribute('pa_type-de-repas');
        }
        
        $price = $product->get_price();
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        
        error_log("Produit ID: {$product->get_id()}, Variation ID: $variation_id, Type: $type_repas");
        error_log("Prix actuel: $price, Prix régulier: $regular_price, Prix promo: $sale_price");
    }
    error_log("Fin du débogage des prix du panier");
}
add_action('woocommerce_before_calculate_totals', 'debug_cart_item_prices', 20);






$gestion_panier_file = get_stylesheet_directory() . '/includes/gestion-panier.php';
if (file_exists($gestion_panier_file)) {
    require_once $gestion_panier_file;
} else {
    error_log("Le fichier gestion-panier.php n'a pas été trouvé dans " . get_stylesheet_directory());
}


function initialiser_hooks_personnalises() {
    if (function_exists('update_points_on_add_to_cart')) {
        add_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 20, 3);
    } else {
        error_log("La fonction update_points_on_add_to_cart n'existe pas.");
    }
    
    if (function_exists('add_custom_price_data')) {
        add_filter('woocommerce_add_cart_item_data', 'add_custom_price_data', 10, 2);
    }
    
    if (function_exists('add_child_name_to_cart_item')) {
        add_filter('woocommerce_add_cart_item_data', 'add_child_name_to_cart_item', 9, 3);
    }
}

// Initialiser nos hooks après que WooCommerce soit chargé
add_action('woocommerce_init', 'initialiser_hooks_personnalises');










function enqueue_custom_orderable_script() {
    wp_enqueue_script('custom-orderable', get_stylesheet_directory_uri() . '/js/custom-orderable.js', array('jquery'), null, true);
    wp_localize_script('custom-orderable', 'orderable_vars', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_orderable_script');


add_filter('woocommerce_product_categories', 'custom_variation_category_assignment', 10, 2);
function custom_variation_category_assignment($categories, $product) {
    if ($product->is_type('variable') && isset($_GET['variation_id'])) {
        $variation_id = intval($_GET['variation_id']);
        $variation = new WC_Product_Variation($variation_id);
        $variation_name = $variation->get_attribute('pa_repas'); // 'pa_repas' correspond au slug de votre attribut "Repas".

        if ($variation_name === 'Menu complet') {
            $categories = array('menu_complet');
        } elseif ($variation_name === 'Assiette seulement') {
            $categories = array('assiette');
        }
    }
    return $categories;
}




function ajouter_date_au_produit($cart_item_data, $product_id) {
    $product = wc_get_product($product_id);

    // Récupérer les informations sur la semaine et le jour
    $product_name = $product->get_name();
    if (preg_match('/Semaine (\d+) - (\w+)/', $product_name, $matches)) {
        $week_number = $matches[1];
        $day_name = strtolower($matches[2]);

        // Calculer la date
        $year = date('Y');
        $month = date('n');
        $dates = get_week_dates($week_number, $month, $year);
        $start_date = strtotime("$year-$month-{$dates['start']}");
        $day_offsets = [
            'lundi' => 0,
            'mardi' => 1,
            'mercredi' => 2,
            'jeudi' => 3,
            'vendredi' => 4,
            'samedi' => 5,
            'dimanche' => 6
        ];

        if (isset($day_offsets[$day_name])) {
            setlocale(LC_TIME, 'fr_FR.UTF-8');
            $date = strftime('%e %B', strtotime("+{$day_offsets[$day_name]} days", $start_date));
            $cart_item_data['item_date'] = $date;
        }
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_child_name_to_cart_item', 10, 3);





// Sauvegarder le nom des enfants dans les méta-données de l'article de la commande


// Assurez-vous que cette fonction n'existe pas déjà dans gestion-panier.php
if (!function_exists('reduce_points')) {
    function reduce_points($user_id, $points_to_reduce) {
        if (class_exists('RS_Points_Data')) {
            $points_data = new RS_Points_Data($user_id);
            $current_points = $points_data->total_available_points();
            $new_points = max(0, $current_points - $points_to_reduce);
            
            $result = $points_data->update_meta($user_id, 'points', $new_points);
            
            error_log("Reduced points. Old: $current_points, New: $new_points, Result: " . ($result ? 'success' : 'failure'));
            
            return $result;
        }
        return false;
    }
}

// Modifier la fonction update_points_on_add_to_cart pour utiliser reduce_points

remove_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10);
add_filter('woocommerce_add_cart_item_data', 'update_points_on_add_to_cart', 10, 3);

function get_week_dates($week_number, $month, $year) {
    $first_day_of_month = strtotime("$year-$month-01");
    $first_week_day = date('N', $first_day_of_month); // Numéro du jour de la semaine (1 pour Lundi, 7 pour Dimanche)
    
    // Calculer le décalage du premier jour de la première semaine
    $offset = ($week_number - 1) * 7 - ($first_week_day - 1);
    $start_date = strtotime("$offset days", $first_day_of_month);
    $end_date = strtotime("+4 days", $start_date); // Fin de semaine après 4 jours
    
    // Ajuster les dates si elles dépassent le mois
    $start_date = max($start_date, $first_day_of_month);
    $end_date = min($end_date, strtotime("last day of $year-$month"));
    
    // Si le début de la semaine est avant le mois en cours, ajuster
    if (date('n', $start_date) < $month) {
        $start_date = strtotime("last monday of previous month", $first_day_of_month);
    }

    return [
        'start' => date('d', $start_date),
        'end' => date('d', $end_date)
    ];
}



// Vérifier et afficher correctement les points dans le mini-panier
function afficher_points_dans_panier($product_name, $cart_item, $cart_item_key) {
    $points_display = isset($cart_item['line_total_points']) ? $cart_item['line_total_points'] : '0';
    return $product_name . ' - ' . $points_display . ' Point(s) repas';
}
add_filter('woocommerce_cart_item_name', 'afficher_points_dans_panier', 10, 3);




function afficher_date_dans_panier($product_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['item_date'])) {
        $product_name .= ' - ' . $cart_item['item_date'];
    }
    return $product_name;
}
add_filter('woocommerce_cart_item_name', 'afficher_date_dans_panier', 10, 3);








// Afficher les données personnalisées dans l'administration
add_action('woocommerce_admin_order_item_headers', 'add_custom_admin_order_item_headers');
function add_custom_admin_order_item_headers() {
    echo '<th>Date personnalisée</th>';
}

add_action('woocommerce_admin_order_item_values', 'add_custom_admin_order_item_values', 10, 3);
function add_custom_admin_order_item_values($product, $item, $item_id) {
    $custom_data = $item->get_meta('_custom_product_date');
    if ($custom_data) {
        $week_number = $custom_data['week_number'];
        $start_date = $custom_data['start_date'];
        
        $date_obj = new DateTime($start_date);
        $formatted_date = $date_obj->format('j F Y');
        $day_name = $date_obj->format('l');
        
        echo "<td>Semaine {$week_number} - " . strtoupper($day_name) . " ({$formatted_date})</td>";
    } else {
        echo '<td>-</td>';
    }
}


add_action('woocommerce_add_to_cart', 'clear_points_cache', 10, 0);
add_action('woocommerce_add_to_cart', 'update_points_on_add_to_cart', 10, 6);
add_action('woocommerce_cart_item_removed', 'clear_points_cache', 10, 0);

function clear_points_cache() {
    if (class_exists('RS_Points_Data')) {
        $user_id = get_current_user_id();
        $points_data = new RS_Points_Data($user_id);
        if (method_exists($points_data, 'clear_cache')) {
            $points_data->clear_cache();
        }
    }
}


add_action('woocommerce_add_to_cart', 'trigger_points_update', 10, 0);
add_action('woocommerce_cart_item_removed', 'trigger_points_update', 10, 0);

function trigger_points_update() {
    do_action('update_points_display');
}


function get_user_points() {
    $points_string = do_shortcode('[rs_my_reward_points]');
    preg_match('/\d+/', $points_string, $matches);
    $points = isset($matches[0]) ? intval($matches[0]) : 0;
    error_log('Current points from shortcode: ' . $points);
    return $points;
}



function update_points_display() {
    $points = get_user_points();
    error_log('Points sent in AJAX response: ' . $points);
    wp_send_json_success(array('points' => $points));
}
add_action('wp_ajax_update_points_display', 'update_points_display');
add_action('wp_ajax_nopriv_update_points_display', 'update_points_display');





function enqueue_wc_cart_fragments() {
    if (function_exists('is_woocommerce')) {
        wp_enqueue_script('wc-cart-fragments');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_wc_cart_fragments');


function enqueue_cart_points_script() {
    wp_enqueue_script('cart-points', get_template_directory_uri() . '/js/cart-points.js', array('jquery', 'wc-cart-fragments'), '1.0', true);

    // Localize the script with new data
    $script_data_array = array(
        'ajax_url' => admin_url('admin-ajax.php'),
    );
    wp_localize_script('cart-points', 'cart_points_vars', $script_data_array);
}
add_action('wp_enqueue_scripts', 'enqueue_cart_points_script');






add_action('woocommerce_review_order_before_payment', 'debug_payment_methods');

function debug_payment_methods() {
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    error_log('Available payment methods: ' . print_r($available_gateways, true));
}




// Ajouter la page de paramètres dans WooCommerce
if (!function_exists('ajouter_page_parametres_repas')) {
    function ajouter_page_parametres_repas() {
        add_submenu_page(
            'woocommerce',
            'Paramètres Repas',
            'Paramètres Repas',
            'manage_options',
            'parametres-repas',
            'afficher_page_parametres_repas'
        );
    }
    add_action('admin_menu', 'ajouter_page_parametres_repas');
}

// Afficher la page de paramètres
if (!function_exists('afficher_page_parametres_repas')) {
    function afficher_page_parametres_repas() {
        ?>
        <div class="wrap">
            <h1>Paramètres Repas</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('parametres_repas_options');
                do_settings_sections('parametres-repas');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialiser les paramètres
if (!function_exists('initialiser_parametres_repas')) {
    function initialiser_parametres_repas() {
        register_setting('parametres_repas_options', 'prix_repas_uniquement');

        add_settings_section(
            'parametres_repas_section',
            'Paramètres de prix des repas',
            'afficher_parametres_repas_section',
            'parametres-repas'
        );

        add_settings_field(
            'prix_repas_uniquement',
            'Prix Repas Uniquement',
            'afficher_champ_prix_repas_uniquement',
            'parametres-repas',
            'parametres_repas_section'
        );
    }
    add_action('admin_init', 'initialiser_parametres_repas');
}

// Afficher la section des paramètres
if (!function_exists('afficher_parametres_repas_section')) {
    function afficher_parametres_repas_section() {
        echo 'Entrez le prix pour "Repas uniquement"';
    }
}

// Afficher le champ de saisie du prix
if (!function_exists('afficher_champ_prix_repas_uniquement')) {
    function afficher_champ_prix_repas_uniquement() {
        $prix = get_option('prix_repas_uniquement');
        echo '<input type="number" name="prix_repas_uniquement" value="' . esc_attr($prix) . '" step="0.01" min="0">';
    }
}



// Afficher le nom des enfants dans l'interface d'administration de WooCommerce
function display_child_names_order_item_meta( $item_id, $item, $product ) {
    if ( $nom_enfant = wc_get_order_item_meta( $item_id, 'nom_enfant' ) ) {
        echo '<p><strong>' . __( 'Nom des enfants', 'woocommerce' ) . ':</strong> ' . esc_html( $nom_enfant ) . '</p>';
    }
}
add_action( 'woocommerce_order_item_meta_end', 'display_child_names_order_item_meta', 10, 3 );

// Afficher le nom de l'enfant dans l'interface d'administration de WooCommerce
function display_child_name_order_item_meta( $item_id, $item, $product ) {
    if ( $nom_enfant = wc_get_order_item_meta( $item_id, 'nom_enfant' ) ) {
        echo '<p><strong>' . __( 'Nom de l\'enfant', 'woocommerce' ) . ':</strong> ' . esc_html( $nom_enfant ) . '</p>';
    }
}
add_action( 'woocommerce_order_item_meta_end', 'display_child_name_order_item_meta', 10, 3 );

// Afficher le nom de l'enfant dans la page de commande (admin)
add_action('woocommerce_admin_order_data_after_order_details', 'display_child_name_in_admin_order', 10, 1);
function display_child_name_in_admin_order($order) {
    foreach ($order->get_items() as $item_id => $item) {
        if ($item->get_meta('nom_enfant')) {
            echo '<p><strong>' . __('Nom de l\'enfant', 'woocommerce' ) . ':</strong> ' . $item->get_meta('nom_enfant') . '</p>';
        }
    }
}





// Ajax handler for custom add to cart
add_action('wp_ajax_custom_add_to_cart', 'custom_add_to_cart');
add_action('wp_ajax_nopriv_custom_add_to_cart', 'custom_add_to_cart');


// Fonction de débogage pour l'ajout au panier
function debug_add_to_cart($cart_item_data, $product_id, $variation_id) {
    error_log("Debug - Adding to cart: Product ID: $product_id, Variation ID: $variation_id");
    error_log("Debug - Cart item data: " . print_r($cart_item_data, true));
    
    // Vérifier le prix du produit
    $product = wc_get_product($product_id);
    $price = $product->get_price();
    error_log("Debug - Product price: $price");
    
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'debug_add_to_cart', 10, 3);

// Fonction pour modifier l'affichage dans le mini-cart
function custom_mini_cart_item($item_name, $cart_item) {
    if (!is_array($cart_item) || !isset($cart_item['data']) || !is_object($cart_item['data'])) {
        return $item_name;
    }

    $product_name = $cart_item['data']->get_name();
    $child_name = isset($cart_item['nom_enfant']) ? $cart_item['nom_enfant'] : 'N/A';
    $is_free_meal = isset($cart_item['free_meal']) && $cart_item['free_meal'];
    $price = $is_free_meal ? '1 repas' : wc_price($cart_item['data']->get_price());
    
    $output = $product_name . ' - ' . $child_name . '<br>' . $price;
    
    error_log("Debug - Custom mini-cart item: " . $output);
    
    return $output;
}
add_filter('woocommerce_cart_item_name', 'custom_mini_cart_item', 9999, 2);

// Fonction pour modifier le prix du produit dans le panier
function modify_cart_item_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $is_free_meal = isset($cart_item['free_meal']) ? $cart_item['free_meal'] : false;
        $child_name = isset($cart_item['nom_enfant']) ? $cart_item['nom_enfant'] : 'N/A';
        $original_price = isset($cart_item['original_price']) ? $cart_item['original_price'] : $product->get_price();

        error_log("Debug - Modifying cart item: Key: $cart_item_key, Child: $child_name, Original price: $original_price, Is free meal: " . ($is_free_meal ? 'Yes' : 'No'));

        if ($is_free_meal) {
            $cart_item['data']->set_price(0);
        } else {
            $cart_item['data']->set_price($original_price);
        }

        error_log("Debug - Final price: " . $cart_item['data']->get_price());
    }
}
add_action('woocommerce_before_calculate_totals', 'modify_cart_item_price', 9999);







function debug_cart_item_data($cart_item_data, $product_id, $variation_id) {
    error_log("Debug - Cart Item Data avant ajout au panier: " . print_r($cart_item_data, true));
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'debug_cart_item_data', 10, 3);

// Fonction pour ajouter au panier
function custom_add_to_cart() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $child_name = isset($_POST['child_name']) ? sanitize_text_field($_POST['child_name']) : '';
    $type_repas = isset($_POST['type_repas']) ? sanitize_text_field($_POST['type_repas']) : '';

    if (!$product_id || empty($child_name)) {
        wp_send_json_error('Données invalides');
        return;
    }

    $user_id = get_current_user_id();
    $free_meals = intval(get_user_meta($user_id, 'points_repas_' . sanitize_key($child_name), true));
    $fondation_member = get_user_meta($user_id, 'fondation_member_' . sanitize_key($child_name), true);
    
    $is_free_meal = ($free_meals > 0 || $fondation_member == '1');
    
    $cart_item_data = array(
        'child_name' => $child_name,
        'free_meal' => $is_free_meal,
        'type_repas' => $type_repas
    );

    error_log("Debug - Adding to cart: Product ID: $product_id, Variation ID: $variation_id, Child: $child_name, Type repas: $type_repas, Is free meal: " . ($is_free_meal ? 'Yes' : 'No'));

    $cart_item_key = WC()->cart->add_to_cart($product_id, 1, $variation_id, array(), $cart_item_data);

    if ($cart_item_key) {
        if ($is_free_meal) {
            WC()->cart->cart_contents[$cart_item_key]['data']->set_price(0);
        }
        if ($free_meals > 0 && !$fondation_member) {
            update_user_meta($user_id, 'points_repas_' . sanitize_key($child_name), $free_meals - 1);
        }
        wp_send_json_success('Produit ajouté au panier.');
    } else {
        wp_send_json_error('Erreur lors de l\'ajout au panier');
    }
}
add_action('wp_ajax_custom_add_to_cart', 'custom_add_to_cart');
add_action('wp_ajax_nopriv_custom_add_to_cart', 'custom_add_to_cart');


function custom_cart_item_price_html($price_html, $cart_item, $cart_item_key) {
    if (isset($cart_item['free_meal']) && $cart_item['free_meal']) {
        return '1 repas';
    } else {
        return wc_price($cart_item['data']->get_price());
    }
}
add_filter('woocommerce_cart_item_price', 'custom_cart_item_price_html', 10, 3);




// Afficher les points utilisateur
function display_user_points() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();

        if (class_exists('RS_Points_Data')) {
            $points_data = new RS_Points_Data($user_id);
            $points = $points_data->total_available_points();

            return '<div class="user-points" style="text-align: center; margin: 20px auto; padding: 10px; font-size: 16px; font-weight: bold; background-color: #000; border: 2px solid #b3d7ff; border-radius: 5px; max-width: 80%;">Points disponibles : ' . intval($points) . '  &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;<a href="/achat-de-repas/" class="button">Acheter des repas</a></div>';
        } else {
            return '<div class="user-points" style="text-align: center; color: red;">Erreur : Impossible de récupérer les points.</div>';
        }
    } else {
        return '<div class="user-points" style="text-align: center;">Veuillez vous connecter pour voir vos points.</div>';
    }
}
add_shortcode('user_points', 'display_user_points');

// Fonction pour vérifier si un produit a été commandé pour un enfant spécifique
function has_ordered_product($product_id, $child_name) {
    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = get_current_user_id();
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'),
        'limit' => -1 // Pour s'assurer de récupérer toutes les commandes
    ));

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $item_product_id = $item->get_product_id();
            $item_child_name = $item->get_meta('nom_enfant');
            
            // Vérifiez aussi les variations si c'est un produit variable
            if ($item_product_id != $product_id) {
                $product = wc_get_product($item_product_id);
                if ($product && $product->is_type('variation')) {
                    $item_product_id = $product->get_parent_id();
                }
            }

            if ($item_product_id == $product_id && $item_child_name == $child_name) {
                return true;
            }
        }
    }

    return false;
}

// Fonction pour personnaliser l'affichage dans le mini-cart
function custom_mini_cart_item_name($product_name, $cart_item, $cart_item_key) {
    $child_name = isset($cart_item['nom_enfant']) ? ' - ' . esc_html($cart_item['nom_enfant']) : '';
    $is_free_meal = isset($cart_item['free_meal']) && $cart_item['free_meal'];
    $price = $is_free_meal ? '1 repas' : wc_price($cart_item['data']->get_price());
    
    $output = $product_name . $child_name . '<br>' . $price;
    
    error_log("Debug - Mini-cart item: " . $output);
    
    return $output;
}
add_filter('woocommerce_cart_item_name', 'custom_mini_cart_item_name', 9999, 3);

// Fonction pour supprimer l'affichage de la quantité dans le mini-cart
function remove_mini_cart_quantity($html, $cart_item, $cart_item_key) {
    if (wp_doing_ajax()) {
        return '';
    }
    return $html;
}
add_filter('woocommerce_cart_item_quantity', 'remove_mini_cart_quantity', 9999, 3);

// Fonction pour modifier le prix affiché dans le mini-cart
function custom_mini_cart_item_price($price_html, $cart_item, $cart_item_key) {
    if (isset($cart_item['free_meal']) && $cart_item['free_meal']) {
        return '1 repas';
    }
    return $price_html;
}
add_filter('woocommerce_cart_item_price', 'custom_mini_cart_item_price', 9999, 3);


function modify_product_price_in_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $prix_repas_complet = floatval(get_option('prix_repas_complet', 12));
    $prix_assiette_seulement = floatval(get_option('prix_assiette_seulement', 66));

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $type_repas = '';

        if ($product->is_type('variation')) {
            $variation_attributes = $product->get_variation_attributes();
            $type_repas = isset($variation_attributes['attribute_repas']) ? $variation_attributes['attribute_repas'] : '';
        } else {
            $type_repas = $product->get_attribute('repas');
        }

        // Si le type de repas n'est toujours pas défini, essayez de le récupérer des données du panier
        if (empty($type_repas) && isset($cart_item['variation']['attribute_repas'])) {
            $type_repas = $cart_item['variation']['attribute_repas'];
        }

        error_log("Debug - Cart item: Key: $cart_item_key, Type repas: $type_repas");

        $original_price = $product->get_price();
        error_log("Avant modification - Produit ID: {$product->get_id()}, Type: $type_repas, Prix original: $original_price");

        if (strpos(strtolower($type_repas), 'complet') !== false) {
            $new_price = $prix_repas_complet;
        } elseif (strpos(strtolower($type_repas), 'assiette') !== false) {
            $new_price = $prix_assiette_seulement;
        } else {
            $new_price = $prix_repas_complet; // Prix par défaut si le type n'est pas reconnu
            error_log("Type de repas non reconnu: $type_repas");
        }

        $product->set_price($new_price);
        error_log("Après modification - Nouveau prix appliqué: $new_price");
    }
}


add_action('woocommerce_before_calculate_totals', 'modify_product_price_in_cart', 10, 1);
function debug_cart_contents($cart) {
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
        $type_repas = $variation_id ? wc_get_product($variation_id)->get_attribute('pa_type-de-repas') : $product->get_attribute('pa_type-de-repas');
        $price = $product->get_price();
        error_log("Cart Item Debug - Product ID: {$product->get_id()}, Variation ID: $variation_id, Type: $type_repas, Price: $price");
    }
}
add_action('woocommerce_before_calculate_totals', 'debug_cart_contents', 30);


function debug_cart_data() {
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        error_log("Détails de l'article du panier:");
        error_log("Produit ID: " . $cart_item['product_id']);
        error_log("Variation ID: " . $cart_item['variation_id']);
        error_log("Type de repas: " . (isset($cart_item['type_repas']) ? $cart_item['type_repas'] : 'Non défini'));
        error_log("Prix: " . $cart_item['data']->get_price());
    }
}
add_action('woocommerce_before_calculate_totals', 'debug_cart_data', 1);

function check_and_fix_product_price($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) {
        return false;
    }

    $price = $product->get_price();
    
    if ($price == 0 || $price == '') {
        // Définir un prix par défaut ou le récupérer d'une option
        $default_price = get_option('default_product_price', 10); // 10 est le prix par défaut si l'option n'est pas définie
        $product->set_price($default_price);
        $product->save();
        return $default_price;
    }
    
    return $price;
}


function apply_custom_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $price = check_and_fix_product_price($product_id);
        
        if ($price !== false) {
            $cart_item['data']->set_price($price);
        }
    }
}





function debug_product_attributes($cart) {
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
        
        error_log("Débogage des attributs - Produit ID: {$product->get_id()}, Variation ID: $variation_id");
        
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $attributes = $variation->get_attributes();
        } else {
            $attributes = $product->get_attributes();
        }
        
        error_log("Attributs: " . print_r($attributes, true));
        
        if (isset($cart_item['variation'])) {
            error_log("Données de variation: " . print_r($cart_item['variation'], true));
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'debug_product_attributes', 5);


function debug_cart_prices() {
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $price = $product->get_price();
        $is_free_meal = isset($cart_item['free_meal']) ? $cart_item['free_meal'] : 'N/A';
        $child_name = isset($cart_item['nom_enfant']) ? $cart_item['nom_enfant'] : 'N/A';
        error_log("Cart Item: Product ID {$cart_item['product_id']}, Child: {$child_name}, Price: {$price}, Free Meal: {$is_free_meal}");
    }
}
add_action('woocommerce_before_calculate_totals', 'debug_cart_prices', 20);




// Fonction pour obtenir les enfants de l'utilisateur
function get_user_children() {
    $user_id = get_current_user_id();
    $enfants = get_user_meta($user_id, 'enfants', true);
    return is_array($enfants) ? $enfants : array();
}


// Affichage des produits avec condition
function display_product_with_add_button($product_id) {
    $product = wc_get_product($product_id);
    $user_children = get_user_children();

    echo '<div class="product">';
    echo '<h2>' . $product->get_name() . '</h2>';

    foreach ($user_children as $child) {
        $child_name = esc_html($child['nom']);
        echo '<button class="add-to-cart" data-product-id="' . $product_id . '" data-child-name="' . $child_name . '">Ajouter pour ' . $child_name . '</button>';
    }

    echo '</div>';
}

// Exemple de fonction pour afficher les produits dans un shortcode
function display_products() {
    $products = wc_get_products(array('limit' => -1)); // Obtenez tous les produits ou ajustez en fonction de vos besoins

    foreach ($products as $product) {
        display_product_with_add_button($product->get_id());
    }
}
add_shortcode('display_products', 'display_products');

// Inclure le fichier JavaScript boutonachat.js
function enqueue_boutonachat_scripts() {
    wp_enqueue_script('boutonachat-js', get_stylesheet_directory_uri() . '/js/boutonachat.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_boutonachat_scripts');




// Fonction pour initialiser WooCommerce
function my_woocommerce_function() {
    if (!function_exists('WC') || !WC()) {
        return;
    }
    // Votre code WooCommerce ici
}

error_reporting(E_ALL & ~E_DEPRECATED);
add_action('plugins_loaded', 'my_woocommerce_function');

// Enqueue scripts and styles.
function mon_theme_enqueue_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
}
add_action( 'wp_enqueue_scripts', 'mon_theme_enqueue_scripts' );



function display_child_name_in_cart($item_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['nom_enfant'])) {
        $item_name .= ' - ' . esc_html($cart_item['nom_enfant']);
    }
    return $item_name;
}
add_filter('woocommerce_cart_item_name', 'display_child_name_in_cart', 10, 3);


function remove_quantity_in_cart($product_quantity, $cart_item_key, $cart_item) {
    return '';
}
add_filter('woocommerce_cart_item_quantity', 'remove_quantity_in_cart', 10, 3);



// Ajoutez vos autres fonctions ici...





?>