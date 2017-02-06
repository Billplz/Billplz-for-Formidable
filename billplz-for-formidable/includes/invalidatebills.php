<?php

function frmbillplz_activate_cron() {
    if (!wp_next_scheduled('frmbillplz_bills_invalidator')) {
        wp_schedule_event(time(), 'bff', 'frmbillplz_bills_invalidator');
    }
}

function frmbillplz_deactivate_cron() {
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled('frmbillplz_bills_invalidator');
    // unschedule previous event if any
    wp_unschedule_event($timestamp, 'frmbillplz_bills_invalidator');
}

function frmbillplz_cron_add($schedules) {

    $masa = 10;
    // Register the time
    $schedules['bff'] = array(
        'interval' => $masa * 86400,
        'display' => __('Billplz for Formidable')
    );
    return $schedules;
}

function frmbillplz_delete_bills() {

    global $wpdb;
    $sql = "SELECT bill_id, api_key, mode FROM  {$wpdb->prefix}frm_billplz WHERE 1";
    $bill_id = $wpdb->get_results($sql);

    if (!empty($bill_id)) {
        foreach ($bill_id as $value) {
            $bill_id = $value->bill_id;
            $api_key = $value->api_key;
            $mode = $value->mode;
            $billplz = new DeleteBill($api_key, $mode);

            if ($billplz->prepare()->setInfo($bill_id)->process()->checkBill()) {
                $wpdb->delete($wpdb->prefix . frm_billplz, [
                    'bill_id' => $bill_id,
                ]);
            }
        }
    }
}

if (!class_exists('DeleteBill')) {

    class DeleteBill {

        var $api_key, $mode, $id, $objdelete, $objcheck;

        public function __construct($api_key, $mode) {
            require_once(__DIR__ . '/../controllers/billplz.php');
            $this->api_key = $api_key;
            $this->mode = $mode;
            $this->objdelete = new curlaction;
            $this->objcheck = new billplz;
        }

        public function prepare() {
            $this->objdelete->setAPI($this->api_key)->setAction('DELETE');
            return $this;
        }

        public function setInfo($id) {
            //this->id is saved for checkBill() function
            $this->id = $id;
            $this->objdelete->setURL($this->mode, $id);
            return $this;
        }

        public function process() {
            $this->objdelete->curl_action('');
            return $this;
        }

        public function checkBill() {
            $data = $this->objcheck->check_bill($this->api_key, $this->id, $this->mode);
            if (isset($data['state'])) {
                // Hidden dah buang. Paid tak boleh buang
                if ($data['state'] == 'hidden' || $data['state'] == 'paid') {
                    // True maksudnya dah buang
                    return true;
                }
                // False maknya tak buang
                return false;
            } else {
                return false;
            }
        }

    }

}