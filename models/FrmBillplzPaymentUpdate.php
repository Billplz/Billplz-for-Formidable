<?php
if ( ! defined( 'ABSPATH' ) ) {
  die( 'You are not allowed to call this page directly.' );
}

class FrmBillplzPaymentUpdate extends FrmAddon {
  public $plugin_file;
  public $plugin_name = 'Billplz for Formidable';
  public $download_id = 163257;
  public $version = '3.2.0';

  public function __construct() {
    $this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-billplz.php';
    parent::__construct();
  }

  public static function load_hooks() {
    add_filter( 'frm_include_addon_page', '__return_true' );
    new FrmBillplzPaymentUpdate();
  }
}
