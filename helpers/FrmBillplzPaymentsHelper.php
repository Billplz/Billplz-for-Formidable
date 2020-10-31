<?php

class FrmBillplzPaymentsHelper
{

    /**
     * Get the url of a file inside the plugin
     * @since 3.0
     */
    public static function get_file_url($file = '')
    {
        return plugins_url($file, dirname(__FILE__));
    }

    public static function get_default_options()
    {
        $frm_payment_settings = new FrmBillplzPaymentSettings();

        return array(
            'repeat_num' => 1, 'repeat_time' => 'M',
            'retry' => 0, 'trial' => 0, 'trial_amount' => '',
            'trial_num' => 1, 'trial_time' => 'M',
            'change_field' => array(),
            'action_id' => 0,
            'name_field'     => '',
            'name'           => '',
            'amount'         => '',
            'amount_field'   => '',
            'email'          => '',
            'email_field'    => '',
            'mobile'         => '',
            'mobile_field'   => '',
            'description_field' => '',
            'reference_1_field' => '',
            'reference_2_field' => '',
            'stop_email'     => '',
            'is_sandbox'        => $frm_payment_settings->settings->is_sandbox,
            'api_key'        => $frm_payment_settings->settings->api_key,
            'collection_id'  => $frm_payment_settings->settings->collection_id,
            'x_signature'    => $frm_payment_settings->settings->x_signature,
            'description'    => $frm_payment_settings->settings->description,
            'reference_1_label'    => $frm_payment_settings->settings->reference_1_label,
            'reference_1'    => $frm_payment_settings->settings->reference_1,
            'reference_2_label'    => $frm_payment_settings->settings->reference_2_label,
            'reference_2'    => $frm_payment_settings->settings->reference_2,
            'currency'       => $frm_payment_settings->settings->currency,
            'return_url'     => $frm_payment_settings->settings->return_url,
            'cancel_url'     => $frm_payment_settings->settings->cancel_url,
        );
    }

    public static function get_action_setting($option, $atts = array())
    {
        $frm_payment_settings = new FrmBillplzPaymentSettings();
        if (! isset($atts['settings']) && isset($atts['payment'])) {
            $atts['payment'] = (array) $atts['payment'];
            if (isset($atts['payment']['action_id']) && ! empty($atts['payment']['action_id'])) {
                $form_action = FrmBillplzPaymentAction::get_payment_action($atts['payment']['action_id']);
                $atts['settings'] = $form_action->post_content;
            }
        }

        $value = isset($atts['settings'][ $option ]) ? $atts['settings'][ $option ] : $frm_payment_settings->settings->{$option};

        return $value;
    }

    /*
    * Check if the version number of Formidable is below 2.0
    */
    public static function is_below_2()
    {
        $frm_version = is_callable('FrmAppHelper::plugin_version') ? FrmAppHelper::plugin_version() : 0;
        return version_compare($frm_version, '1.07.19') == '-1';
    }

    /*
    * Check global $frm_settings as a 2.0 fallback
    */
    public static function get_settings()
    {
        global $frm_settings;
        if (! empty($frm_settings)) {
            return $frm_settings;
        } elseif (is_callable('FrmAppHelper::get_settings')) {
            return FrmAppHelper::get_settings();
        } else {
            return array();
        }
    }

    /**
     * @since 2.04.02
     */
    public static function stop_email_set($form_id, $settings = array())
    {
        if (empty($settings)) {
            $form = FrmForm::getOne($form_id);
            if (empty($form)) {
                return false;
            }
            $settings = self::get_form_settings($form);
        }

        return ( isset($settings['stop_email']) && ! empty($settings['stop_email']) );
    }

    /**
     * @since 2.04.02
     */
    public static function get_form_settings($form)
    {
        $form_settings = $form->options;
        if (( isset($form->options['billplz']) && ! empty($form->options['billplz']) ) || ! class_exists('FrmFormActionsHelper')) {
            return $form_settings;
        }

        // get the 2.0 form action settings
        $action_control = FrmFormActionsHelper::get_action_for_form($form->id, 'billplz', 1);
        if (! $action_control) {
            return;
        }
        $form_settings = $action_control->post_content;

        return $form_settings;
    }

