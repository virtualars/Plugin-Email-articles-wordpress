<?php
function eas_send_page() {
    global $wpdb;

    // Recupera le liste
    $lists = eas_get_lists();

    // Recupera gli articoli
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );
    $posts = get_posts( $args );

    ?>
    <div class="wrap">
        <h1>Invia Articoli</h1>
        <form id="eas-send-form" method="post">
            <input type="hidden" name="action" value="eas_send_articles">
            <h2>Seleziona Articoli da Inviare</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="eas-select-all-posts"></th>
                        <th>Titolo</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $posts as $post ) : ?>
                        <tr>
                            <td><input type="checkbox" name="selected_posts[]" value="<?php echo $post->ID; ?>"></td>
                            <td><?php echo esc_html( $post->post_title ); ?></td>
                            <td><?php echo esc_html( $post->post_date ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Seleziona Liste di Destinatari</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="eas-select-all-lists"></th>
                        <th>Nome Lista</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $lists as $list ) : ?>
                        <tr>
                            <td><input type="checkbox" name="list_ids[]" value="<?php echo $list->id; ?>"></td>
                            <td><?php echo esc_html( $list->list_name ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button( 'Invia Articoli' ); ?>
        </form>

        <div id="eas-progress-wrap" style="display:none;">
            <h2>Invio in corso...</h2>
            <div id="eas-progress-bar" style="width:100%; background:#e1e1e1; height:30px; border-radius:5px;">
                <div id="eas-progress" style="width:0%; background:#0073aa; height:30px; border-radius:5px;"></div>
            </div>
            <p id="eas-progress-text">0% completato</p>
        </div>
    </div>
    <?php
}

// Ajax handler per l'invio degli articoli
function eas_send_articles_ajax() {
    check_ajax_referer( 'eas_send_articles_nonce', 'security' );

    $selected_posts = $_POST['selected_posts'];
    $list_ids       = array_map( 'intval', $_POST['list_ids'] );

    // Impostazioni di invio
    $batch_size = get_option( 'eas_batch_size', 50 );
    $interval   = get_option( 'eas_interval', 900 ); // 900 secondi = 15 minuti

    // Avvia l'invio in batch
    $total_batches = eas_send_emails_in_batches( $list_ids, $selected_posts, $batch_size, $interval );

    wp_send_json_success( array( 'total_batches' => $total_batches ) );
}
add_action( 'wp_ajax_eas_send_articles', 'eas_send_articles_ajax' );

// Funzione per inviare email in batch
function eas_send_emails_in_batches( $list_ids, $articles, $batch_size, $interval ) {
    global $wpdb;
    $emails = eas_get_emails_by_lists( $list_ids );

    // Rimuovi eventuali duplicati
    $emails = array_unique( $emails );

    // Suddivide gli indirizzi in batch
    $email_batches = array_chunk( $emails, $batch_size );
    $total_batches = count( $email_batches );

    // Schedula l'invio dei batch
    foreach ( $email_batches as $index => $batch ) {
        $batch_data = array(
            'emails'        => $batch,
            'articles'      => $articles,
            'batch_number'  => $index + 1,
            'total_batches' => $total_batches,
        );
        wp_schedule_single_event( time() + ( $interval * $index ), 'eas_send_email_batch', array( $batch_data ) );
    }

    return $total_batches;
}

// Hook per inviare il batch di email
add_action( 'eas_send_email_batch', 'eas_process_email_batch', 10, 1 );
function eas_process_email_batch( $batch_data ) {
    $batch    = $batch_data['emails'];
    $articles = $batch_data['articles'];

    foreach ( $batch as $email ) {
        // Costruisci il contenuto dell'email
        $content = eas_build_email_content( $articles );
        // Invia l'email
        wp_mail( $email, 'Novità da Caltaqua', $content, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }

    // Aggiorna lo stato di avanzamento se necessario
}

// Funzione per costruire il contenuto dell'email utilizzando tabelle per una migliore compatibilità e responsività
function eas_build_email_content( $articles ) {
    // Recupera le opzioni di intestazione e piè di pagina
    $header  = get_option( 'eas_email_header' );
    $footer  = get_option( 'eas_email_footer' );

    // Inizia la struttura della tabella principale con una larghezza massima di 600px e centratura
    $content = "
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f4f4f4; padding:20px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' border='0' style='background-color:#ffffff; padding:20px; max-width:600px; width:100%;'>
                    <!-- Header -->
                    <tr>
                        <td align='center' style='padding-bottom:20px;'>
                            {$header}
                        </td>
                    </tr>
    ";

    // Itera attraverso ciascun articolo selezionato
    foreach ( $articles as $article_id ) {
        $post = get_post( $article_id );
        if ( ! $post ) continue; // Salta se il post non esiste

        $title           = esc_html( $post->post_title );
        $permalink       = esc_url( get_permalink( $article_id ) );
        $featured_image  = get_the_post_thumbnail_url( $article_id, 'full' );

        // Recupera il contenuto completo dell'articolo e applica i filtri di WordPress
        $full_content    = apply_filters( 'the_content', $post->post_content );

        // Inizio sezione articolo
        $content .= "
                    <!-- Articolo {$article_id} -->
                    <tr>
                        <td>
                            <table width='600' cellpadding='0' cellspacing='0' border='0' style='border-collapse:collapse;'>
                                <!-- Titolo -->
                                <tr>
                                    <td align='center' style='font-size:24px; font-family:Arial, sans-serif; color:#333333; padding-bottom:10px;'>
                                        <a href='{$permalink}' style='text-decoration:none; color:#333333;'>{$title}</a>
                                    </td>
                                </tr>
        ";

        // Aggiungi immagine se presente
        if ( $featured_image ) {
            $content .= "
                                <!-- Immagine -->
                                <tr>
                                    <td align='center' style='padding-bottom:10px;'>
                                        <img src='{$featured_image}' alt='{$title}' style='max-width:100%; width:100%; height:auto; display:block;' border='0'>
                                    </td>
                                </tr>
            ";
        }

        // Contenuto completo dell'articolo
        $content .= "
                                <!-- Contenuto -->
                                <tr>
                                    <td style='font-size:16px; font-family:Arial, sans-serif; color:#555555; line-height:1.5;'>
                                        {$full_content}
                                    </td>
                                </tr>

                                <!-- Link per leggere di più -->
                                <tr>
                                    <td align='center' style='padding-top:10px;'>
                                        <a href='{$permalink}' style='font-size:14px; font-family:Arial, sans-serif; color:#000000; text-decoration:none;'>Hai difficoltà a leggere questa news? Clicca qui</a>
                                    </td>
                                </tr>

                                <!-- Separatore -->
                                <tr>
                                    <td style='padding-top:20px; border-bottom:1px solid #dddddd;'></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
        ";
    }

    // Chiudi la struttura della tabella principale e aggiungi il piè di pagina
    $content .= "
                    <!-- Footer -->
                    <tr>
                        <td align='center' style='padding-top:20px;'>
                            {$footer}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    ";

    return $content;
}
