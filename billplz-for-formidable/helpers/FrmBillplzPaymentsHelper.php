<?php

class FrmBillplzPaymentsHelper {

    /**
     * Get the url of a file inside the plugin
     * @since 3.0
     */
    public static function get_file_url($file = '') {
        return plugins_url($file, dirname(__FILE__));
    }

    public static function get_default_options() {
        $frm_payment_settings = new FrmBillplzSettings();
        // Maybe: Amount field for custom amount by the web administrator
        return array(
            'billplz_item_name' => 'Payment', 'billplz_amount_field' => '',
            'billplz_amount' => '', 'billplz_list' => array(),
            'billplz_stop_email' => 0,
            'billplz_mode' => '',
            'change_field' => array(),
            'action_id' => 0,
            'api_key' => $frm_payment_settings->settings->api_key,
            'collection_id' => $frm_payment_settings->settings->collection_id,
            'currency' => $frm_payment_settings->settings->currency,
            'return_url' => $frm_payment_settings->settings->return_url,
            'cancel_url' => $frm_payment_settings->settings->cancel_url,
        );
    }

    public static function get_action_setting($option, $atts = array()) {
        $frm_payment_settings = new FrmBillplzSettings();
        if (!isset($atts['settings']) && isset($atts['payment'])) {
            $atts['payment'] = (array) $atts['payment'];
            if (isset($atts['payment']['action_id']) && !empty($atts['payment']['action_id'])) {
                $form_action = FrmBillplzAction::get_payment_action($atts['payment']['action_id']);
                $atts['settings'] = $form_action->post_content;
            }
        }

        $value = isset($atts['settings'][$option]) ? $atts['settings'][$option] : $frm_payment_settings->settings->{$option};

        return $value;
    }

    /*
     * Check if the version number of Formidable is below 2.0
     */

    public static function is_below_2() {
        $frm_version = is_callable('FrmAppHelper::plugin_version') ? FrmAppHelper::plugin_version() : 0;
        return version_compare($frm_version, '1.07.19') == '-1';
    }

    /*
     * Check global $frm_settings as a 2.0 fallback
     */

    public static function get_settings() {
        global $frm_settings;
        if (!empty($frm_settings)) {
            return $frm_settings;
        } else if (is_callable('FrmAppHelper::get_settings')) {
            return FrmAppHelper::get_settings();
        } else {
            return array();
        }
    }

    /**
     * @since 2.04.02
     */
    public static function stop_email_set($form_id, $settings = array()) {
        if (empty($settings)) {
            $form = FrmForm::getOne($form_id);
            if (empty($form)) {
                return false;
            }
            $settings = self::get_form_settings($form);
        }

        return ( isset($settings['billplz_stop_email']) && !empty($settings['billplz_stop_email']) );
    }

    /**
     * @since 2.04.02
     */
    public static function get_form_settings($form) {
        $form_settings = $form->options;
        if (( isset($form->options['billplz']) && !empty($form->options['billplz']) ) || !class_exists('FrmFormActionsHelper')) {
            return $form_settings;
        }

        // get the 2.0 form action settings
        $action_control = FrmFormActionsHelper::get_action_for_form($form->id, 'billplz', 1);
        if (!$action_control) {
            return;
        }
        $form_settings = $action_control->post_content;

        return $form_settings;
    }

    public static function after_payment_field_dropdown($atts) {
        $dropdown = '<select name="' . esc_attr($atts['name']) . '[' . absint($atts['row_num']) . '][id]" >';
        $dropdown .= '<option value="">' . __('&mdash; Select Field &mdash;', 'frmbz') . '</option>';

        foreach ($atts['form_fields'] as $field) {
            $selected = selected($atts['form_action']->post_content['change_field'][$atts['row_num']]['id'], $field->id, false);
            $label = FrmAppHelper::truncate($field->name, 20);
            $dropdown .= '<option value="' . esc_attr($field->id) . '" ' . $selected . '>' . $label . '</option>';
        }
        $dropdown .= '</select>';
        return $dropdown;
    }

