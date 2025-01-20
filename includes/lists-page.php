<?php
// Evita l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Funzione principale per gestire le liste e le email.
 */
function eas_lists_page() {
    global $wpdb;
    $table_lists = $wpdb->prefix . 'eas_lists';
    $table_emails = $wpdb->prefix . 'eas_emails';

    // Gestione delle azioni prima di qualsiasi output
    if ( isset( $_POST['action'] ) && $_POST['action'] == 'add_list' ) {
        // Verifica il nonce
        if ( ! isset( $_POST['eas_add_list_nonce'] ) || ! wp_verify_nonce( $_POST['eas_add_list_nonce'], 'eas_add_list' ) ) {
            add_action( 'admin_notices', 'eas_error_notice_invalid_nonce' );
        } else {
            $list_name = sanitize_text_field( $_POST['list_name'] );
            if ( ! empty( $list_name ) ) {
                $wpdb->insert( $table_lists, array( 'list_name' => $list_name ) );
                add_action( 'admin_notices', 'eas_success_notice_add_list' );
            } else {
                add_action( 'admin_notices', 'eas_error_notice_empty_list_name' );
            }
        }
    }

    if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete_list' ) {
        // Verifica il nonce
        if ( ! isset( $_GET['eas_delete_list_nonce'] ) || ! wp_verify_nonce( $_GET['eas_delete_list_nonce'], 'eas_delete_list' ) ) {
            add_action( 'admin_notices', 'eas_error_notice_invalid_nonce' );
        } else {
            $list_id = intval( $_GET['list_id'] );
            if ( $list_id > 0 ) {
                $wpdb->delete( $table_lists, array( 'id' => $list_id ) );
                $wpdb->delete( $table_emails, array( 'list_id' => $list_id ) );
                add_action( 'admin_notices', 'eas_success_notice_delete_list' );
            } else {
                add_action( 'admin_notices', 'eas_error_notice_invalid_list' );
            }
        }
    }

    if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete_email' ) {
        // Verifica il nonce
        if ( ! isset( $_GET['eas_delete_email_nonce'] ) || ! wp_verify_nonce( $_GET['eas_delete_email_nonce'], 'eas_delete_email' ) ) {
            add_action( 'admin_notices', 'eas_error_notice_invalid_nonce' );
        } else {
            $email_id = intval( $_GET['email_id'] );
            $list_id  = intval( $_GET['list_id'] );

            if ( $email_id > 0 && $list_id > 0 ) {
                $wpdb->delete( $table_emails, array( 'id' => $email_id ) );
                add_action( 'admin_notices', 'eas_success_notice_delete_email' );
            } else {
                add_action( 'admin_notices', 'eas_error_notice_invalid_email' );
            }
        }
    }

    // Visualizzazione delle liste
    $lists = $wpdb->get_results( "SELECT * FROM $table_lists" );

    ?>
    <div class="wrap">
        <h1>Gestisci Liste</h1>

        <!-- Admin Notices -->
        <?php
        do_action( 'admin_notices' );
        ?>

        <h2>Aggiungi Nuova Lista</h2>
        <form method="post">
            <?php wp_nonce_field( 'eas_add_list', 'eas_add_list_nonce' ); ?>
            <input type="hidden" name="action" value="add_list">
            <table class="form-table">
                <tr>
                    <th><label for="list_name">Nome Lista</label></th>
                    <td><input type="text" name="list_name" id="list_name" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button( 'Aggiungi Lista' ); ?>
        </form>

        <h2>Liste Esistenti</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Lista</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $lists ) ) : ?>
                    <?php foreach ( $lists as $list ) : ?>
                        <tr>
                            <td><?php echo esc_html( $list->id ); ?></td>
                            <td><?php echo esc_html( $list->list_name ); ?></td>
                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=eas-lists&action=manage_emails&list_id=' . esc_attr( $list->id ) ); ?>">Gestisci Email</a> |
                                <a href="<?php echo admin_url( 'admin.php?page=eas-lists&action=delete_list&list_id=' . esc_attr( $list->id ) . '&eas_delete_list_nonce=' . wp_create_nonce( 'eas_delete_list' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Sei sicuro di voler eliminare questa lista?', 'email-article-sender' ); ?>');">Elimina</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3"><?php esc_html_e( 'Nessuna lista trovata.', 'email-article-sender' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php

    // Gestione delle email nella lista
    if ( isset( $_GET['action'] ) && $_GET['action'] == 'manage_emails' ) {
        $list_id = intval( $_GET['list_id'] );
        if ( $list_id > 0 ) {
            eas_manage_emails_page( $list_id );
        } else {
            add_action( 'admin_notices', 'eas_error_notice_invalid_list' );
            eas_manage_emails_page( 0 ); // Passa un ID non valido
        }
    }
}

