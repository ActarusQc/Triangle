<?php

add_action('admin_menu', 'create_triangle_admin_menu');

function create_triangle_admin_menu() {
    add_menu_page(
        'Triangle Admin', // Titre de la page
        'Triangle Admin', // Texte du menu
        'manage_options', // Capacité requise pour voir ce menu
        'triangle-admin', // Slug du menu
        'triangle_admin_main_page', // Fonction de callback pour la page principale
        'dashicons-admin-generic', // Icône (vous pouvez changer cela)
        5 // Position dans le menu (ajustez selon vos besoins)
    );

    // Ajouter la page de gestion des élèves
    add_submenu_page(
        'triangle-admin', // Slug du menu parent
        'Gestion des élèves', // Titre de la page
        'Gestion des élèves', // Texte du menu
        'manage_options', // Capacité requise
        'gestion_eleves', // Slug du sous-menu
        'gestion_eleves_page' // Fonction de callback
    );
}

function triangle_admin_main_page() {
    echo '<div class="wrap">';
    echo '<h1>Triangle Admin</h1>';
    echo '<p>Bienvenue dans la section d\'administration du Triangle du gourmet.</p>';
    echo '</div>';
}

function get_liste_ecoles() {
    return array(
        'ecole_a' => 'St-Vincent de St-Césaire',
        'ecole_b' => 'St-Michel de Rougemon',
        'ecole_c' => 'Jean-XXIII de Ange-Gardien',
        'ecole_d' => ' Micheline-Brodeur de St-Paul-d\'Abbotsford',
        // Ajoutez d'autres écoles si nécessaire
    );
}

function gestion_eleves_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }

    $ecoles = get_liste_ecoles();
    $ecole_selectionnee = isset($_GET['ecole']) ? sanitize_text_field($_GET['ecole']) : '';

    echo '<div class="wrap">';
    echo '<h1>Gestion des élèves</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="gestion_eleves">';
    echo '<label for="ecole_selectionnee">Sélectionnez une école :</label>';
    echo '<select name="ecole" id="ecole_selectionnee" style="width: 300px;" onchange="this.form.submit()">';
    foreach ($ecoles as $ecole_id => $ecole_nom) {
        echo '<option value="' . esc_attr($ecole_id) . '" ' . selected($ecole_selectionnee, $ecole_id, false) . '>' . esc_html($ecole_nom) . '</option>';
    }
    echo '</select>';
    echo '</form>';

    if ($ecole_selectionnee) {
        if (isset($_POST['save_fondation_points'])) {
            update_fondation_points($_POST);
        }

        $eleves = get_eleves_par_ecole($ecole_selectionnee);

        if (!empty($eleves)) {
            echo '<form method="post">';
            echo '<input type="hidden" name="ecole" value="' . esc_attr($ecole_selectionnee) . '">';

            foreach ($eleves as $eleve) {
                $fondation_checked = get_user_meta($eleve['user_id'], 'fondation_member', true) ? 'checked' : '';
                $points_repas = get_user_meta($eleve['user_id'], 'points_repas', true);
                ?>
                <p>
                    <label>
                        <input type="checkbox" name="fondation[<?php echo esc_attr($eleve['user_id']); ?>]" <?php echo $fondation_checked; ?>>
                        <?php echo esc_html($eleve['nom']); ?>
                    </label>
                    Points repas: <input type="number" name="points_repas[<?php echo esc_attr($eleve['user_id']); ?>]" value="<?php echo esc_attr($points_repas); ?>" min="0">
                </p>
                <?php
            }
            echo '<button type="submit" name="save_fondation_points" class="button button-primary">Enregistrer les modifications</button>';
            echo '</form>';
        } else {
            echo '<p>Aucun élève trouvé pour l\'école sélectionnée.</p>';
        }
    }

    echo '</div>';
}



