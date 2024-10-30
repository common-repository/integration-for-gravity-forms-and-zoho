<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

$uninstall = get_option( 'igzf_zoho_uninstall' );
if ( $uninstall ) {
    delete_option( 'igzf_zoho_domain' );
    delete_option( 'igzf_zoho_client_id' );
    delete_option( 'igzf_zoho_client_secret' );
    delete_option( 'igzf_zoho' );
    delete_option( 'igzf_zoho_token' );
}