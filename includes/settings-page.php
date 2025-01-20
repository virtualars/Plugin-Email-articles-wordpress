<?php
function eas_settings_page() {
    // Salva le impostazioni
    if ( isset( $_POST['eas_save_settings'] ) ) {
        update_option( 'eas_email_header', wp_kses_post( $_POST['eas_email_header'] ) );
        update_option( 'eas_email_footer', wp_kses_post( $_POST['eas_email_footer'] ) );
        update_option( 'eas_batch_size', intval( $_POST['eas_batch_size'] ) );
        update_option( 'eas_interval', intval( $_POST['eas_interval'] ) );
        echo '<div class="updated"><p>Impostazioni salvate con successo.</p></div>';
    }

    // Recupera le impostazioni
    $email_header = get_option( 'eas_email_header', '' );
    $email_footer = get_option( 'eas_email_footer', '' );
    $batch_size = get_option( 'eas_batch_size', 50 );
    $interval = get_option( 'eas_interval', 900 );

    ?>
    <div class="wrap">
        <h1>Impostazioni</h1>
        <form method="post">
            <h2>Header Email</h2>
            <?php
            wp_editor( $email_header, 'eas_email_header', array(
                'textarea_name' => 'eas_email_header',
                'teeny' => true,
                'textarea_rows' => 10,
            ) );
            ?>

            <h2>Footer Email</h2>
            <?php
            wp_editor( $email_footer, 'eas_email_footer', array(
                'textarea_name' => 'eas_email_footer',
                'teeny' => true,
                'textarea_rows' => 10,
            ) );
            ?>

            <h2>Impostazioni di Invio</h2>
            <table class="form-table">
                <tr>
                    <th><label for="eas_batch_size">Numero di Email per Batch</label></th>
                    <td><input type="number" name="eas_batch_size" id="eas_batch_size" value="<?php echo $batch_size; ?>" class="small-text" required></td>
                </tr>
                <tr>
                    <th><label for="eas_interval">Intervallo tra i Batch (in secondi)</label></th>
                    <td><input type="number" name="eas_interval" id="eas_interval" value="<?php echo $interval; ?>" class="small-text" required></td>
                </tr>
            </table>

            <?php submit_button( 'Salva Impostazioni', 'primary', 'eas_save_settings' ); ?>
        </form>
    </div>
    <?php
}
