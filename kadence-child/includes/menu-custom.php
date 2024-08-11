<?php
if (!defined('ABSPATH')) exit; // Sécurité


add_action('woocommerce_settings_save_custom', 'update_menu_products_price');





// Ajoutez cette fonction pour filtrer le prix affiché
function custom_woocommerce_get_price_html($price, $product) {
    // Supprimer le texte "De: 0.00$ ou 1 carte"
    return '<span class="price">' . wc_price($product->get_price()) . '</span>';
}
add_filter('woocommerce_get_price_html', 'custom_woocommerce_get_price_html', 10, 2);


// Ajoutez cette fonction pour enlever le texte "0 ou 1 carte repas"
function custom_woocommerce_product_add_to_cart_text($text, $product) {
    return 'Sélectionner';
}
add_filter('woocommerce_product_add_to_cart_text', 'custom_woocommerce_product_add_to_cart_text', 10, 2);



function update_menu_products_price() {
    error_log('Fonction update_menu_products_price() appelée');

    // Récupérer le nouveau prix des repas
    $new_price = get_option('wc_settings_tab_meal_price');
    error_log('Nouveau prix des repas : ' . $new_price);

    // Vérifier si le prix a été modifié
    if ($new_price !== false) {
        // Récupérer tous les produits de la catégorie "Menu"
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => 'menu', // Assurez-vous que c'est le bon slug de votre catégorie
                ),
            ),
        );
        $menu_products = new WP_Query($args);
        error_log('Nombre de produits trouvés : ' . $menu_products->post_count);

        // Mettre à jour le prix de chaque produit
        if ($menu_products->have_posts()) {
            while ($menu_products->have_posts()) {
                $menu_products->the_post();
                $product_id = get_the_ID();
                update_post_meta($product_id, '_regular_price', $new_price);
                update_post_meta($product_id, '_price', $new_price);
                wc_delete_product_transients($product_id);
                error_log('Prix mis à jour pour le produit ID : ' . $product_id);
            }
        }

        wp_reset_postdata();

        // Optionnel : Ajouter un message de succès
        add_action('admin_notices', 'meal_price_update_notice');
    }
}


add_filter( 'woocommerce_account_menu_items', 'remove_account_menu_items', 999 );

function remove_account_menu_items( $menu_items ) {
    unset( $menu_items['downloads'] );
    unset( $menu_items['dashboard'] );
    return $menu_items;
}



add_filter('woocommerce_account_menu_items', 'add_menu_mensuel_tab', 5);
function add_menu_mensuel_tab($menu_items) {
    $new_menu_items = array('menu-mensuel' => 'Menu Mensuel');
    return array_merge($new_menu_items, $menu_items);
}


add_action('init', 'add_menu_mensuel_endpoint');
function add_menu_mensuel_endpoint() {
    add_rewrite_endpoint('menu-mensuel', EP_ROOT | EP_PAGES);
}

add_action('woocommerce_account_menu-mensuel_endpoint', 'menu_mensuel_content');
function menu_mensuel_content() {
    echo '<h2>Menu Mensuel</h2>';
    // Ajoutez ici le contenu de votre page Menu Mensuel
    afficher_menu_mensuel();
}







// Mettre à jour les prix des produits de la catégorie "menu"
if (!function_exists('mettre_a_jour_prix_produits_menu')) {
    function mettre_a_jour_prix_produits_menu($old_value, $value, $option) {
        if ($option === 'prix_repas_uniquement') {
            $prix = get_option('prix_repas_uniquement', 0);
            error_log('Mise à jour des prix des produits de la catégorie "menu" avec le prix : ' . $prix);

            if ($prix > 0) {
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'slug',
                            'terms'    => 'menu',
                        ),
                    ),
                );

                $products = get_posts($args);
                error_log('Nombre de produits trouvés dans la catégorie "menu" : ' . count($products));

                foreach ($products as $product) {
                    $product_id = $product->ID;
                    update_post_meta($product_id, '_regular_price', $prix);
                    update_post_meta($product_id, '_price', $prix);
                    error_log('Produit ID ' . $product_id . ' mis à jour avec le prix : ' . $prix);
                }
            } else {
                error_log('Le prix du repas est de 0 ou inférieur. Aucune mise à jour effectuée.');
            }
        }
    }
    add_action('update_option_prix_repas_uniquement', 'mettre_a_jour_prix_produits_menu', 10, 3);
}

// Mettre à jour les prix des produits de la catégorie "menu"
function mettre_a_jour_prix_produits_menu($old_value, $value, $option) {
    if ($option === 'prix_repas_uniquement') {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => 'menu',
                ),
            ),
        );

        $products = get_posts($args);

        foreach ($products as $product) {
            $product_id = $product->ID;
            update_post_meta($product_id, '_regular_price', $value);
            update_post_meta($product_id, '_price', $value);
        }
    }
}
add_action('update_option_prix_repas_uniquement', 'mettre_a_jour_prix_produits_menu', 10, 3);


