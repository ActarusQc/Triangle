<?php

add_action('admin_menu', 'create_triangle_admin_menu');

function create_triangle_admin_menu() {
    add_menu_page(
        'Triangle Admin',
        'Triangle Admin',
        'manage_options',
        'triangle-admin',
        'triangle_admin_main_page',
        'dashicons-admin-generic',
        5
    );

    add_submenu_page(
        'triangle-admin',
        'Repas fondation',
        'Repas fondation',
        'manage_options',
        'gestion_eleves',
        'gestion_eleves_page'
    );

    add_submenu_page(
        'triangle-admin',
        'Paramètres Repas',
        'Paramètres Repas',
        'manage_options',
        'parametres_repas',
        'parametres_repas_page'
    );

    add_submenu_page(
        'triangle-admin',
        'Début de Mois',
        'Début de Mois',
        'manage_options',
        'debut-du-mois',
        'debut_du_mois_page'
    );

    add_submenu_page(
        'triangle-admin',
        'Rapport par école',
        'Rapport école',
        'manage_options',
        'rapport-ecole',
        'afficher_page_rapport_ecole'
    );
}



// Ajouter la sous-page sous "Triangle Admin"
add_action('admin_menu', 'add_conversion_produits_submenu');

function add_conversion_produits_submenu() {
    add_submenu_page(
        'triangle-admin',
        'Conversion Produits',
        'Conversion Produits',
        'manage_options',
        'conversion-produits',
        'conversion_produits_page'
    );
}



// Affichage de la page de conversion
function conversion_produits_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }

    // Traitement du formulaire si soumis
    if (isset($_POST['convertir_produits'])) {
        $categorie_id = intval($_POST['categorie_produits']);
        convertir_produits_simples_en_variables($categorie_id);
    }

    // Affichage du formulaire pour sélectionner la catégorie
    echo '<div class="wrap">';
    echo '<h1>Conversion des Produits Simples en Produits Variables</h1>';
    echo '<form method="post">';
    echo '<p><label for="categorie_produits">Sélectionnez une catégorie :</label>';
    wp_dropdown_categories(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'name' => 'categorie_produits',
        'orderby' => 'name',
        'show_option_none' => __('Sélectionnez une catégorie', 'votre-theme')
    ));
    echo '</p>';
    echo '<p class="submit"><button type="submit" name="convertir_produits" class="button button-primary">Convertir les produits</button></p>';
    echo '</form>';
    echo '</div>';
}






function convertir_produits_simples_en_variables($categorie_id) {
    if (!function_exists('WC')) {
        echo '<div class="error"><p>WooCommerce n\'est pas actif ou n\'est pas correctement initialisé.</p></div>';
        return;
    }

    // Obtenir les produits simples de la catégorie sélectionnée
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $categorie_id,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_product_type',
                'value' => 'simple',
            ),
        ),
    );

    $produits = get_posts($args);

    foreach ($produits as $produit) {
        try {
            // Changer le type de produit en 'variable'
            wp_set_object_terms($produit->ID, 'variable', 'product_type');

            // Mettre à jour les méta-données du produit
            update_post_meta($produit->ID, '_product_attributes', array());
            
            // Supprimer les méta-données spécifiques aux produits simples
            delete_post_meta($produit->ID, '_regular_price');
            delete_post_meta($produit->ID, '_sale_price');
            delete_post_meta($produit->ID, '_price');

            // Message de succès
            echo '<div class="updated"><p>Le produit "' . esc_html($produit->post_title) . '" a été converti en produit variable avec succès.</p></div>';
        } catch (Exception $e) {
            echo '<div class="error"><p>Erreur lors de la conversion du produit "' . esc_html($produit->post_title) . '" : ' . $e->getMessage() . '</p></div>';
        }
    }
}




function triangle_admin_main_page() {
    echo '<div class="wrap">';
    echo '<h1>Triangle Admin</h1>';
    echo '<p>Bienvenue dans la section d\'administration du Triangle du gourmet.</p>';
    echo '</div>';
}


function update_product_variation_prices() {
    $prix_repas_complet = get_option('prix_repas_complet', 7);
    $prix_assiette_seulement =  get_option('prix_assiette_seulement', 5);

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'variable',
            ),
        ),
    );
    $variable_products = new WP_Query($args);

    if ($variable_products->have_posts()) {
        while ($variable_products->have_posts()) {
            $variable_products->the_post();
            $product = wc_get_product(get_the_ID());
            $variations = $product->get_available_variations();

            foreach ($variations as $variation) {
                $variation_obj = wc_get_product($variation['variation_id']);
                $attributes = $variation_obj->get_attributes();

                if (isset($attributes['pa_type-de-repas'])) {
                    if ($attributes['pa_type-de-repas'] === 'repas-complet') {
                        $variation_obj->set_regular_price($prix_repas_complet);
                    } elseif ($attributes['pa_type-de-repas'] === 'assiette-seulement') {
                        $variation_obj->set_regular_price($prix_assiette_seulement);
                    }
                    $variation_obj->save();
                }
            }
        }
        wp_reset_postdata();
    }
}


