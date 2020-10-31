<?php

class FrmBillplzPaymentMultithread
{
    
    function create($bill_id)
    {
        if (empty($bill_id)){
            return false;
        }

        global $wpdb;

        $new_value = array('bill_id' => $bill_id);

        $query_results = $wpdb->insert($wpdb->prefix .'frm_billplz_multithread', $new_value);

        return $wpdb->insert_id;
    }
    
    function delete($bill_id)
    {
        global $wpdb;

        $result = $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'frm_billplz_multithread WHERE bill_id=%s', $bill_id));
        return $result;
    }
}
