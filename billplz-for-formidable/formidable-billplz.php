<?php
/**
 * Plugin Name: Billplz for Formidable
 * Description: Billplz Payment Gateway | Accept Payment using all participating FPX Banking Channels. <a href="https://www.billplz.com/join/8ant7x743awpuaqcxtqufg" target="_blank">Sign up Now</a>.
 * Version: 3.00
 * Plugin URI: http://wanzul-hosting.com
 * Author URI: http://wanzul.net
 * Author: Wan Zulkarnain
 * Text Domain: frmbz
 * License: GPLv3
*/

function frm_billplz_forms_autoloader($class_name) {
    // Only load FrmBillplz classes here
	if ( ! preg_match( '/^FrmBillplz.+$/', $class_name ) && $class_name != 'FrmBillplz' ) {
        return;
    }

    $filepath = dirname(__FILE__);

    if ( preg_match('/^.+Helper$/', $class_name) ) {
        $filepath .= '/helpers';
    } else if ( preg_match('/^.+Controller$/', $class_name) ) {
        $filepath .= '/controllers';
    } else {
        $filepath .= '/models';
    }

    $filepath .= '/'. $class_name .'.php';

    if ( file_exists($filepath) ) {
        include($filepath);
    }
}

// if __autoload is active, put it on the spl_autoload stack
if ( is_array(spl_autoload_functions()) && in_array('__autoload', spl_autoload_functions()) ) {
    spl_autoload_register('__autoload');
}

// Add the autoloader
spl_autoload_register('frm_billplz_forms_autoloader');

FrmBillplzPaymentsController::load_hooks();
FrmBillplzSettingsController::load_hooks();

require __DIR__ . '/includes/invalidatebills.php';

add_action('frmbillplz_bills_invalidator', 'frmbillplz_delete_bills');
add_filter('cron_schedules', 'frmbillplz_cron_add');
register_activation_hook(__FILE__, 'frmbillplz_activate_cron');
register_deactivation_hook(__FILE__, 'frmbillplz_deactivate_cron');
 