function parametres_repas_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }

    if (isset($_POST['save_repas_settings'])) {
        update_option('prix_repas_complet', sanitize_text_field($_POST['prix_repas_complet']));
        update_option('prix_assiette_seulement', sanitize_text_field($_POST['prix_assiette_seulement']));
        update_option('points_repas_carte', sanitize_text_field($_POST['points_repas_carte']));
        echo '<div class="updated"><p>Les paramètres ont été mis à jour.</p></div>';
        update_product_variation_prices();
    }

    $prix_repas_complet = get_option('prix_repas_complet', '');
    $prix_assiette_seulement = get_option('prix_assiette_seulement', '');
    $points_repas_carte = get_option('points_repas_carte', '');

    echo '<div class="wrap">';
    echo '<h1>Paramètres Repas</h1>';
    echo '<form method="post">';
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="prix_repas_complet">Prix Repas Complet</label></th>';
    echo '<td><input name="prix_repas_complet" type="text" id="prix_repas_complet" value="' . esc_attr($prix_repas_complet) . '" class="regular-text"></td></tr>';
    echo '<tr><th scope="row"><label for="prix_assiette_seulement">Prix Assiette Seulement</label></th>';
    echo '<td><input name="prix_assiette_seulement" type="text" id="prix_assiette_seulement" value="' . esc_attr($prix_assiette_seulement) . '" class="regular-text"></td></tr>';
    echo '<tr><th scope="row"><label for="points_repas_carte">Points Repas Carte</label></th>';
    echo '<td><input name="points_repas_carte" type="text" id="points_repas_carte" value="' . esc_attr($points_repas_carte) . '" class="regular-text"></td></tr>';
    echo '</table>';
    echo '<p class="submit"><button type="submit" name="save_repas_settings" class="button button-primary">Enregistrer les modifications</button></p>';
    echo '</form></div>';
}

function get_french_month_name($month_number) {
    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $months[$month_number];
}

function debut_du_mois_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }

    if (isset($_POST['save_debut_mois'])) {
        for ($i = 1; $i <= 12; $i++) {
            $month_name = get_french_month_name($i);
            update_option('first_monday_' . strtolower($month_name), sanitize_text_field($_POST['first_monday_' . strtolower($month_name)]));
        }
        echo '<div class="updated"><p>Les dates ont été enregistrées.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Début de Mois - Sélectionnez le premier lundi</h1>';
    echo '<form method="post">';
    echo '<table class="form-table">';

    for ($i = 1; $i <= 12; $i++) {
        $month_name = get_french_month_name($i);
        $stored_date = get_option('first_monday_' . strtolower($month_name), '');
        echo '<tr><th scope="row"><label for="first_monday_' . strtolower($month_name) . '">Premier lundi de ' . $month_name . '</label></th>';
        echo '<td><input name="first_monday_' . strtolower($month_name) . '" type="date" id="first_monday_' . strtolower($month_name) . '" value="' . esc_attr($stored_date) . '" class="regular-text"></td></tr>';
    }

    echo '</table>';
    echo '<p class="submit"><button type="submit" name="save_debut_mois" class="button button-primary">Enregistrer les dates</button></p>';
    echo '</form></div>';
}

function get_liste_ecoles() {
    return array(
        'ecole_a' => 'St-Vincent de St-Césaire',
        'ecole_b' => 'St-Michel de Rougemont',
        'ecole_c' => 'Jean-XXIII de Ange-Gardien',
        'ecole_d' => 'Micheline-Brodeur de St-Paul-d\'Abbotsford',
    );
}

