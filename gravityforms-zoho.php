<?php
/*
* Plugin Name: Integration for Gravity Forms with Zoho CRM
* Description: Integration for Gravity Forms with Zoho CRM is a Zoho CRM integration plugin for WordPress that makes it really simple to send your Gravity forms directly to your Zoho CRM account. After the integration, submitted forms are automatically added as lead, contact, or others modules in Zoho CRM to the specified account in Zoho CRM.
Tags: 	gravity forms, zoho crm, CRM Lead Magnet,Lead Magnet, zoho add-on, zoho extension, zoho plugin, gf zoho integration, gravity forms zoho crm addon, gravity forms zoho crm contact capture, gravity forms zoho crm integration, gravity forms zoho crm lead capture, gravity forms zoho crm lead generate, gravity forms zoho crm lead generation, gravity forms zoho crm plugin, web to case, web to contact, web to lead, zoho crm, zoho crm case integration, zoho crm contact integration, zoho crm lead integration
* Version:     1.0.3
* Stable tag: 5.7.1
* Requires at least: 4.5
* Tested up to: 5.8
* Text Domain: gravity-form-zoho
* Domain Path: /languages/
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

define( 'ZGF_ZOHO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
include_once ZGF_ZOHO_PLUGIN_PATH . 'admin/class-zoho-api.php';
include_once ZGF_ZOHO_PLUGIN_PATH . 'admin/admin.php';
include_once ZGF_ZOHO_PLUGIN_PATH . 'admin/createtable.php';
include_once ZGF_ZOHO_PLUGIN_PATH . 'function.php';
if (!function_exists("igzbookadmin_script")) {
function igzbookadmin_script( $data ) {
   wp_register_script( "customscript", plugin_dir_url( __FILE__ ).'/customscript.js', array('jquery') );
   wp_localize_script( 'customscript', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
   wp_enqueue_style( 'style', plugin_dir_url( __FILE__ ) . '/css/style.css',false,'1.1','all');
   wp_enqueue_script( 'jquery' );
   wp_enqueue_script( 'customscript' );
}
}
add_action( 'admin_enqueue_scripts', 'igzbookadmin_script' );