// Afficher le menu mensuel
function afficher_menu_mensuel($mois = null, $annee = null) {
    if (!$mois) $mois = date('n');
    if (!$annee) $annee = date('Y');

    $shortcodes = [
        '1' => 1304, // Janvier
        '2' => 1305, // Février
        '3' => 1306, // Mars
        '4' => 1307, // Avril
        '5' => 1308, // Mai
        '6' => 1309, // Juin 
        '7' => 1131, // Juillet
        '8' => 1131, // Aout
        '9' => 1300, // Septembre
        '10' => 1301, // Octobre
        '11' => 1302, // Novembre
        '12' => 1303, // Décembre
    ];

    $id_shortcode = $shortcodes[$mois] ?? null;
    
    
    // Afficher les catégories de semaines
function afficher_categories_semaines($mois, $annee) {
    $categories = array('Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4');
    
    echo '<div class="semaines-container">';
    echo '<div id="semaine-courante"></div>';
    
    foreach ($categories as $index => $categorie) {
        $week_number = $index + 1;
        $dates = get_week_dates($week_number, $mois, $annee);
        $date_range = "{$dates['start']} au {$dates['end']} " . date_i18n('F', mktime(0, 0, 0, $mois, 1, $annee));
        
        echo '<button class="semaine-btn" data-target="categorie-semaine-' . $week_number . '" data-date="' . esc_attr($date_range) . '">';
        echo esc_html($categorie);
        echo '</button>';
    }
    
    echo '</div>';

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Afficher la première semaine par défaut
        var defaultDate = $('.semaine-btn:first').data('date');
        $('#semaine-courante').text('Semaine du ' + defaultDate);

        $('.semaine-btn').on('click', function() {
            var dateRange = $(this).data('date');
            $('#semaine-courante').text('Semaine du ' + dateRange);
        });
    });
    </script>
    <?php
}




    // Afficher le div pour la semaine courante avec un style amélioré
    echo '<div id="semaine-courante" style="text-align: center; margin: 20px auto; padding: 10px; font-size: 18px; font-weight: bold; background-color: #f0f0f0; border: 2px solid #ddd; border-radius: 5px; max-width: 80%;">La semaine courante apparaîtra ici.</div>';

    if ($id_shortcode) {
        echo do_shortcode("[orderable id=\"$id_shortcode\"]");
    } else {
        echo "Pas de menu disponible pour ce mois.";
    }

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var currentMonth = <?php echo $mois; ?>;
        var currentYear = <?php echo $annee; ?>;

       function getWeekDates(month, year) {
    var dates = [];
    var firstDay = new Date(year, month - 1, 1);
    var firstMonday = new Date(firstDay);
    firstMonday.setDate(firstDay.getDate() - firstDay.getDay() + 1);
    if (firstMonday > firstDay) {
        firstMonday.setDate(firstMonday.getDate() - 7);
    }

    for (var i = 0; i < 4; i++) {
        var startDate = new Date(firstMonday);
        startDate.setDate(startDate.getDate() + i * 7);
        var endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 4);

        var startFormatted = startDate.getDate() + ' ' + startDate.toLocaleString('fr-FR', { month: 'long' });
        var endFormatted = endDate.getDate() + ' ' + endDate.toLocaleString('fr-FR', { month: 'long' });
        dates.push(startFormatted + ' au ' + endFormatted);
    }
    return dates;
}

        var weekDates = getWeekDates(currentMonth, currentYear);

        var lastActiveTab = '';

        function updateCurrentWeek() {
            var $activeTab = $('[class*="orderable"][class*="tab"][class*="active"]');
            
            if ($activeTab.length) {
                var tabText = $activeTab.text().trim();
                
                if (tabText !== lastActiveTab) {
                    lastActiveTab = tabText;
                    var weekNumber = tabText.match(/\d+/);
                    if (weekNumber) {
                        weekNumber = parseInt(weekNumber[0]);
                        var text = 'Semaine ' + weekNumber + ' - ' + weekDates[weekNumber - 1];
                        $('#semaine-courante').text(text);
                    }
                }
            }
        }

        // Vérifier toutes les 500ms
        setInterval(updateCurrentWeek, 500);

        // Déclencher une mise à jour initiale
        updateCurrentWeek();

        // Observer les changements de mois
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-month') {
                    var newMonth = parseInt($('.orderable-main').attr('data-month'));
                    if (newMonth !== currentMonth) {
                        currentMonth = newMonth;
                        weekDates = getWeekDates(currentMonth, currentYear);
                        updateCurrentWeek();
                    }
                }
            });
        });

        observer.observe(document.querySelector('.orderable-main'), { attributes: true });
    });
    </script>
    <?php
}

