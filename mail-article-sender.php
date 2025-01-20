<?php
/*
Plugin Name: Email Article Sender
Description: Invia articoli tramite email a liste di utenti.
Version: 1.3
Author: VirtualArs di Vittorio Nicoletti
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Definisci costanti per il plugin
define( 'EAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includi i file necessari
include_once( EAS_PLUGIN_DIR . 'includes/admin-menu.php' );
include_once( EAS_PLUGIN_DIR . 'includes/lists-page.php' );
include_once( EAS_PLUGIN_DIR . 'includes/send-page.php' );
include_once( EAS_PLUGIN_DIR . 'includes/settings-page.php' );
include_once( EAS_PLUGIN_DIR . 'includes/functions.php' );

// Enqueue scripts and styles
function eas_enqueue_admin_assets() {
    wp_enqueue_style( 'eas-admin-style', EAS_PLUGIN_URL . 'assets/css/admin-style.css' );
    wp_enqueue_script( 'eas-admin-script', EAS_PLUGIN_URL . 'assets/js/admin-script.js', array( 'jquery' ), '1.0', true );
    wp_localize_script( 'eas-admin-script', 'eas_ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'security' => wp_create_nonce( 'eas_send_articles_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'eas_enqueue_admin_assets' );

// Attiva il plugin
register_activation_hook( __FILE__, 'eas_activate_plugin' );
function eas_activate_plugin() {
    eas_create_tables();
}

// Deattiva il plugin
register_deactivation_hook( __FILE__, 'eas_deactivate_plugin' );
function eas_deactivate_plugin() {
    // Codice per la deattivazione se necessario
}
