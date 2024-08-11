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
require_once get_stylesheet_directory() . '/includes/variation.php';





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
add_filter('woocommerce_add_cart_item_data', 'ajouter_date_au_produit', 10, 2);




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



function calculate_cart_totals() {
    $cart = WC()->cart;
    $cart_total = 0;
    $free_meals_used = 0;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];
        $price = $product->get_price();
        
        $user_id = get_current_user_id();
        $free_meals = intval(get_user_meta($user_id, 'points_repas', true));
        
        if ($free_meals > 0) {
            $free_meals_for_this_item = min($free_meals - $free_meals_used, $quantity);
            $paid_meals = $quantity - $free_meals_for_this_item;
            
            $cart_total += $price * $paid_meals;
            $free_meals_used += $free_meals_for_this_item;
            
            // Mettre à jour les métadonnées de l'article du panier
            $cart_item['free_meals'] = $free_meals_for_this_item;
            $cart->cart_contents[$cart_item_key] = $cart_item;
        } else {
            $cart_total += $price * $quantity;
        }
    }
    
    $cart->set_total($cart_total);
}

add_action('woocommerce_before_calculate_totals', 'calculate_cart_totals', 10, 1);

function display_free_meals_in_cart($product_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['free_meals']) && $cart_item['free_meals'] > 0) {
        $product_name .= sprintf('<br><small>(%d repas gratuits appliqués)</small>', $cart_item['free_meals']);
    }
    return $product_name;
}

add_filter('woocommerce_cart_item_name', 'display_free_meals_in_cart', 10, 3);




function display_free_meals_on_product_page() {
    $user_id = get_current_user_id();
    $free_meals = intval(get_user_meta($user_id, 'points_repas', true));
    
    if ($free_meals > 0) {
        echo '<p class="free-meals-available">Vous avez ' . $free_meals . ' repas gratuits disponibles cette semaine.</p>';
    }
}

add_action('woocommerce_before_add_to_cart_form', 'display_free_meals_on_product_page');





function update_free_meals_after_order($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $free_meals = intval(get_user_meta($user_id, 'points_repas', true));
    
    foreach ($order->get_items() as $item) {
        $free_meals_used = $item->get_meta('free_meals');
        if ($free_meals_used) {
            $free_meals -= $free_meals_used;
        }
    }
    
    update_user_meta($user_id, 'points_repas', max(0, $free_meals));
}

add_action('woocommerce_order_status_completed', 'update_free_meals_after_order');







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

// Enqueue SweetAlert scripts and styles
function enqueue_sweetalert_scripts() {
    wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array('jquery'), '11.0.0', true);
    wp_enqueue_style('sweetalert-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
    wp_enqueue_script('boutonachat-js', get_stylesheet_directory_uri() . '/js/boutonachat.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_sweetalert_scripts');

// Ajax handler for custom add to cart
add_action('wp_ajax_custom_add_to_cart', 'custom_add_to_cart');
add_action('wp_ajax_nopriv_custom_add_to_cart', 'custom_add_to_cart');

function custom_add_to_cart() {
    error_log('custom_add_to_cart function called');

    $product_id = intval($_POST['product_id']);
    $child_name = sanitize_text_field($_POST['child_name']);

    error_log('Product ID: ' . $product_id);
    error_log('Child Name: ' . $child_name);

    $cart_item_data = array(
        'nom_enfant' => $child_name
    );
    WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
    error_log('Product added to cart for child: ' . $child_name);
    wp_send_json_success();

    wp_die();
}

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




// Ajoutez vos autres fonctions ici...

add_action('wp_ajax_nopriv_orderable_add_to_cart', 'debug_add_to_cart', 1);
add_action('wp_ajax_orderable_add_to_cart', 'debug_add_to_cart', 1);

function debug_add_to_cart() {
    error_log('AJAX add to cart called');
    error_log('POST data: ' . print_r($_POST, true));
}

?>