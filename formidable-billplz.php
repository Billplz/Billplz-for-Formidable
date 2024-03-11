<?php
/**
 * Plugin Name: Billplz for Formidable
 * Description: Billplz. Fair payment platform.
 * Version: 3.2.6
 * Plugin URI: http://github.com/billplz/billplz-for-formidable
 * Author URI: https://www.billplz.com
 * Author: Billplz Sdn Bhd
 * License: GPLv3
 * Text Domain: frmbz
*/

if ( !defined( 'ABSPATH' ) ) exit;

define( 'FRM_BILLPLZ_FILE',  __FILE__ );
define( 'FRM_BILLPLZ_URL', plugin_dir_url(FRM_BILLPLZ_FILE));
define( 'FRM_BILLPLZ_PATH', plugin_dir_path(FRM_BILLPLZ_FILE));
define( 'FRM_BILLPLZ_BASENAME', plugin_basename(FRM_BILLPLZ_FILE));
define( 'FRM_BILLPLZ_VER',  '3.2.6' );

function frm_billplz_forms_autoloader($class_name)
{
    // Only load FrmBillplzPayment classes here
    if (! preg_match('/^FrmBillplzPayment.+$/', $class_name) && $class_name != 'FrmBillplzPayment') {
        return;
    }

    $filepath = dirname(__FILE__);

    if (preg_match('/^.+Helper$/', $class_name)) {
        $filepath .= '/helpers';
    } elseif (preg_match('/^.+Controller$/', $class_name)) {
        $filepath .= '/controllers';
    } else {
        $filepath .= '/models';
    }

    $filepath .= '/'. $class_name .'.php';

    if (file_exists($filepath)) {
        include($filepath);
    }
}

// if __autoload is active, put it on the spl_autoload stack
if (is_array(spl_autoload_functions()) && in_array('__autoload', spl_autoload_functions())) {
    spl_autoload_register('__autoload');
}

// Add the autoloader
spl_autoload_register('frm_billplz_forms_autoloader');

FrmBillplzPaymentsController::load_hooks();
FrmBillplzPaymentSettingsController::load_hooks();
