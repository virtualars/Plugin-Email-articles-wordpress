<?php
function eas_admin_menu() {
    add_menu_page(
        'Email Article Sender',
        'Email Sender',
        'manage_options',
        'eas-main-menu',
        'eas_send_page',
        'dashicons-email-alt',
        6
    );

    add_submenu_page(
        'eas-main-menu',
        'Invia Articoli',
        'Invia Articoli',
        'manage_options',
        'eas-main-menu',
        'eas_send_page'
    );

    add_submenu_page(
        'eas-main-menu',
        'Gestisci Liste',
        'Gestisci Liste',
        'manage_options',
        'eas-lists',
        'eas_lists_page'
    );

    add_submenu_page(
        'eas-main-menu',
        'Impostazioni',
        'Impostazioni',
        'manage_options',
        'eas-settings',
        'eas_settings_page'
    );
}
add_action( 'admin_menu', 'eas_admin_menu' );
