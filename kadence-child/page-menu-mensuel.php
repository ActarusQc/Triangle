<?php
/*
Template Name: Menu Mensuel
*/

get_header();
?>

<div id="primary" class="content-area">
    <div class="content-container site-container">
        <main id="main" class="site-main" role="main">
            <div class="content-wrap">
                <?php echo do_shortcode('[user_points]'); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry-content">
                        <?php
                        $mois = isset($_GET['mois']) ? intval($_GET['mois']) : date('n');
                        $annee = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');

                        echo "<h1>Menu mensuel - " . date_i18n('F Y', mktime(0, 0, 0, $mois, 1, $annee)) . "</h1>";

                        boutons_navigation_mois();
                        afficher_menu_mensuel($mois, $annee);
                        ?>
                    </div>
                </article>
            </div>
        </main>
    </div>
</div>

<?php
get_footer();
?>