<?php
function eas_create_tables() {
    global $wpdb;

    // Tabella per le liste
    $table_lists = $wpdb->prefix . 'eas_lists';
    $charset_collate = $wpdb->get_charset_collate();

    $sql1 = "CREATE TABLE $table_lists (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        list_name varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabella per le email
    $table_emails = $wpdb->prefix . 'eas_emails';

    $sql2 = "CREATE TABLE $table_emails (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        list_id mediumint(9) NOT NULL,
        email varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY  (list_id) REFERENCES $table_lists(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql1 );
    dbDelta( $sql2 );
}

// Funzioni utili per il plugin
function eas_get_lists() {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'eas_lists';
    return $wpdb->get_results( "SELECT * FROM $table_lists" );
}

function eas_get_emails_by_lists( $list_ids ) {
    global $wpdb;
    $table_emails = $wpdb->prefix . 'eas_emails';
    $placeholders = implode( ',', array_fill( 0, count( $list_ids ), '%d' ) );
    $query = $wpdb->prepare( "SELECT DISTINCT email FROM $table_emails WHERE list_id IN ($placeholders)", $list_ids );
    return $wpdb->get_col( $query );
}
