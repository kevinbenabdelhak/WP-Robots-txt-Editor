<?php
/*
Plugin Name: WP Robots txt Editor
Plugin URI: https://kevin-benabdelhak.fr/plugins/WP-Robots-txt-Editor/
Description: WP Robots txt Editor permet de modifier le fichier robots.txt via une interface utilisateur dans l'administration de WordPress.
Version: 1.0
Author: Kevin BENABDELHAK
*/

// Sécurisation: Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}




if ( !class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
}
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$monUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/kevinbenabdelhak/WP-Robots-txt-Editor/', 
    __FILE__,
    'WP-Robots-txt-Editor' 
);

$monUpdateChecker->setBranch('main');











// Définir des constantes pour le chemin du plugin
define('MPRT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPRT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclure les fichiers nécessaires
include_once MPRT_PLUGIN_DIR . 'includes/admin-page.php';

include_once MPRT_PLUGIN_DIR . 'includes/display-columns.php';

// Enregistrer l'admin menu
add_action('admin_menu', 'register_robots_txt_page');





// Enqueue les styles CSS admin
function mprt_enqueue_admin_styles($hook) {
    // Charger uniquement sur la page robots.txt
    if ($hook != 'settings_page_robots_txt_page') {
        return;
    }
    wp_enqueue_style(
        'mprt-admin-style',
        MPRT_PLUGIN_URL . 'assets/css/styles.css',
        [],
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'mprt_enqueue_admin_styles');

?>