    public static function after_payment_status($atts) {
        $status = array(
            'complete' => __('Completed', 'frmbz'),
            'failed' => __('Failed', 'frmbz'),
        );
        $input = '<select name="' . esc_attr($atts['name']) . '[' . absint($atts['row_num']) . '][status]">';
        foreach ($status as $value => $name) {
            $selected = selected($atts['form_action']->post_content['change_field'][$atts['row_num']]['status'], $value, false);
            $input .= '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        $input .= '</select>';
        return $input;
    }

    public static function get_currencies($currency = false) {
        $currencies = array(
            'MYR' => array(
                'name' => __('Malaysian Ringgit', 'frmbz'),
                'symbol_left' => '&#82;&#77;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
        );

        $currencies = apply_filters('frm_currencies', $currencies);
        if ($currency) {
            $currency = strtoupper($currency);
            if (isset($currencies[$currency])) {
                $currencies = $currencies[$currency];
            }
        }

        return $currencies;
    }

    public static function format_for_url($value) {
        if (seems_utf8($value)) {
            $value = utf8_uri_encode($value, 200);
        } else {
            $value = strip_tags($value);
        }
        $value = urlencode($value);
        return $value;
    }

    public static function formatted_amount($payment) {
        $frm_payment_settings = new FrmBillplzSettings();
        $currency = $frm_payment_settings->settings->currency;
        $amount = $payment;

        if (is_object($payment) || is_array($payment)) {
            $payment = (array) $payment;
            $amount = $payment['amount'];
            $currency = self::get_action_setting('currency', array('payment' => $payment));
        }

        $currency = self::get_currencies($currency);

        $formatted = $currency['symbol_left'] . $currency['symbol_padding'] .
                number_format($amount, $currency['decimals'], $currency['decimal_separator'], $currency['thousand_separator']) .
                $currency['symbol_padding'] . $currency['symbol_right'];

        return $formatted;
    }

    // If ipn correct & paid, return true
    // If ipn not correct, return false
    public static function get_ipn_data() {

        //global $frm_pay_form_settings;
        //$form = FrmForm::getOne(5);

        if (isset($_POST['id'])) {
            $signal = 'Callback';
            $bill_id = sanitize_text_field($_POST['id']);
            sleep(10);
        } elseif (isset($_GET['billplz']['id'])) {
            $signal = 'Return';
            $bill_id = sanitize_text_field($_GET['billplz']['id']);
        } else {
            wp_die('Hi. What happen to you huh?');
        }

        // Log return type
        self::log_message(__('Response is: ' . $signal . ' ' . print_r($_REQUEST, true), 'frmbz'));
        // Entry ID == Item ID
        $item_form = self::find_item_action_id($bill_id);
        $item_id = $item_form['item_id'];
        $action_id = $item_form['action_id'];
        $amount = $item_form['amount'];

        if (empty($item_id)) {
            self::log_message(__('Bill ID' . $bill_id . ' Not valid.', 'frmbz'));
            wp_die('Item ID or Action ID does not valid');
        }

        // Get payment action from wp_posts table.
        $wp_posts = FrmBillplzAction::get_payment_action($action_id);

        $api_key = $wp_posts->post_content['api_key'];
        $mode = $wp_posts->post_content['billplz_mode'];

        require_once __DIR__ . '/../controllers/billplz.php';
        $billplz = new billplz();
        $data = $billplz->check_bill($api_key, $bill_id, $mode);

        $amt = number_format(($data['amount'] / 100), 2);

        if ($amt !== number_format($amount, 2)) {
            wp_die('Amount paid did not match');
            self::log_message(__('Amount paid did not match.', 'frmbz'));
        }

        self::log_message(__('Bills Re-Query: ' . print_r($data, true), 'frmbz'));

        return [
            'status' => $data['paid'],
            'item_id' => $item_id,
            'action_id' => $action_id,
            'bill_id' => $data['id'],
            'amount' => $amount,
            'completed' => $item_form['completed'],
            'data' => $data,
            'item_action_db' => $item_form,
            'signal' => $signal,
            'return_url' => $wp_posts->post_content['return_url'],
            'cancel_url' => $wp_posts->post_content['cancel_url'],
            'payment_id' => $item_form['id'],
        ];
    }

    public static function find_item_action_id($bill_id) {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "frm_payments` WHERE receipt_id='" . $bill_id . "'";
        $item_id = $wpdb->get_results($sql);
        return [
            'item_id' => $item_id[0]->item_id,
            'action_id' => $item_id[0]->action_id,
            'amount' => $item_id[0]->amount,
            'completed' => $item_id[0]->completed,
            'id' => $item_id[0]->id,
        ];
    }

    public static function log_message($text) {
        $frm_payment_settings = new FrmBillplzSettings();
        if (!$frm_payment_settings->settings->ipn_log) {
            return;  // is logging turned off?
        }

        $logged = false;
        $access_type = get_filesystem_method();
        if ($access_type === 'direct') {
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, array());

            // initialize the API
            if (WP_Filesystem($creds)) {

                global $wp_filesystem;

                $chmod_dir = defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : ( fileperms(ABSPATH) & 0777 | 0755 );

                $log = $wp_filesystem->get_contents($frm_payment_settings->settings->ipn_log_file);
                $log .= $text . "\n\n";
                $wp_filesystem->put_contents($frm_payment_settings->settings->ipn_log_file, $log, 0600);
                $logged = true;
            }
        }

        if (!$logged) {
            error_log($text);
        }
    }

    /**
     * Used for debugging, this function will output all the field/value pairs
     * that are currently defined in the instance of the class using the
     * add_field() function.
     */
    public static function dump_fields($fields) {
        ksort($fields);
        ?>
        <h3>FrmBillplzPaymentsHelper::dump_fields() Output:</h3>
        <table width="95%" border="1" cellpadding="2" cellspacing="0">
            <tr>
                <td bgcolor="black"><b><font color="white">Field Name</font></b></td>
                <td bgcolor="black"><b><font color="white">Value</font></b></td>
            </tr> 

            <?php foreach ($fields as $key => $value) { ?>
                <tr><td><?php echo $key ?></td>
                    <td><?php echo urldecode($value) ?>&nbsp;</td>
                </tr>
            <?php } ?>
        </table>
        <br/>
        <?php
    }

}