    public static function after_payment_field_dropdown($atts)
    {
        $dropdown = '<select name="' . esc_attr($atts['name']) . '[' . absint($atts['row_num']) . '][id]" >';
        $dropdown .= '<option value="">' . __('&mdash; Select Field &mdash;', 'frmbz') . '</option>';

        foreach ($atts['form_fields'] as $field) {
            $selected = selected($atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['id'], $field->id, false);
            $label = FrmAppHelper::truncate($field->name, 20);
            $dropdown .= '<option value="' . esc_attr($field->id) . '" '. $selected . '>' . $label . '</option>';
        }
        $dropdown .= '</select>';
        return $dropdown;
    }

    public static function after_payment_status($atts)
    {
        $status = array(
            'complete' => __('Completed', 'frmbz'),
            'failed' => __('Failed', 'frmbz'),
        );
        $input = '<select name="' . esc_attr($atts['name']) . '[' . absint($atts['row_num']) . '][status]">';
        foreach ($status as $value => $name) {
            $selected = selected($atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['status'], $value, false);
            $input .= '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        $input .= '</select>';
        return $input;
    }

    public static function get_repeat_times()
    {
        return array(
            'D' => __('days', 'frmbz'),
            'W' => __('weeks', 'frmbz'),
            'M' => __('months', 'frmbz'),
            'Y' => __('years', 'frmbz'),
        );
    }

    public static function get_currencies($currency = false)
    {
        $currencies = array(
            'MYR' => array(
                'name' => __('Malaysian Ringgit', 'frmbz'),
                'symbol_left' => '&#82;&#77;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            )
        );

        $currencies = apply_filters('frm_currencies', $currencies);
        if ($currency) {
            $currency = strtoupper($currency);
            if (isset($currencies[ $currency ])) {
                $currencies = $currencies[ $currency ];
            }
        }
            
        return $currencies;
    }
    
    public static function format_for_url($value)
    {
        if (seems_utf8($value)) {
            $value = utf8_uri_encode($value, 200);
        } else {
            $value = strip_tags($value);
        }
        $value = urlencode($value);
        return $value;
    }
    
    public static function formatted_amount($payment)
    {
        $frm_payment_settings = new FrmBillplzPaymentSettings();
        $currency = $frm_payment_settings->settings->currency;
        $amount = $payment;

        if (is_object($payment) || is_array($payment)) {
            $payment = (array) $payment;
            $amount = $payment['amount'];
            $currency = self::get_action_setting('currency', array( 'payment' => $payment ));
        }

        $currency = self::get_currencies($currency);

        $formatted = $currency['symbol_left'] . $currency['symbol_padding'] .
            number_format($amount, $currency['decimals'], $currency['decimal_separator'], $currency['thousand_separator']) .
            $currency['symbol_padding'] . $currency['symbol_right'];

        return $formatted;
    }

    public static function get_rand($length)
    {
        $all_g = 'ABCDEFGHIJKLMNOPQRSTWXZ';
        $all_len = strlen($all_g) - 1;
        $pass = '';
        for ($i=0; $i < $length; $i++) {
            $pass .= $all_g[ rand(0, $all_len) ];
        }
        return $pass;
    }
    
    public static function log_message($text)
    {
        $frm_payment_settings = new FrmBillplzPaymentSettings();
        if (! $frm_payment_settings->settings->callback_log) {
            return;  // is logging turned off?
        }

        $logged = false;
        $access_type = get_filesystem_method();
        if ($access_type === 'direct') {
            $creds = request_filesystem_credentials(site_url() .'/wp-admin/', '', false, false, array());

            // initialize the API
            if (WP_Filesystem($creds)) {
                global $wp_filesystem;

                $chmod_dir = defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : ( fileperms(ABSPATH) & 0777 | 0755 );

                $log = $wp_filesystem->get_contents($frm_payment_settings->settings->callback_log_file);
                $log .= $text . "\n\n";
                $wp_filesystem->put_contents($frm_payment_settings->settings->callback_log_file, $log, 0600);
                $logged = true;
            }
        }

        if (! $logged) {
            error_log($text);
        }
    }

    /**
     * Used for debugging, this function will output all the field/value pairs
     * that are currently defined in the instance of the class using the
     * add_field() function.
     */
    public static function dump_fields($fields)
    {
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