/**
 * Funzione per gestire la pagina delle email di una lista.
 *
 * @param int $list_id ID della lista.
 */
function eas_manage_emails_page( $list_id ) {
    global $wpdb;
    $table_emails = $wpdb->prefix . 'eas_emails';

    // Gestione delle azioni
    if ( isset( $_POST['action'] ) && $_POST['action'] == 'add_emails' ) {
        // Verifica il nonce
        if ( ! isset( $_POST['eas_add_emails_nonce'] ) || ! wp_verify_nonce( $_POST['eas_add_emails_nonce'], 'eas_add_emails' ) ) {
            add_action( 'admin_notices', 'eas_error_notice_invalid_nonce' );
        } else {
            $emails_input = sanitize_textarea_field( $_POST['emails'] );
            $emails_array = preg_split( '/\r\n|\r|\n/', $emails_input );
            $emails_array = array_map( 'sanitize_email', $emails_array );
            $emails_array = array_filter( $emails_array );

            $added_emails = 0;
            foreach ( $emails_array as $email ) {
                if ( is_email( $email ) ) {
                    // Verifica se l'email esiste già nella lista
                    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_emails WHERE list_id = %d AND email = %s", $list_id, $email ) );
                    if ( $existing == 0 ) {
                        $wpdb->insert( $table_emails, array( 'list_id' => $list_id, 'email' => $email ) );
                        $added_emails++;
                    }
                }
            }

            if ( $added_emails > 0 ) {
                add_action( 'admin_notices', 'eas_success_notice_add_emails' );
            } else {
                add_action( 'admin_notices', 'eas_error_notice_no_valid_emails' );
            }
        }
    }

    // Visualizzazione delle email
    $emails = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_emails WHERE list_id = %d", $list_id ) );

    ?>
    <div class="wrap">
        <h1><?php printf( esc_html__( 'Gestisci Email nella Lista ID: %d', 'email-article-sender' ), $list_id ); ?></h1>

        <!-- Admin Notices -->
        <?php
        do_action( 'admin_notices' );
        ?>

        <h2>Aggiungi Nuove Email</h2>
        <form method="post">
            <?php wp_nonce_field( 'eas_add_emails', 'eas_add_emails_nonce' ); ?>
            <input type="hidden" name="action" value="add_emails">
            <table class="form-table">
                <tr>
                    <th><label for="emails">Indirizzi Email (una per riga)</label></th>
                    <td><textarea name="emails" id="emails" rows="10" cols="50" class="large-text" required></textarea></td>
                </tr>
            </table>
            <?php submit_button( 'Aggiungi Email' ); ?>
        </form>

        <h2>Email nella Lista</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $emails ) ) : ?>
                    <?php foreach ( $emails as $email ) : ?>
                        <tr>
                            <td><?php echo esc_html( $email->id ); ?></td>
                            <td><?php echo esc_html( $email->email ); ?></td>
                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=eas-lists&action=delete_email&email_id=' . esc_attr( $email->id ) . '&list_id=' . esc_attr( $list_id ) . '&eas_delete_email_nonce=' . wp_create_nonce( 'eas_delete_email' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Sei sicuro di voler eliminare questa email?', 'email-article-sender' ); ?>');"><?php esc_html_e( 'Elimina', 'email-article-sender' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3"><?php esc_html_e( 'Nessuna email nella lista.', 'email-article-sender' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="<?php echo admin_url( 'admin.php?page=eas-lists' ); ?>">&#8592; <?php esc_html_e( 'Torna alle Liste', 'email-article-sender' ); ?></a>
    </div>
    <?php
}

/**
 * Funzioni per le Admin Notices
 */

// Successo nell'aggiunta di una lista
function eas_success_notice_add_list() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Lista aggiunta con successo.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Errore: nome lista vuoto
function eas_error_notice_empty_list_name() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'Il nome della lista non può essere vuoto.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Successo nell'eliminazione di una lista
function eas_success_notice_delete_list() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Lista eliminata con successo.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Errore: lista non valida
function eas_error_notice_invalid_list() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'ID della lista non valida.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Successo nell'aggiunta delle email
function eas_success_notice_add_emails() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Email aggiunte con successo.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Errore: nessuna email valida trovata
function eas_error_notice_no_valid_emails() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'Nessuna email valida trovata da aggiungere.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Successo nell'eliminazione di un'email
function eas_success_notice_delete_email() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Email eliminata con successo.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Errore: email non valida
function eas_error_notice_invalid_email() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'ID dell\'email non valida.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}

// Errore: nonce non valido
function eas_error_notice_invalid_nonce() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'Nonce non valido. Per favore, riprova.', 'email-article-sender' ); ?></p>
    </div>
    <?php
}
?>