function gestion_eleves_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }

    $ecoles = get_liste_ecoles();
    $ecole_selectionnee = isset($_GET['ecole']) ? sanitize_text_field($_GET['ecole']) : '';

    echo '<div class="wrap">';
    echo '<h1>Repas fondation</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="gestion_eleves">';
    echo '<label for="ecole_selectionnee">Sélectionnez une école :</label>';
    echo '<select name="ecole" id="ecole_selectionnee" style="width: 300px;" onchange="this.form.submit()">';
    echo '<option value="">' . __('Choisissez une école', 'votre-theme') . '</option>';

    foreach ($ecoles as $ecole_id => $ecole_nom) {
        echo '<option value="' . esc_attr($ecole_id) . '" ' . selected($ecole_selectionnee, $ecole_id, false) . '>' . esc_html($ecole_nom) . '</option>';
    }

    echo '</select></form>';

    if ($ecole_selectionnee) {
        $eleves = get_eleves_par_ecole($ecole_selectionnee);

        if (!empty($eleves)) {
            echo '<form method="post">';
            wp_nonce_field('save_fondation_points_action', 'fondation_points_nonce');
            echo '<input type="hidden" name="ecole" value="' . esc_attr($ecole_selectionnee) . '">';

            echo '<table style="width: 50%; border-collapse: collapse; margin-top: 20px;">';
            echo '<thead><tr style="background-color: #f2f2f2;">';
            echo '<th style="width: 5%; text-align: center; border: 1px solid #ccc; padding: 8px;">✓</th>';
            echo '<th style="width: 70%; text-align: left; border: 1px solid #ccc; padding: 8px;">Nom de l\'élève</th>';
            echo '<th style="width: 25%; text-align: center; border: 1px solid #ccc; padding: 8px;">Repas fondation</th>';
            echo '</tr></thead><tbody>';

            foreach ($eleves as $eleve) {
                $fondation_member = get_user_meta($eleve['user_id'], 'fondation_member_' . sanitize_key($eleve['nom']), true);
                $fondation_checked = $fondation_member ? 'checked' : '';
                $points_repas = get_user_meta($eleve['user_id'], 'points_repas_' . sanitize_key($eleve['nom']), true);
                
                echo '<tr>';
                echo '<td style="text-align: center; border: 1px solid #ccc; padding: 8px;">';
                echo '<input type="checkbox" name="fondation[' . esc_attr($eleve['user_id']) . '][' . esc_attr(sanitize_key($eleve['nom'])) . ']" ' . $fondation_checked . '>';
                echo '</td>';
                echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($eleve['nom']) . '</td>';
                echo '<td style="text-align: center; border: 1px solid #ccc; padding: 8px;">';
                echo '<input type="number" name="points_repas[' . esc_attr($eleve['user_id']) . '][' . esc_attr(sanitize_key($eleve['nom'])) . ']" value="' . esc_attr($points_repas) . '" min="0" max="99" style="width: 50px;">';
                echo '</td></tr>';
            }

            echo '</tbody></table>';
            echo '<button type="submit" name="save_fondation_points" class="button button-primary" style="margin-top: 20px;">Enregistrer les modifications</button>';
            echo '</form>';
        } else {
            echo '<p>Aucun élève trouvé pour l\'école sélectionnée.</p>';
        }
    }

    echo '</div>';
}

function update_fondation_points_simple($post_data) {
    if (!isset($post_data['points_repas'])) {
        error_log("Données POST manquantes pour les points de repas");
        return;
    }

    foreach ($post_data['points_repas'] as $user_id => $enfants) {
        $user_id = intval($user_id);
        foreach ($enfants as $enfant_nom => $points) {
            $enfant_nom = sanitize_key($enfant_nom);
            
            // Mise à jour du statut de membre de la fondation
            if (isset($post_data['fondation'][$user_id][$enfant_nom])) {
                update_user_meta($user_id, 'fondation_member_' . $enfant_nom, '1');
            } else {
                delete_user_meta($user_id, 'fondation_member_' . $enfant_nom);
            }
            
            // Mise à jour des points de repas
            $points = intval($points);
            update_user_meta($user_id, 'points_repas_' . $enfant_nom, $points);
        }
    }

    echo "<div class='updated'><p>Les modifications ont été enregistrées.</p></div>";
}

function get_eleves_par_ecole($ecole_selectionnee) {
    $users = get_users();
    $eleves = array();

    foreach ($users as $user) {
        $enfants = get_user_meta($user->ID, 'enfants', true);
        if (is_array($enfants)) {
            foreach ($enfants as $enfant) {
                if (isset($enfant['ecole']) && $enfant['ecole'] === $ecole_selectionnee) {
                    $eleves[] = array(
                        'nom' => $enfant['nom'],
                        'classe' => isset($enfant['classe']) ? $enfant['classe'] : 'Non spécifiée',
                        'user_id' => $user->ID
                    );
                }
            }
        }
    }

    return $eleves;
}

// Gestion de la sauvegarde des points fondation
add_action('admin_init', 'handle_fondation_points_save');