function charger_contenu_semaine() {
    $semaine = isset($_POST['semaine']) ? intval($_POST['semaine']) : 1;
    $mois = isset($_POST['mois']) ? intval($_POST['mois']) : date('n');
    $annee = isset($_POST['annee']) ? intval($_POST['annee']) : date('Y');
    $shortcode_id = isset($_POST['shortcode_id']) ? intval($_POST['shortcode_id']) : 0;

    // Ici, vous devez implémenter la logique pour obtenir le contenu de la semaine spécifique
    // Cela dépendra de la façon dont Orderable structure ses données

    $contenu = do_shortcode("[orderable id=\"$shortcode_id\" semaine=\"$semaine\"]");
    
    echo $contenu;
    wp_die(); // Nécessaire pour terminer proprement une requête AJAX
}
add_action('wp_ajax_charger_contenu_semaine', 'charger_contenu_semaine');
add_action('wp_ajax_nopriv_charger_contenu_semaine', 'charger_contenu_semaine');

function boutons_navigation_mois() {
    $mois_actuel = isset($_GET['mois']) ? intval($_GET['mois']) : date('n');
    $annee_actuelle = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');

    // Gestion du mois précédent
    if ($mois_actuel == 1) {
        $mois_precedent = 12;
        $annee_precedente = $annee_actuelle - 1;
    } else {
        $mois_precedent = $mois_actuel - 1;
        $annee_precedente = $annee_actuelle;
    }

    // Gestion du mois suivant
    if ($mois_actuel == 12) {
        $mois_suivant = 1;
        $annee_suivante = $annee_actuelle + 1;
    } elseif ($mois_actuel == 7) {
        $mois_suivant = 8;
        $annee_suivante = $annee_actuelle;
    } else {
        $mois_suivant = $mois_actuel + 1;
        $annee_suivante = $annee_actuelle;
    }

    $url_precedent = add_query_arg(['mois' => $mois_precedent, 'annee' => $annee_precedente]);
    $url_suivant = add_query_arg(['mois' => $mois_suivant, 'annee' => $annee_suivante]);

    echo '<div class="navigation-mois">';
    echo "<a href='$url_precedent' class='bouton-mois bouton-precedent'>&#8592; Mois précédent</a>";
    echo "<a href='$url_suivant' class='bouton-mois bouton-suivant'>Mois suivant &#8594;</a>";
    echo '</div>';
}

function enqueue_print_adjustments_script() {
    // Vérifiez si c'est la page où votre rapport est affiché
    if (is_page('rapport-ecole') || is_singular('votre-type-de-post')) {
        wp_enqueue_script('print-adjustments', get_stylesheet_directory_uri() . '/print-adjustments.js', array(), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_print_adjustments_script');

function debug_product_metadata($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) {
        return "Produit non trouvé";
    }
    $metadata = get_post_meta($product_id);
    $debug_info = "Métadonnées pour le produit " . $product->get_name() . " (ID: $product_id):\n";
    foreach ($metadata as $key => $value) {
        $debug_info .= "$key: " . print_r($value[0], true) . "\n";
    }
    return $debug_info;
}



// Fonction pour vérifier les dates des produits
function verifier_dates_produits() {
    if (!function_exists('WC') || !WC()) {
        echo "<p>WooCommerce n'est pas initialisé. Impossible de vérifier les dates des produits.</p>";
        return;
    }
    $products = wc_get_products(array('limit' => -1));
    echo "<h3>Vérification des dates de fin des produits</h3>";
    foreach ($products as $product) {
        $end_date = '';
        $possible_keys = array(
            '_availability_end_date',
            '_end_date',
            'end_date',
            '_availability_to',
            '_sale_price_dates_to',
            '_schedule_end'
        );
        foreach ($possible_keys as $key) {
            $end_date = get_post_meta($product->get_id(), $key, true);
            if (!empty($end_date)) {
                break;
            }
        }
        if (empty($end_date)) {
            $end_date = $product->get_meta('_availability_end_date');
        }
        echo "<p>Produit : " . $product->get_name() . ", Date de fin : " . ($end_date ? $end_date : 'Non définie') . "</p>";
    }
}

// Fonction pour vérifier les commandes
function verifier_commandes() {
    if (!function_exists('WC') || !WC()) {
        echo "<p>WooCommerce n'est pas initialisé. Impossible de vérifier les commandes.</p>";
        return;
    }
    $commandes = wc_get_orders(array(
        'limit' => -1,
        'status' => array('wc-completed', 'wc-processing')
    ));
    echo "<h3>Vérification des commandes</h3>";
    if (empty($commandes)) {
        echo "<p>Aucune commande trouvée.</p>";
        return;
    }
    foreach ($commandes as $commande) {
        echo "<p>Commande #" . $commande->get_id() . "</p>";
        foreach ($commande->get_items() as $item) {
            $product_id = $item->get_product_id();
            $end_date = get_post_meta($product_id, '_availability_end_date', true);
            $nom_enfant = $item->get_meta('nom_enfant');
            echo "<p>Produit : " . $item->get_name() . ", Date de fin : " . $end_date . ", Nom de l'enfant : " . $nom_enfant . "</p>";
        }
    }
}


add_filter('woocommerce_return_to_shop_redirect', 'custom_empty_cart_redirect');