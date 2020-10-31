<?php

class FrmBillplzPaymentMultithreadDb
{
   
    var $payments;

    function __construct()
    {
        global $wpdb;
        $this->billplz_multithread = $wpdb->prefix . 'frm_billplz_multithread';
    }

    function upgrade($old_db_version = false)
    {
        global $wpdb;
        
        $db_version = FrmBillplzPaymentsController::$db_multithread_version; //$db_version is the version of the database we're moving to
        $db_opt_name = FrmBillplzPaymentsController::$db_multithread_opt_name;
        
        if (!$old_db_version) {
            $old_db_version = get_option($db_opt_name);
        }

        if ($db_version != $old_db_version) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)) {
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                }
                if (!empty($wpdb->collate)) {
                    $charset_collate .= " COLLATE $wpdb->collate";
                }
            }

            /* Create/Upgrade Multithread Table */
            $sql = "CREATE TABLE {$this->billplz_multithread} (
                    id bigint(20) NOT NULL auto_increment,
                    bill_id varchar(100) NOT NULL,
                    PRIMARY KEY  (id),
                    UNIQUE KEY billplz_id_slug (bill_id)
                  ) {$charset_collate};";

            dbDelta($sql);

            /***** SAVE DB VERSION *****/
            update_option($db_opt_name, $db_version);
        }
    }
}
