<?php
/**
 * Plugin Name: FMDB SMTP — MailHog (dev)
 * Description: Routes wp_mail() through the local MailHog container.
 */
add_action( 'phpmailer_init', function ( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'mailhog';
    $phpmailer->Port       = 1025;
    $phpmailer->SMTPAuth   = false;
    $phpmailer->SMTPSecure = '';
}, 9999 );