if (!function_exists('get_foundation_points')) {
    function get_foundation_points($child_name) {
        $user_id = get_current_user_id();
        $enfants = get_user_meta($user_id, 'enfants', true);
        if (is_array($enfants)) {
            foreach ($enfants as $enfant) {
                if ($enfant['nom'] === $child_name) {
                    return intval(get_user_meta($user_id, 'points_repas_' . $child_name, true));
                }
            }
        }
        return 0;
    }
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

function update_fondation_points($post_data) {
    if (isset($post_data['points_repas'])) {
        foreach ($post_data['points_repas'] as $user_id => $points) {
            $user_id = intval($user_id);

            // Gestion du statut de membre de la fondation
            if (isset($post_data['fondation'][$user_id])) {
                update_user_meta($user_id, 'fondation_member', true);
            } else {
                delete_user_meta($user_id, 'fondation_member');
            }

            // Mise à jour des points repas
            $points = intval($points);
            update_user_meta($user_id, 'points_repas', $points);
        }
    }
    echo "<div class='updated'><p>Les modifications ont été enregistrées.</p></div>";
}



// Ajouter la page de rapport au menu d'administration
function ajouter_page_rapport_ecole() {
    add_submenu_page(
        'triangle-admin', // Slug du menu parent
        'Rapport par École', // Titre de la page
        'Rapport École', // Texte du menu
        'manage_options', // Capacité requise
        'rapport-ecole', // Slug du sous-menu
        'afficher_page_rapport_ecole' // Fonction de callback
    );
}
add_action('admin_menu', 'ajouter_page_rapport_ecole');

// Fonction pour afficher le rapport
function afficher_rapport_ecole($date_selectionnee, $ecole_selectionnee = '') {
    wp_enqueue_style('rapport-print-style', get_stylesheet_directory_uri() . '/style.css');
    if (!function_exists('WC') || !WC()) {
        echo "<p>Wooasrce n'est pas initialisé. Impossible de générer le rapport.</p>";
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

    echo "<p>Nombre total de commandes : " . count($commandes) . "</p>";

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
                        $ecole_enfant = isset($enfant['ecole']) ? $enfant['ecole'] : '';
                        $classe_enfant = isset($enfant['classe']) ? $enfant['classe'] : '';
                        if (!empty($ecole_selectionnee) && $ecole_enfant !== $ecole_selectionnee) {
                            continue;
                        }
                        if (!isset($rapport[$ecole_enfant])) {
                            $rapport[$ecole_enfant] = array();
                        }
                        if (!isset($rapport[$ecole_enfant][$enfant['nom']])) {
                            $rapport[$ecole_enfant][$enfant['nom']] = array('classe' => $classe_enfant, 'produits' => array());
                        }
                        $rapport[$ecole_enfant][$enfant['nom']]['produits'][] = $item->get_name();
                    }
                }
            } else {
                $ecole_enfant = '';
                $classe_enfant = '';
                if (is_array($enfants)) {
                    foreach ($enfants as $enfant) {
                        if ($enfant['nom'] === $nom_enfant) {
                            $ecole_enfant = isset($enfant['ecole']) ? $enfant['ecole'] : '';
                            $classe_enfant = isset($enfant['classe']) ? $enfant['classe'] : '';
                            break;
                        }
                    }
                }
                if (!empty($ecole_selectionnee) && $ecole_enfant !== $ecole_selectionnee) {
                    continue;
                }
                if (!isset($rapport[$ecole_enfant])) {
                    $rapport[$ecole_enfant] = array();
                }
                if (!isset($rapport[$ecole_enfant][$nom_enfant])) {
                    $rapport[$ecole_enfant][$nom_enfant] = array('classe' => $classe_enfant, 'produits' => array());
                }
                $rapport[$ecole_enfant][$nom_enfant]['produits'][] = $item->get_name();
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
            echo "<tr style='background-color: #e6e6e6;'><th colspan='3' style='border: 1px solid black; padding: 8px;'>École : " . esc_html($ecoles[$ecole] ?? 'Non spécifiée') . "</th></tr>";
            echo "<tr style='background-color: #f2f2f2;'>";
            echo "<th style='border: 1px solid black; padding: 8px;'>Nom de l'élève</th>";
            echo "<th style='border: 1px solid black; padding: 8px;'>Classe</th>";
            echo "<th style='border: 1px solid black; padding: 8px;'>Produits</th>";
            echo "</tr>";
            foreach ($eleves as $eleve => $info) {
                echo "<tr>";
                echo "<td style='border: 1px solid black; padding: 8px;'>" . esc_html($eleve) . "</td>";
                echo "<td style='border: 1px solid black; padding: 8px;'>" . esc_html($info['classe']) . "</td>";
                echo "<td style='border: 1px solid black; padding: 8px;'>" . esc_html(implode(', ', $info['produits'])) . "</td>";
                echo "</tr>";
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
            printWindow.document.write("<style>@media print { body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid black; padding: 8px; } }</style>");
            printWindow.document.write("</head><body>");
            printWindow.document.write(rapportContent);
            printWindow.document.write("</body></html>");
            printWindow.document.close();
            printWindow.print();
        });
    </script>';
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
