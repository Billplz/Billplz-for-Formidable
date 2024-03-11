<?php
class FrmBillplzPaymentSettings
{

    var $settings;

    function __construct()
    {
        $this->set_default_options();
    }
    
    function default_options()
    {
        $siteurl = get_option('siteurl');
        return array(
            'is_sandbox'             => 'production',
            'api_key'                => '',
            'collection_id'          => '',
            'x_signature'            => '',
            // not using description to avoid conflict
            'bill_description'       => 'Payment',
            'send_copy'              => '0',
            'reference_1_label'      => '',
            'reference_1'            => '',
            'reference_2_label'      => '',
            'reference_2'            => '',
            'update_bill_id'         => '',
            'currency'               => 'MYR',
            'return_url'             => $siteurl,
            'cancel_url'             => $siteurl,
            'callback_log'           => false,
            'callback_log_file'      => dirname(dirname(__FILE__)) .'/log/callback_results.log'
        );
    }
    

    function set_default_options($settings = false)
    {
        $default_settings = $this->default_options();
        
        if ($settings === true) {
            $settings = new stdClass();
        } elseif (!$settings) {
            $settings = $this->get_options();
        }
            
        if (!isset($this->settings)) {
            $this->settings = new stdClass();
        }
        
        foreach ($default_settings as $setting => $default) {
            if (is_object($settings) && isset($settings->{$setting})) {
                $this->settings->{$setting} = $settings->{$setting};
            }
                
            if (!isset($this->settings->{$setting})) {
                $this->settings->{$setting} = $default;
            }

            if ('callback_log_file' == $setting) {
                if ($this->settings->{$setting} == '') {
                    $this->settings->{$setting} = $default;
                }
                $this->settings->{$setting} = stripslashes($this->settings->{$setting});
            }
        }
        
        if ($this->settings->callback_log_file == '.callback_results.log') {
            $this->settings->callback_log_file = $default_settings['callback_log_file'];
        }
        
        $this->settings = apply_filters('frm_billplz_settings', $this->settings);
    }
    
    function get_options()
    {
        $settings = get_option('frm_billplz_options');

        if (!is_object($settings)) {
            if ($settings) { //workaround for W3 total cache conflict
                $settings = unserialize(serialize($settings));
            } else {
                // If unserializing didn't work
                if (!is_object($settings)) {
                    if ($settings) { //workaround for W3 total cache conflict
                        $settings = unserialize(serialize($settings));
                    } else {
                        $settings = $this->set_default_options(true);
                    }
                    $this->store();
                }
            }
        } else {
            $this->set_default_options($settings);
        }
        
        return $this->settings;
    }

    function validate($params, $errors)
    {
        if (empty($params[ 'frm_billplz_is_sandbox' ])) {
            $errors[] = __('Please select is sandbox mode', 'frmbz');
        }
        if (empty($params[ 'frm_billplz_api_key' ])) {
            $errors[] = __('Please enter an API Key', 'frmbz');
        }
        if (empty($params[ 'frm_billplz_collection_id' ])) {
            $errors[] = __('Please enter a Collection ID', 'frmbz');
        }
        if (empty($params[ 'frm_billplz_x_signature' ])) {
            $errors[] = __('Please enter an X Signature Key', 'frmbz');
        }
        return $errors;
    }

    function update($params)
    {
        $settings = $this->default_options();
        
        foreach ($settings as $setting => $default) {
            if (! isset($params['frm_billplz_'. $setting])) {
                continue;
            }
            if ('callback_log_file' == $setting) {
                // allow for Windows paths
                $this->settings->{$setting} = addslashes($params['frm_billplz_'. $setting]);
            } else {
                $this->settings->{$setting} = $params['frm_billplz_'. $setting];
            }
        }
        
        $this->settings->callback_log = isset($params['frm_billplz_callback_log']) ? $params['frm_billplz_callback_log'] : 0;
    }

    function store()
    {
        // Save the posted value in the database
        update_option('frm_billplz_options', $this->settings);
    }
}
