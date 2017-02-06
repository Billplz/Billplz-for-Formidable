<?php

class FrmBillplzDb {

    var $payments;

    function __construct() {
        global $wpdb;
        $this->payments = $wpdb->prefix . 'frm_payments';
        $this->billplz = $wpdb->prefix . 'frm_billplz';
    }

    function upgrade($old_db_version = false) {
        global $wpdb;

        $db_version = FrmBillplzPaymentsController::$db_version; //$db_version is the version of the database we're moving to
        $db_opt_name = FrmBillplzPaymentsController::$db_opt_name;

        if (!$old_db_version)
            $old_db_version = get_option($db_opt_name);

        if ($db_version != $old_db_version) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset))
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                if (!empty($wpdb->collate))
                    $charset_collate .= " COLLATE $wpdb->collate";
            }

            /* Create/Upgrade Payments Table */
            $sql = "CREATE TABLE {$this->payments} (
                    id bigint(20) NOT NULL auto_increment,
                    meta_value longtext default NULL,
                    receipt_id varchar(100) default NULL,
                    item_id bigint(20) NOT NULL,
					action_id bigint(20) NOT NULL,
                    amount decimal(12,2) NOT NULL default '0.00',
                    completed smallint(6) default '0',
                    begin_date date NOT NULL,
                    expire_date date default NULL,
                    paysys varchar(100) default NULL,
                    created_at datetime NOT NULL,
                    PRIMARY KEY  (id),
                    KEY item_id (item_id)
                  ) {$charset_collate};";

            dbDelta($sql);


            /*             * *** SAVE DB VERSION **** */
            update_option($db_opt_name, $db_version);
        }
    }

    function billplz() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = '';
        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset))
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if (!empty($wpdb->collate))
                $charset_collate .= " COLLATE $wpdb->collate";
        }

        /* Create/Upgrade Billplz Table */
        $sql = "CREATE TABLE {$this->billplz} (
                    id bigint(20) NOT NULL auto_increment,
                    form_id bigint(20) NOT NULL,                    
                    item_id bigint(20) NOT NULL,       
                    action_id bigint(20) NOT NULL,
                    bill_id varchar(25) NOT NULL,
                    api_key varchar(100) NOT NULL,
                    mode varchar(11) NOT NULL,
                    url varchar(100) NOT NULL,
                    hash varchar(5) NOT NULL,
                    PRIMARY KEY  (id),
                    KEY item_id (item_id)
                  ) {$charset_collate};";

        dbDelta($sql);
    }

}