function handle_fondation_points_save() {
    if (isset($_POST['save_fondation_points']) && isset($_POST['fondation_points_nonce']) && wp_verify_nonce($_POST['fondation_points_nonce'], 'save_fondation_points_action')) {
        update_fondation_points_simple($_POST);
    }
}

// Assurez-vous que la fonction afficher_rapport_ecole n'est pas redéfinie
if (!function_exists('afficher_rapport_ecole')) {
    function afficher_rapport_ecole($date_selectionnee, $ecole_selectionnee = '') {
        wp_enqueue_style('rapport-print-style', get_stylesheet_directory_uri() . '/style.css');
        if (!function_exists('WC') || !WC()) {
            echo "<p>WooCommerce n'est pas initialisé. Impossible de générer le rapport.</p>";
            return;
        }

        echo '<button id="print-report-button" class="button button-primary" style="margin-top: 10px;">Imprimer le rapport</button>';

        echo '<div id="rapport-ecole" class="rapport-ecole-print" style="width: 100%; max-width: 100%; margin: 0; padding: 0;">';
        echo "<p>Date sélectionnée : " . esc_html($date_selectionnee) . "</p>";
        
        $commandes = wc_get_orders(array(
            'limit' => -1,
            'status' => array('wc-completed', 'wc-processing'),
            'date_created' => '>' . strtotime('-1 year')
        ));

        if (is_wp_error($commandes)) {
            echo "<p>Erreur lors de la récupération des commandes : " . $commandes->get_error_message() . "</p>";
            echo '</div>';
            return;
        }

        if (empty($commandes)) {
            echo "<p>Aucune commande trouvée pour la période sélectionnée.</p>";
            echo '</div>';
            return;
        }

        $rapport = array();
        $ecoles = get_liste_ecoles();

        foreach ($commandes as $commande) {
            $user_id = $commande->get_user_id();
            $enfants = get_user_meta($user_id, 'enfants', true);
            
            foreach ($commande->get_items() as $item) {
                $product_id = $item->get_product_id();
                $end_date = get_post_meta($product_id, 'was_scheduler_end_date', true);
                $end_time = get_post_meta($product_id, 'was_scheduler_end_time', true);
                
                if (empty($end_date)) {
                    continue;
                }

                $end_datetime = $end_date . ' ' . $end_time;
                
                $date_selectionnee_obj = DateTime::createFromFormat('Y-m-d', $date_selectionnee);
                $end_date_obj = DateTime::createFromFormat('Y-m-d H:i', $end_datetime);
                
                if (!$date_selectionnee_obj || !$end_date_obj) {
                    continue;
                }

                if ($date_selectionnee_obj->format('Y-m-d') !== $end_date_obj->format('Y-m-d')) {
                    continue;
                }

                $nom_enfant = $item->get_meta('nom_enfant');
                
                if (empty($nom_enfant) || $nom_enfant === 'tous') {
                    if (is_array($enfants)) {
                        foreach ($enfants as $enfant) {
                            ajouter_enfant_au_rapport($rapport, $enfant, $ecole_selectionnee, $item, $commande);
                        }
                    }
                } else {
                    if (is_array($enfants)) {
                        foreach ($enfants as $enfant) {
                            if ($enfant['nom'] === $nom_enfant) {
                                ajouter_enfant_au_rapport($rapport, $enfant, $ecole_selectionnee, $item, $commande);
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Afficher le rapport
        echo "<h2>Rapport pour la date : " . esc_html($date_selectionnee) . "</h2>";
        if (empty($rapport)) {
            echo "<p>Aucune donnée trouvée pour cette date.</p>";
        } else {
            foreach ($rapport as $ecole => $eleves) {
                echo "<table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
                echo "<tr style='background-color: #e6e6e6;'><th colspan='8' style='border: 1px solid black; padding: 8px;'>École : " . esc_html($ecoles[$ecole] ?? 'Non spécifiée') . "</th></tr>";
                echo "<tr style='background-color: #d9d9d9;'>"; // En-tête en gris
                echo "<th style='border: 1px solid black; padding: 8px;'>Nom de l'élève</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>Fondation</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>Classe</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>Carte repas</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>Payé</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>À payer</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>Produits</th>";
                echo "<th style='border: 1px solid black; padding: 8px;'>Livré</th>";
                echo "</tr>";
                
                $row_color = false;

                foreach ($eleves as $eleve => $info) {
                    $row_style = $row_color ? "background-color: #f2f2f2;" : "background-color: #ffffff;";
                    echo "<tr style='{$row_style}'>";
                    echo "<td style='border: 1px solid black; padding: 8px;'>" . esc_html($eleve) . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px; text-align: center;'>" . ($info['fondation'] ? '✓' : '') . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px;'>" . esc_html($info['classe']) . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px; text-align: center;'>" . ($info['carte_repas'] ? '✓' : '') . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px; text-align: center;'>" . ($info['paye'] ? '✓' : '') . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px; text-align: center;'>" . ($info['a_payer'] ? '✓' : '') . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px;'>" . esc_html(implode(', ', $info['produits'])) . "</td>";
                    echo "<td style='border: 1px solid black; padding: 8px; text-align: center;'><input type='checkbox'></td>";
                    echo "</tr>";

                    $row_color = !$row_color;
                }
                echo "</table>";
            }
        }
        echo '</div>';

        echo '<script>
    document.getElementById("print-report-button").addEventListener("click", function() {
        var rapportContent = document.getElementById("rapport-ecole").innerHTML;
        var printWindow = window.open("", "_blank", "width=800,height=600");
        printWindow.document.write("<html><head><title>Imprimer le rapport</title>");
        printWindow.document.write("<style>@media print { body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid black; padding: 8px; } @page { size: landscape; } }</style>");
        printWindow.document.write("</head><body>");
        printWindow.document.write(rapportContent);
        printWindow.document.write("</body></html>");
        printWindow.document.close();
        printWindow.print();
    });
</script>';

    }
}

function ajouter_enfant_au_rapport(&$rapport, $enfant, $ecole_selectionnee, $item, $commande) {
    $ecole_enfant = isset($enfant['ecole']) ? $enfant['ecole'] : '';
    $classe_enfant = isset($enfant['classe']) ? $enfant['classe'] : '';
    
    if (!empty($ecole_selectionnee) && $ecole_enfant !== $ecole_selectionnee) {
        return;
    }
    
    if (!isset($rapport[$ecole_enfant])) {
        $rapport[$ecole_enfant] = array();
    }
    
    if (!isset($rapport[$ecole_enfant][$enfant['nom']])) {
        $rapport[$ecole_enfant][$enfant['nom']] = array(
            'classe' => $classe_enfant,
            'produits' => array(),
            'fondation' => get_user_meta($commande->get_user_id(), 'fondation_member_' . sanitize_key($enfant['nom']), true),
            'carte_repas' => false,
            'paye' => false,
            'a_payer' => false
        );
    }
    
    $rapport[$ecole_enfant][$enfant['nom']]['produits'][] = $item->get_name();
    
    // Déterminer le mode de paiement à partir de la commande WooCommerce
    $payment_method = $commande->get_payment_method_title(); // Utiliser get_payment_method_title() pour obtenir le titre du mode de paiement
    
    // Selon le mode de paiement, cochez les champs correspondants
    if ($payment_method === 'Repas') { // Exemple pour SUMO Rewards
        $rapport[$ecole_enfant][$enfant['nom']]['carte_repas'] = true;
    } elseif ($payment_method === 'Transfert bancaire' || $payment_method === 'Carte de crédit') { // Exemple pour BACS et Stripe
        $rapport[$ecole_enfant][$enfant['nom']]['paye'] = true;
    } elseif ($payment_method === 'Paiement à la livraison') { // Exemple pour COD
        $rapport[$ecole_enfant][$enfant['nom']]['a_payer'] = true;
    }
}

function afficher_page_rapport_ecole() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }
    ?>
    <div class="wrap">
        <h1>Rapport par Date et École</h1>
        <form method="post">
            <p>
                <label for="date_rapport">Date du rapport :</label>
                <input type="date" id="date_rapport" name="date_rapport" required>
            </p>
            <?php
            $ecoles = get_liste_ecoles();
            if (!empty($ecoles)) {
                ?>
                <p>
                    <label for="ecole">École :</label>
                    <select name="ecole" id="ecole">
                        <option value="">Toutes les écoles</option>
                        <?php
                        foreach ($ecoles as $value => $label) {
                            echo "<option value='" . esc_attr($value) . "'>" . esc_html($label) . "</option>";
                        }
                        ?>
                    </select>
                </p>
                <input type="submit" name="generer_rapport" class="button button-primary" value="Générer le rapport">
                <?php
            } else {
                echo "<p>Aucune école trouvée. Veuillez vérifier la fonction get_liste_ecoles().</p>";
            }
            ?>
        </form>
        <?php
        if (isset($_POST['generer_rapport'])) {
            $date_selectionnee = sanitize_text_field($_POST['date_rapport']);
            $ecole_selectionnee = isset($_POST['ecole']) ? sanitize_text_field($_POST['ecole']) : '';
            afficher_rapport_ecole($date_selectionnee, $ecole_selectionnee);
        }
        ?>
    </div>
    <?php
}

?>
