<?php
class FrmBillplzPaymentSettingsController
{
    public static function load_hooks()
    {
        add_action('frm_add_settings_section', 'FrmBillplzPaymentSettingsController::add_settings_section'); // global settings
        add_action('frm_after_duplicate_form', 'FrmBillplzPaymentSettingsController::duplicate', 15, 2);

        // 2.0 hooks
        add_action('frm_registered_form_actions', 'FrmBillplzPaymentSettingsController::register_actions');
        add_action('frm_add_form_option_section', 'FrmBillplzPaymentSettingsController::actions_js');
        add_action('frm_before_list_actions', 'FrmBillplzPaymentSettingsController::migrate_to_2');

        add_filter('frm_action_triggers', 'FrmBillplzPaymentSettingsController::add_payment_trigger');
        add_filter('frm_email_action_options', 'FrmBillplzPaymentSettingsController::add_trigger_to_action');
        add_filter('frm_twilio_action_options', 'FrmBillplzPaymentSettingsController::add_trigger_to_action');
        add_filter('frm_mailchimp_action_options', 'FrmBillplzPaymentSettingsController::add_trigger_to_action');
        add_filter('frm_register_action_options', 'FrmBillplzPaymentSettingsController::add_payment_trigger_to_register_user_action');
        add_filter('frm_api_action_options', 'FrmBillplzPaymentSettingsController::add_trigger_to_action');
    }

    public static function add_settings_section($sections)
    {
        $sections['billplz'] = array('class' => 'FrmBillplzPaymentSettingsController', 'function' => 'route');
        return $sections;
    }
    
    public static function include_logic_row($meta_name, $form_id, $values)
    {
        if (!is_callable('FrmProFormsController::include_logic_row')) {
            return;
        }
        
        FrmProFormsController::include_logic_row(array(
            'meta_name' => $meta_name,
            'condition' => array(
                'hide_field'    => ( isset($values['hide_field']) && isset($values['hide_field'][$meta_name]) ) ? $values['hide_field'][$meta_name] : '',
                'hide_field_cond' => ( isset($values['hide_field_cond']) && isset($values['hide_field_cond'][$meta_name]) ) ? $values['hide_field_cond'][$meta_name] : '',
                'hide_opt'      => ( isset($values['hide_opt']) && isset($values['hide_opt'][$meta_name]) ) ? $values['hide_opt'][$meta_name] : '',
            ),
            'type' => 'billplz',
            'showlast' => '.frm_add_billplz_logic',
            'key' => 'billplz',
            'form_id' => $form_id,
            'id' => 'frm_logic_billplz_'. $meta_name,
            'names' => array(
                'hide_field'    => 'options[billplz_list][hide_field][]',
                'hide_field_cond' => 'options[billplz_list][hide_field_cond][]',
                'hide_opt'      => 'options[billplz_list][hide_opt][]',
            ),
        ));
    }
        
    public static function display_form($errors = array(), $message = '')
    {
        $frm_payment_settings = new FrmBillplzPaymentSettings();

        require(FrmBillplzPaymentsController::path() .'/views/settings/form.php');
    }

    public static function process_form()
    {
        $frm_payment_settings = new FrmBillplzPaymentSettings();
        
        //$errors = $frm_payment_settings->validate($_POST,array());
        $errors = array();
        $frm_payment_settings->update($_POST);

        if (empty($errors)) {
            $frm_payment_settings->store();
            $message = __('Settings Saved', 'frmbz');
        }
        
        self::display_form($errors, $message);
    }

    public static function route()
    {
        $action = isset($_REQUEST['frm_action']) ? 'frm_action' : 'action';
        $action = FrmAppHelper::get_param($action, '', 'get', 'sanitize_text_field');
        if ($action == 'process-form') {
            return self::process_form();
        } else {
            return self::display_form();
        }
    }
    
    /**
     * switch field keys/ids after form is duplicated
     */
    public static function duplicate($id, $values)
    {
        if (is_callable('FrmProFieldsHelper::switch_field_ids')) {
            // don't switch IDs unless running Formidabe version that does
            return;
        }
        
        $form = FrmForm::getOne($id);
        $new_opts = $values['options'] = $form->options;
        unset($form);
        
        if (!isset($values['options']['api_key']) || empty($values['options']['api_key'])) {
            // don't continue if there aren't api key settings to switch
            return;
        }

        if (!isset($values['options']['collection_id']) || empty($values['options']['collection_id'])) {
            // don't continue if there aren't collection_id settings to switch
            return;
        }

        if (!isset($values['options']['x_signature']) || empty($values['options']['x_signature'])) {
            // don't continue if there aren't x signature key settings to switch
            return;
        }
        
        global $frm_duplicate_ids;
        
        if (is_numeric($new_opts['amount_field']) && isset($frm_duplicate_ids[$new_opts['amount_field']])) {
            $new_opts['amount_field'] = $frm_duplicate_ids[$new_opts['amount_field']];
        }
        
        $new_opts['api_key'] = FrmProFieldsHelper::switch_field_ids($new_opts['api_key']);
        $new_opts['collection_id'] = FrmProFieldsHelper::switch_field_ids($new_opts['collection_id']);
        $new_opts['x_signature'] = FrmProFieldsHelper::switch_field_ids($new_opts['x_signature']);
                
        if ($new_opts != $values['options']) {
            global $wpdb;
            $wpdb->update($wpdb->prefix . 'frm_forms', array( 'options' => maybe_serialize($new_opts) ), array( 'id' => absint($id) ));
        }
    }

    public static function register_actions($actions)
    {
        $actions['billplz'] = 'FrmBillplzPaymentAction';
        return $actions;
    }

    public static function actions_js()
    {
        wp_enqueue_script('frmbz', FrmBillplzPaymentsHelper::get_file_url('frmbz.js'));
    }

    public static function migrate_to_2($form)
    {
        if (! isset($form->options['billplz']) || ! $form->options['billplz']) {
            return;
        }

        if (FrmBillplzPaymentsHelper::is_below_2()) {
            return;
        }

        $action_control = FrmFormActionsController::get_form_actions('billplz');
        if (isset($form->options['billplz_list'])) {
            $form->options['conditions'] = $form->options['billplz_list'];
            unset($form->options['billplz_list']);
        }

        $post_id = $action_control->migrate_to_2($form);

        return $post_id;
    }

    public static function add_payment_trigger($triggers)
    {
        $triggers['billplz'] = __('Successful Billplz payment', 'frmbz');
        $triggers['billplz-failed'] = __('Failed Billplz payment', 'frmbz');
        return $triggers;
    }

    public static function add_trigger_to_action($options)
    {
        $options['event'][] = 'billplz';
        $options['event'][] = 'billplz-failed';
        return $options;
    }

    /**
     * Add the payment trigger to registration 2.0+
     *
     * @since 3.06
     *
     * @param array $options
     *
     * @return array
     */
    public static function add_payment_trigger_to_register_user_action($options)
    {
        if (is_callable('FrmRegUserController::register_user')) {
            $options['event'][] = 'billplz';
        }

        return $options;
    }
}
