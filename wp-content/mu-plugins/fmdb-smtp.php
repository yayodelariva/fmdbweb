<?php
/**
 * Plugin Name: FMDB SMTP — MailHog (dev)
 * Description: Routes wp_mail() through the local MailHog container. Dev only.
 */

// MailHog only exists in local Docker — bail on any non-local environment.
if ( false === strpos( site_url(), 'localhost' ) ) {
    return;
}

add_action( 'phpmailer_init', function ( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'mailhog';
    $phpmailer->Port       = 1025;
    $phpmailer->SMTPAuth   = false;
    $phpmailer->SMTPSecure = '';
}, 9999 );
