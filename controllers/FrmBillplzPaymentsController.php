<?php

class FrmBillplzPaymentsController
{
    public static $min_version = '3.0';
    public static $db_version = 2;
    public static $db_opt_name = 'frm_billplz_db_version';
    public static $db_multithread_version = 1;
    public static $db_multithread_opt_name = 'frm_billplz_multithread';

    public static function load_hooks()
    {
        register_activation_hook(FRM_BILLPLZ_PATH . 'formidable-billplz.php', 'FrmBillplzPaymentsController::install');
        
        if (is_admin()) {
            add_action('admin_menu', 'FrmBillplzPaymentsController::menu', 25);
            add_filter('frm_nav_array', 'FrmBillplzPaymentsController::frm_nav', 30);
            add_filter('plugin_action_links_' . FRM_BILLPLZ_BASENAME . 'formidable-billplz.php', 'FrmBillplzPaymentsController::settings_link', 10, 2);
            add_action('after_plugin_row_' . FRM_BILLPLZ_PATH . 'formidable-billplz.php', 'FrmBillplzPaymentsController::min_version_notice');
            add_action( 'admin_notices', 'FrmBillplzPaymentsController::get_started_headline' );
            add_action( 'admin_init', 'FrmBillplzPaymentsController::load_updater' );
            add_action( 'wp_ajax_frmbz_install', 'FrmBillplzPaymentsController::install' );
            add_filter('set-screen-option', 'FrmBillplzPaymentsController::save_per_page', 10, 3);

            add_filter('frm_form_options_before_update', 'FrmBillplzPaymentsController::update_options', 15, 2);
            add_action('frm_show_entry_sidebar', 'FrmBillplzPaymentsController::sidebar_list');
        }

        add_action('wp_ajax_frm_payments_billplz_webhook', 'FrmBillplzPaymentsController::billplz_webhook');
        add_action('wp_ajax_nopriv_frm_payments_billplz_webhook', 'FrmBillplzPaymentsController::billplz_webhook');

        // 2.0 hook
        add_action('frm_trigger_billplz_create_action', 'FrmBillplzPaymentsController::create_payment_trigger', 10, 3);

        add_filter('frm_csv_columns', 'FrmBillplzPaymentsController::add_payment_to_csv', 20, 2);
    }

    public static function menu()
    {
        $frm_settings = FrmBillplzPaymentsHelper::get_settings();
        $menu = $frm_settings ? $frm_settings->menu : 'Formidable';

        // Remove and replace "PayPal" with "Payment"
        if(has_action('admin_menu', 'FrmPaymentsController::menu')) {
          remove_action('admin_menu', 'FrmPaymentsController::menu', 26);
          add_submenu_page('formidable', $menu . ' | Payments', 'Payments', 'frm_view_entries', 'formidable-payments', 'FrmBillplzPaymentsController::route');
        } else {
          add_submenu_page('formidable', $menu . ' | Billplz', 'Billplz', 'frm_view_entries', 'formidable-payments', 'FrmBillplzPaymentsController::route');
        }

        add_filter('manage_' . sanitize_title($menu) . '_page_formidable-payments_columns', 'FrmBillplzPaymentsController::payment_columns');
        add_filter('manage_' . sanitize_title($menu) . '_page_formidable-entries_columns', 'FrmBillplzPaymentsController::entry_columns', 20);
        add_filter('frm_entries_payments_column', 'FrmBillplzPaymentsController::entry_payment_column', 10, 2);
        add_filter('frm_entries_current_payment_column', 'FrmBillplzPaymentsController::entry_current_payment_column', 10, 2);
        add_filter('frm_entries_payment_expiration_column', 'FrmBillplzPaymentsController::entry_payment_expiration_column', 10, 2);
    }

    public static function frm_nav($nav)
    {
        if (current_user_can('frm_view_entries')) {
            $nav['formidable-payments'] = 'Billplz';
        }

        return $nav;
    }

    public static function payment_columns($cols = array())
    {
        add_screen_option('per_page', array(
            'label' => __('Payments', 'frmbz'), 'default' => 20,
            'option' => 'formidable_page_formidable_payments_per_page',
        ));

        return array(
            'cb'         => '<input type="checkbox" />',
            'receipt_id' => __('Receipt ID', 'frmbz'),
            'user_id'    => __('User', 'frmbz'),
            'item_id'    => __('Entry', 'frmbz'),
            'form_id'    => __('Form', 'frmbz'),
            'completed'  => __('Completed', 'frmbz'),
            'amount'     => __('Amount', 'frmbz'),
            'created_at' => __('Date', 'frmbz'),
            'begin_date' => __('Begin Date', 'frmbz'),
            'expire_date' => __('Expire Date', 'frmbz'),
            'paysys'     => __('Processor', 'frmbz'),
        );
    }

    // Adds a settings link to the plugins page
    public static function settings_link($links, $file)
    {
        $settings = '<a href="' . esc_url(admin_url('admin.php?page=formidable-settings')) . '">' . __('Settings', 'frmbz') . '</a>';
        array_unshift($links, $settings);

        return $links;
    }

    public static function min_version_notice()
    {
        $frm_version = is_callable('FrmAppHelper::plugin_version') ? FrmAppHelper::plugin_version() : 0;

        // check if Formidable meets minimum requirements
        if (version_compare($frm_version, self::$min_version, '>=')) {
            return;
        }

        $wp_list_table = _get_list_table('WP_Plugins_List_Table');
        echo '<tr class="plugin-update-tr active"><th colspan="' . esc_attr($wp_list_table->get_column_count()) . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
        __('You are running an outdated version of Formidable. This plugin may not work correctly if you do not update Formidable.', 'frmbz') .
        '</div></td></tr>';
    }

    public static function get_started_headline(){
        // Don't display this error as we're upgrading
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        if ( $action == 'upgrade-plugin' && ! isset( $_GET['activate'] ) ) {
            return;
        }

        if ( ! current_user_can( 'administrator' ) ) {
            return;
        }

        $db_version = get_option( self::$db_opt_name );
        if ( (int) $db_version < self::$db_version ) {
            if ( is_callable( 'FrmAppHelper::plugin_url' ) ) {
                $url = FrmAppHelper::plugin_url();
            } else if ( defined( 'FRM_URL' ) ) {
                $url = FRM_URL;
            } else {
                return;
            }
            include( FRM_BILLPLZ_PATH . 'views/notices/update_database.php' );
        }
    }

    public static function load_updater() {
        if ( class_exists( 'FrmAddon' ) ) {
            FrmBillplzPaymentsController::load_hooks();
        }
    }

    public static function install($old_db_version = false)
    {
        $frm_payment_db = new FrmBillplzPaymentDb();
        $frm_payment_db->upgrade($old_db_version);
        $frm_multithread_db = new FrmBillplzPaymentMultithreadDb();
        $frm_multithread_db->upgrade($old_db_version);
    }

    private static function show($id)
    {
        if (! $id) {
            die(__('Please select a payment to view', 'frmbz'));
        }
        
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare("SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id));

        $user_name = '';
        if ($payment->user_id) {
            $user = get_userdata($payment->user_id);
            if ($user) {
                $user_name = '<a href="' . esc_url(admin_url('user-edit.php?user_id=' . $payment->user_id)) . '">' . $user->display_name . '</a>';
            }
        }
        
        include(FRM_BILLPLZ_PATH . 'views/payments/show.php');
    }

    private static function display_list($message = '', $errors = array())
    {
        $title = __('Downloads', 'frmbz');
        $wp_list_table = new FrmBillplzPaymentsListHelper();
        
        $pagenum = $wp_list_table->get_pagenum();
        
        $wp_list_table->prepare_items();

        $total_pages = $wp_list_table->get_pagination_arg('total_pages');
        if ($pagenum > $total_pages && $total_pages > 0) {
            // if the current page is higher than the total pages,
            // reset it and prepare again to get the right entries
            $_GET['paged'] = $_REQUEST['paged'] = $total_pages;
            $pagenum = $wp_list_table->get_pagenum();
            $wp_list_table->prepare_items();
        }

        include(FRM_BILLPLZ_PATH . 'views/payments/list.php');
    }

    public static function save_per_page($save, $option, $value)
    {
        if ($option == 'formidable_page_formidable_payments_per_page') {
            $save = absint($value);
        }
        return $save;
    }

    public static function create_payment_trigger($action, $entry, $form)
    {
        return self::maybe_redirect_for_payment($action->post_content, $entry, $form);
    }

    public static function maybe_redirect_for_payment($settings, $entry, $form)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            // don't send to Billplz if submitting from the back-end
            return;
        }

        $amount = self::get_amount($form, $settings, $entry);
        if (empty($amount)) {
            return;
        }

        // stop the emails if payment is required
        if (FrmBillplzPaymentsHelper::stop_email_set($form, $settings)) {
            self::stop_form_emails();
            add_filter('frm_to_email', 'FrmBillplzPaymentsController::stop_the_email', 20, 4);
            add_filter('frm_send_new_user_notification', 'FrmBillplzPaymentsController::stop_registration_email', 10, 3);
        }

        // save in global for use building redirect url
        global $frm_pay_form_settings;
        $frm_pay_form_settings = $settings;

        // prevent action_id with value 0 to redirect
        if ($settings['action_id'] == '0') {
            FrmBillplzPaymentsHelper::log_message("Please update your Billplz action settings to fix action_id 0 issue.");
            return;
        }

        // trigger payment redirect after other functions have a chance to complete
        add_action('frm_after_create_entry', 'FrmBillplzPaymentsController::redirect_for_payment', 50, 2);

        return true;
    }

    private static function stop_form_emails()
    {
        if (is_callable('FrmNotification::stop_emails')) {
            FrmNotification::stop_emails();
        } else {
            remove_action('frm_trigger_email_action', 'FrmNotification::trigger_email', 10);
        }
    }

    public static function redirect_for_payment($entry_id, $form_id)
    {
        global $frm_pay_form_settings;

        $form = FrmForm::getOne($form_id);

        $atts = array(
            'action_settings' => $frm_pay_form_settings,
            'form'     => $form,
            'entry_id' => $entry_id,
            'entry'    => FrmEntry::getOne($entry_id, true),
        );

        self::check_global_fallbacks( $atts['action_settings'] );

        $amount = self::get_amount($atts['form'], $atts['action_settings'], $atts['entry']);
        $name = self::get_name($atts['form'], $atts['action_settings'], $atts['entry']);
        $email = self::get_email($atts['form'], $atts['action_settings'], $atts['entry']);
        $mobile = self::get_mobile($atts['form'], $atts['action_settings'], $atts['entry']);
        $description = self::get_description($atts['form'], $atts['action_settings'], $atts['entry']);

        $ref_1_label = self::get_reference_1_label($atts['form'], $atts['action_settings'], $atts['entry']);
        $ref_1 = self::get_reference_1($atts['form'], $atts['action_settings'], $atts['entry']);
        $ref_2_label = self::get_reference_2_label($atts['form'], $atts['action_settings'], $atts['entry']);
        $ref_2 = self::get_reference_2($atts['form'], $atts['action_settings'], $atts['entry']);
        $is_sandbox = self::get_is_sandbox($atts['form'], $atts['action_settings'], $atts['entry']);

        if (empty($amount) || empty($name) || empty($description)) {
            return;
        }

        if (empty($email) && (empty($mobile))) {
            return;
        }

        if (is_callable('FrmAppHelper::plugin_version')) {
            $frm_version = FrmAppHelper::plugin_version();
        } else {
            global $frm_version; //global fallback
        }

        $is_sandbox = $is_sandbox == 'sandbox';
        $api_key = trim($frm_pay_form_settings['api_key']);
        $collection_id = trim($frm_pay_form_settings['collection_id']);
        $webhook_url = admin_url('admin-ajax.php?action=frm_payments_billplz_webhook');

        if (empty($api_key) || empty($collection_id) || empty($description)) {
            return;
        }

        $connect = FrmBillplzPaymentsBillplzWPConnectHelper::get_instance();
        $connect->set_api_key($api_key, $is_sandbox);

        $billplz = FrmBillplzPaymentsBillplzAPIHelper::get_instance();
        $billplz->set_connect($connect);

        $parameter = array(
            'collection_id' => $collection_id,
            'email'=> trim($email),
            'mobile'=> trim($mobile),
            'name'=> substr($name, 0, 255),
            'amount'=> strval($amount * 100),
            'callback_url'=> $webhook_url,
            'description'=> substr($description, 0, 200)
        );

        $optional = array(
            'redirect_url' => $webhook_url,
            'reference_1_label' => substr($ref_1_label, 0, 20),
            'reference_1' => substr($ref_1, 0, 120),
            'reference_2_label' => substr($ref_2_label, 0, 20),
            'reference_2' => substr($ref_2, 0, 120)
        );

        $is_send_copy_email  = trim( $frm_pay_form_settings['send_copy_email'] ) === '1';
        $is_send_copy_mobile = trim( $frm_pay_form_settings['send_copy_mobile'] ) === '1';

        if ( $is_send_copy_email && $is_send_copy_mobile ) {
            $send_copy = '3';
        } elseif ( $is_send_copy_mobile ) {
            $send_copy = '2';
        } elseif ( $is_send_copy_email ) {
            $send_copy = '1';
        } else {
            $send_copy = '0';
        }

        list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional, $send_copy));

        if ($rheader != 200) {
            FrmBillplzPaymentsHelper::log_message('Failed to create bill ' . print_r($rbody, true));
            return;
        }

        $atts['amount'] = $amount;
        self::create_invoice_for_payment($atts, $rbody);

        if (is_callable('FrmProEntryMeta::update_single_field') && isset($atts['action_settings']['update_bill_id'])){
            FrmProEntryMeta::update_single_field(array(
              'entry_id' => $entry_id,
              'field_id' => $atts['action_settings']['update_bill_id'],
              'value'    => $rbody['id'],
            ));
        }

        add_filter('frm_redirect_url', 'FrmBillplzPaymentsController::redirect_url', 9, 3);
        $conf_args = array( 'bill_url' => $rbody['url'] );
        if (defined('DOING_AJAX') && isset($form->options['ajax_submit']) && $form->options['ajax_submit'] && $_POST && isset($_POST['action']) && in_array($_POST['action'], array( 'frm_entries_create', 'frm_entries_update' ))) {
            $conf_args['ajax'] = true;
        }

        if (is_callable('FrmProEntriesController::confirmation')) {
          FrmProEntriesController::confirmation('redirect', $form, $form->options, $entry_id, $conf_args);
        } else {
          $conf_args['id'] = $entry_id;
          self::confirmation($form, $conf_args);
        }
    }

    public static function create_invoice_for_payment($atts, $billplz)
    {
        $frm_payment = new FrmBillplzPayment();
        $invoice = $frm_payment->create(array(
            'item_id'     => $atts['entry_id'],
            'meta_value'  => maybe_serialize($billplz),
            'amount'      => $atts['amount'],
            'receipt_id'  => $billplz['id'],
            'action_id'   => $atts['action_settings']['action_id'],
        ));

        return $invoice;
    }

    public static function confirmation($form, $args)
    {
        global $frm_vars;

        add_filter('frm_use_wpautop', '__return_false');

        $success_url = apply_filters('frm_redirect_url', '', $form, $args);
        $success_url = str_replace(array( ' ', '[', ']', '|', '@' ), array( '%20', '%5B', '%5D', '%7C', '%40' ), $success_url);

        if (! headers_sent()) {
            wp_redirect(esc_url_raw($success_url));
            die();
        } else {
            add_filter('frm_use_wpautop', '__return_true');

            $success_msg = isset($form->options['success_msg']) ? $form->options['success_msg'] : __('Please wait while you are redirected.', 'formidable');

            $redirect_msg = '<div class="' . esc_attr(FrmFormsHelper::get_form_style_class($form)) . '"><div class="frm-redirect-msg frm_message">' . $success_msg . '<br/>' .
                sprintf(__('%1$sClick here%2$s if you are not automatically redirected.', 'formidable'), '<a href="'. esc_url($success_url) .'">', '</a>') .
                '</div></div>';

            $redirect_msg = apply_filters('frm_redirect_msg', $redirect_msg, array(
                'entry_id' => $args['id'], 'form_id' => $form->id, 'form' => $form
            ));

            echo $redirect_msg;
            echo "<script type='text/javascript'>jQuery(document).ready(function(){ setTimeout(window.location='" . esc_url_raw($success_url) . "', 8000); });</script>";
        }
    }

    public static function get_amount($form, $settings, $entry = array())
    {
        $amount_field = isset($settings['amount_field']) ? $settings['amount_field'] : '';
        $amount = 0;
        if (empty($entry) && ! empty($amount_field) && isset($_POST['item_meta'][ $amount_field ])) {
            // for reverse compatibility for custom code
            $amount = sanitize_text_field($_POST['item_meta'][ $amount_field ]);
        } elseif (! empty($amount_field) && isset($entry->metas[ $amount_field ])) {
            $amount = $entry->metas[ $amount_field ];
        } elseif (isset($settings['amount'])) {
            $amount = $settings['amount'];
        }

        if (empty($amount)) {
            // no amount has been set
            return 0;
        }

        return self::prepare_amount($amount, $settings);
    }

    private static function prepare_amount($amount, $settings)
    {
        $currency = FrmBillplzPaymentsHelper::get_action_setting('currency', array( 'settings' => $settings ));
        $currencies = FrmBillplzPaymentsHelper::get_currencies($currency);

        $total = 0;
        foreach ((array) $amount as $a) {
            $this_amount = trim($a);
            preg_match_all('/[0-9,.]*\.?\,?[0-9]+/', $this_amount, $matches);
            $this_amount = $matches ? end($matches[0]) : 0;
            $this_amount = self::maybe_use_decimal($this_amount, $currencies);
            $this_amount = str_replace($currencies['decimal_separator'], '.', str_replace($currencies['thousand_separator'], '', $this_amount));
            $this_amount = round((float) $this_amount, $currencies['decimals']);
            $total += $this_amount;
            unset($a, $this_amount, $matches);
        }

        return $total;
    }

    private static function maybe_use_decimal($amount, $currencies)
    {
        if ($currencies['thousand_separator'] == '.') {
            $amount_parts = explode('.', $amount);
            $used_for_decimal = ( count($amount_parts) == 2 && strlen($amount_parts[1]) == 2 );
            if ($used_for_decimal) {
                $amount = str_replace('.', $currencies['decimal_separator'], $amount);
            }
        }
        return $amount;
    }

    public static function get_name($form, $settings, $entry = array())
    {
        $name_field = isset($settings['name_field']) ? $settings['name_field'] : '';
        $name = '';
        if (empty($entry) && ! empty($name_field) && isset($_POST['item_meta'][ $name_field ])) {
            // for reverse compatibility for custom code
            $name = sanitize_text_field($_POST['item_meta'][ $name_field ]);
        } elseif (! empty($name_field) && isset($entry->metas[ $name_field ])) {
            $name = $entry->metas[ $name_field ];
        } elseif (isset($settings['bill_name'])) {
            $name = $settings['bill_name'];
        }

        return trim($name);
    }

    public static function get_email($form, $settings, $entry = array())
    {
        $email_field = isset($settings['email_field']) ? $settings['email_field'] : '';
        $email = '';
        if (empty($entry) && ! empty($email_field) && isset($_POST['item_meta'][ $email_field ])) {
            // for reverse compatibility for custom code
            $email = sanitize_text_field($_POST['item_meta'][ $email_field ]);
        } elseif (! empty($email_field) && isset($entry->metas[ $email_field ])) {
            $email = $entry->metas[ $email_field ];
        } elseif (isset($settings['email'])) {
            $email = $settings['email'];
        }

        return trim($email);
    }

    public static function get_mobile($form, $settings, $entry = array())
    {
        $mobile_field = isset($settings['mobile_field']) ? $settings['mobile_field'] : '';
        $mobile = '';
        if (empty($entry) && ! empty($mobile_field) && isset($_POST['item_meta'][ $mobile_field ])) {
            // for reverse compatibility for custom code
            $mobile = sanitize_text_field($_POST['item_meta'][ $mobile_field ]);
        } elseif (! empty($mobile_field) && isset($entry->metas[ $mobile_field ])) {
            $mobile = $entry->metas[ $mobile_field ];
        } elseif (isset($settings['mobile'])) {
            $mobile = $settings['mobile'];
        }

        return trim($mobile);
    }

    public static function get_description($form, $settings, $entry = array())
    {
        $description_field = isset($settings['description_field']) ? $settings['description_field'] : '';
        $description = '';
        if (empty($entry) && ! empty($description_field) && isset($_POST['item_meta'][ $description_field ])) {
            // for reverse compatibility for custom code
            $description = sanitize_text_field($_POST['item_meta'][ $description_field ]);
        } elseif (! empty($description_field) && isset($entry->metas[ $description_field ])) {
            $description = $entry->metas[ $description_field ];
        } elseif (isset($settings['bill_description'])) {
            $description = $settings['bill_description'];
        }

        return trim($description);
    }

    public static function get_reference_1_label($form, $settings, $entry = array())
    {
        $reference_1_label = isset($settings['reference_1_label']) ? $settings['reference_1_label'] : '';
        return trim($reference_1_label);
    }

    public static function get_reference_2_label($form, $settings, $entry = array())
    {
        $reference_2_label = isset($settings['reference_2_label']) ? $settings['reference_2_label'] : '';
        return trim($reference_2_label);
    }

    public static function get_reference_1($form, $settings, $entry = array())
    {
        $ref_1_field = isset($settings['reference_1_field']) ? $settings['reference_1_field'] : '';
        $ref_1 = '';
        if (empty($entry) && ! empty($ref_1_field) && isset($_POST['item_meta'][ $ref_1_field ])) {
            // for reverse compatibility for custom code
            $ref_1 = sanitize_text_field($_POST['item_meta'][ $ref_1_field ]);
        } elseif (! empty($ref_1_field) && isset($entry->metas[ $ref_1_field ])) {
            $ref_1 = $entry->metas[ $ref_1_field ];
        } elseif (isset($settings['reference_1'])) {
            $ref_1 = $settings['reference_1'];
        }

        return trim($ref_1);
    }

    public static function get_reference_2($form, $settings, $entry = array())
    {
        $ref_2_field = isset($settings['reference_2_field']) ? $settings['reference_2_field'] : '';
        $ref_2 = '';
        if (empty($entry) && ! empty($ref_2_field) && isset($_POST['item_meta'][ $ref_2_field ])) {
            // for reverse compatibility for custom code
            $ref_2 = sanitize_text_field($_POST['item_meta'][ $ref_2_field ]);
        } elseif (! empty($ref_2_field) && isset($entry->metas[ $ref_2_field ])) {
            $ref_2 = $entry->metas[ $ref_2_field ];
        } elseif (isset($settings['reference_2'])) {
            $ref_2 = $settings['reference_2'];
        }

        return trim($ref_2);
    }

    public static function get_is_sandbox($form, $settings, $entry = array())
    {
        $is_sandbox_field = isset($settings['is_sandbox_field']) ? $settings['is_sandbox_field'] : '';
        $is_sandbox = 'production';
        if (empty($entry) && ! empty($is_sandbox_field) && isset($_POST['item_meta'][ $is_sandbox_field ])) {
            // for reverse compatibility for custom code
            $is_sandbox = sanitize_text_field($_POST['item_meta'][ $is_sandbox_field ]);
        } elseif (! empty($is_sandbox_field) && isset($entry->metas[ $is_sandbox_field ])) {
            $is_sandbox = $entry->metas[ $is_sandbox_field ];
        } elseif (isset($settings['is_sandbox'])) {
            $is_sandbox = $settings['is_sandbox'];
        }

        return trim($is_sandbox);
    }

    private static function check_global_fallbacks( &$settings ) {
        $globals = array( 'is_sandbox', 'api_key', 'collection_id', 'x_signature', 'bill_description', 'return_url', 'cancel_url' );
        foreach ( $globals as $name ) {
            $settings[ $name ] = FrmBillplzPaymentsHelper::get_action_setting( $name, array( 'settings' => $settings ) );
        }
    }

    public static function stop_registration_email( $send_it, $form, $entry_id ) {
        if ( ! is_callable( 'FrmRegAppController::send_paid_user_notification' ) ) {
            // don't stop the registration email unless the function exists to send it later
            return $send_it;
        }
        
        if ( ! isset( $_POST['payment_completed'] ) || empty( $_POST['payment_completed'] ) ) {
            // stop the email if payment is not completed
            $send_it = false;
        }
        
        return $send_it;
    }

    public static function stop_the_email($emails, $values, $form_id, $args = array()) {
        if ( isset( $_POST['payment_completed'] ) && absint( $_POST['payment_completed'] ) ) {
            // always send the email if the payment was just completed
            return $emails;
        }

        $action = FrmAppHelper::get_post_param( 'action', '', 'sanitize_title' );
        $frm_action = FrmAppHelper::get_post_param( 'frm_action', '', 'sanitize_title' );
        if ( isset($args['entry']) && $action == 'frm_entries_send_email' ) {
            // if resending, make sure the payment is complete first
            global $wpdb;
            $complete = FrmDb::get_var( $wpdb->prefix .'frm_payments', array( 'item_id' => $args['entry']->id, 'completed' => 1 ), 'completed' );

        } else {
            // send the email when resending the email, and we don't know if the payment is complete
            $complete = ( ! isset( $args['entry'] ) && ( $frm_action == 'send_email' || $action == 'frm_entries_send_email' ) );
        }
            
        //do not send if payment is not complete
        if ( ! $complete ) {
            $emails = array();
        }
        
        return $emails;
    }

    public static function redirect_url($url, $form, $args = array( ))
    {
        if (isset($args['bill_url'])) {
            //only change it if it came from this plugin
            $url = $args['bill_url'];
        }

        return $url;
    }

    //Trigger the email to send after a payment is completed:
    public static function send_email_now($vars, $entry)
    {
        if (!isset($vars['completed']) || !$vars['completed']) {
            //only send the email if payment is completed
            return;
        }
        
        $_POST['payment_completed'] = true; //to let the other function know to send the email
        if (is_callable('FrmFormActionsController::trigger_actions')) {
            // 2.0
            FrmFormActionsController::trigger_actions('create', $entry->form_id, $entry->id, 'email');
        } else {
            // < 2.0
            FrmProNotification::entry_created($entry->id, $entry->form_id);
        }

        // trigger registration email
        if (is_callable('FrmRegNotification::send_paid_user_notification')) {
            FrmRegNotification::send_paid_user_notification($entry);
        } elseif (is_callable('FrmRegAppController::send_paid_user_notification')) {
            FrmRegAppController::send_paid_user_notification($entry);
        }
    }

    public static function billplz_webhook()
    {
        $bill_id = isset($_GET['billplz']['id']) ? $_GET['billplz']['id'] : $_POST['id'];
        if (empty($bill_id)) {
            FrmBillplzPaymentsHelper::log_message('Webhook called without parameter');
            wp_die();
        }
        $frm_payment = new FrmBillplzPayment();
        $payment_row = $frm_payment->get_payment_by_bill_id($bill_id);

        $wp_post = FrmBillplzPaymentAction::get_payment_action($payment_row->action_id);
        $post_meta = $wp_post->post_content;
        try {
            $data = FrmBillplzPaymentsBillplzWPConnectHelper::getXSignature($post_meta['x_signature']);
            FrmBillplzPaymentsHelper::log_message(print_r($data, true));
        } catch (Exception $e) {
            FrmBillplzPaymentsHelper::log_message($e->getMessage());
            wp_die($e->getMessage());
        }

        $entry = FrmEntry::getOne($payment_row->item_id);
        if (! $entry) {
            $action = $data['type'];
            FrmBillplzPaymentsHelper::log_message("The {$action} does not match an existing entry.");
            wp_die();
        }

        // Custom made mechanism to prevent race-condition
        $frm_multithread = new FrmBillplzPaymentMultithread();

        if ($data['paid'] && ! $payment_row->completed && $frm_multithread->create($data['id'])) {
            $frm_payment->update($payment_row->id, [
                'receipt_id' => $data['id'],
                'meta_value' => maybe_serialize($data),
                'amount' => $payment_row->amount,
                'completed' => 1,
                'begin_date' => $payment_row->begin_date,
            ]);

            FrmBillplzPaymentsHelper::log_message('Payment successfully updated for bill '.$data['id']. ' type: '. $data['type']);

            $pay_vars = array(
                'action_id' => $payment_row->action_id,
                'item_id' => $payment_row->item_id,
                'completed' => true
            );

            
            self::actions_after_callback($pay_vars, $entry);

            if (FrmBillplzPaymentsHelper::stop_email_set($entry->form_id)) {
              self::send_email_now($pay_vars, $entry);
            }
            $frm_multithread->delete($data['id']);
        }

        if ($data['type'] === 'redirect') {
            if ($data['paid']) {
                $redirect_url = self::process_shortcodes(array(
                    'value' => $post_meta['return_url'],
                    'form'  => $wp_post->menu_order,
                    'entry' => $entry,
                ));
            } else {
                $redirect_url = self::process_shortcodes(array(
                    'value' => $post_meta['cancel'],
                    'form'  => $wp_post->menu_order,
                    'entry' => $entry,
                ));
            }

            header('Location: '. $redirect_url);
        }
        exit;
    }

    private static function actions_after_callback($pay_vars, $entry)
    {
        $trigger_actions = is_callable('FrmFormActionsController::trigger_actions');
        if (! $trigger_actions) {
            return;
        }

        $form_action = false;
        if (isset($pay_vars['action_id']) && $pay_vars['action_id']) {
            $form_action = FrmBillplzPaymentAction::get_payment_action($pay_vars['action_id']);
        }

        self::set_fields_after_payment($form_action, $pay_vars, $entry);

        $trigger_event = ( $pay_vars['completed'] ) ? 'billplz' : 'billplz-failed';
        FrmFormActionsController::trigger_actions($trigger_event, $entry->form_id, $entry->id);
    }

    public static function set_fields_after_payment($form_action, $pay_vars, $entry = array())
    {
        if (! is_callable('FrmProEntryMeta::update_single_field') || empty($form_action) || empty($form_action->post_content['change_field'])) {
            return;
        }

        foreach ($form_action->post_content['change_field'] as $change_field) {
            $completed = ( $change_field['status'] == 'complete' && $pay_vars['completed'] );
            $failed = ( $change_field['status'] == 'failed' && ! $pay_vars['completed'] );
            if ($completed || $failed) {
                $value = self::process_shortcodes(array(
                    'value' => $change_field['value'],
                    'form'  => $form_action->menu_order,
                    'entry' => $entry,
                ));

                FrmProEntryMeta::update_single_field(array(
                    'entry_id' => $pay_vars['item_id'],
                    'field_id' => $change_field['id'],
                    'value'    => $value,
                ));
            }
        }
    }

    private static function process_shortcodes($atts)
    {
        $value = $atts['value'];

        // Check if there are any shortcodes
        if ( strpos( $value, '[' ) !== false ) {
            // For pro version
            if ( is_callable( 'FrmProFieldsHelper::replace_non_standard_formidable_shortcodes' ) ) {
                FrmProFieldsHelper::replace_non_standard_formidable_shortcodes(array(), $value);

                if (isset($atts['entry']) && ! empty($atts['entry'])) {
                    $value = apply_filters('frm_content', $value, $atts['form'], $atts['entry']);
                }

                return do_shortcode($value);
            }

            // For free version
            if ( is_callable( 'FrmFieldsHelper::basic_replace_shortcodes' ) ) {
                return FrmFieldsHelper::basic_replace_shortcodes( $value, $atts['form'], $atts['entry'] );
            }
        }

        return $value;

    }

    public static function update_options($options, $values)
    {
        $defaults = FrmBillplzPaymentsHelper::get_default_options();
        
        foreach ($defaults as $opt => $default) {
            $options[ $opt ] = isset($values['options'][ $opt ]) ? $values['options'][ $opt ] : $default;
            unset($default, $opt);
        }

        return $options;
    }

    public static function sidebar_list($entry)
    {
        global $wpdb;
        
        $payments = $wpdb->get_results($wpdb->prepare("SELECT id,begin_date,amount,completed FROM {$wpdb->prefix}frm_payments WHERE item_id=%d ORDER BY created_at DESC", $entry->id));
        
        if (!$payments) {
            return;
        }
        
        $date_format = get_option('date_format');
        $currencies = FrmBillplzPaymentsHelper::get_currencies();
        
        include(FRM_BILLPLZ_PATH . 'views/payments/sidebar_list.php');
    }

    public static function entry_columns($columns)
    {
        if (is_callable('FrmForm::get_current_form_id')) {
            $form_id = FrmForm::get_current_form_id();
        } else {
            $form_id = FrmEntriesHelper::get_current_form_id();
        }

        if ($form_id) {
            $columns[ $form_id . '_payments' ] = __('Payments', 'frmbz');
            $columns[ $form_id . '_current_payment' ] = __('Paid', 'frmbz');
            $columns[ $form_id . '_payment_expiration' ] = __('Expiration', 'frmbz');
        }

        return $columns;
    }


    public static function entry_payment_column($value, $atts)
    {
        $value = '';

        $payments = FrmBillplzPaymentEntry::get_completed_payments($atts['item']);
        foreach ($payments as $payment) {
            $value .= '<a href="' . esc_url(admin_url('admin.php?page=formidable-payments&action=show&id=' . $payment->id)) . '">' . FrmBillplzPaymentsHelper::formatted_amount($payment) . '</a><br/>';
            unset($payment);
        }
        return $value;
    }

    public static function entry_current_payment_column($value, $atts)
    {
        $payments = FrmBillplzPaymentEntry::get_completed_payments($atts['item']);
        $is_current = ! empty($payments) && ! FrmBillplzPaymentEntry::is_expired($atts['item']);
        $value = $is_current ? __('Paid', 'frmbz') : __('Not Paid', 'frmbz');
        return $value;
    }

    public static function entry_payment_expiration_column($value, $atts)
    {
        $expiration = FrmBillplzPaymentEntry::get_entry_expiration($atts['item']);
        return $expiration ? $expiration : '';
    }

    public static function add_payment_to_csv($headings, $form_id)
    {
        if (FrmBillplzPaymentAction::form_has_payment_action($form_id)) {
            $headings['billplz'] = __('Payments', 'frmbz');
            $headings['billplz_expiration'] = __('Expiration Date', 'frmbz');
            $headings['billplz_complete'] = __('Paid', 'frmbz');
            add_filter('frm_csv_row', 'FrmBillplzPaymentsController::add_payment_to_csv_row', 20, 2);
        }
        return $headings;
    }

    public static function add_payment_to_csv_row($row, $atts)
    {
        $row['billplz'] = 0;
        $atts['item'] = $atts['entry'];
        $row['billplz_expiration'] = self::entry_payment_expiration_column('', $atts);

        $payments = FrmBillplzPaymentEntry::get_completed_payments($atts['entry']);
        foreach ($payments as $payment) {
            $row['billplz'] += $payment->amount;
        }

        $row['billplz_complete'] = ! empty($payments) && ! FrmBillplzPaymentEntry::is_expired($atts['entry']);

        return $row;
    }


    private static function new_payment()
    {
        self::get_new_vars();
    }

    private static function create()
    {
        $message = $error = '';

        $frm_payment = new FrmBillplzPayment();
        if ($id = $frm_payment->create($_POST)) {
            $message = __('Payment was Successfully Created', 'frmbz');
            self::get_edit_vars($id, '', $message);
        } else {
            $error = __('There was a problem creating that payment', 'frmbz');
            return self::get_new_vars($error);
        }
    }

    private static function edit()
    {
        $id = FrmAppHelper::get_param('id');
        return self::get_edit_vars($id);
    }

    private static function update()
    {
        $frm_payment = new FrmBillplzPayment();
        $id = FrmAppHelper::get_param('id');
        $message = $error = '';
        if ($frm_payment->update($id, $_POST)) {
            $message = __('Payment was Successfully Updated', 'frmbz');
        } else {
            $error = __('There was a problem updating that payment', 'frmbz');
        }
        return self::get_edit_vars($id, $error, $message);
    }

    private static function destroy()
    {
        if (!current_user_can('administrator')) {
            $frm_settings = FrmBillplzPaymentsHelper::get_settings();
            wp_die($frm_settings->admin_permission);
        }

        $frm_payment = new FrmBillplzPayment();
        $message = '';
        if ($frm_payment->destroy(FrmAppHelper::get_param('id'))) {
            $message = __('Payment was Successfully Deleted', 'frmbz');
        }
            
        self::display_list($message);
    }

    private static function bulk_actions($action)
    {
        $errors = array();
        $message = '';
        $bulkaction = str_replace('bulk_', '', $action);

        $items = FrmAppHelper::get_param('item-action', '');
        if (empty($items)) {
            $errors[] = __('No payments were selected', 'frmbz');
        } else {
            if (!is_array($items)) {
                $items = explode(',', $items);
            }
                
            if ($bulkaction == 'delete') {
                if (!current_user_can('frm_delete_entries')) {
                    $frm_settings = FrmBillplzPaymentsHelper::get_settings();
                    $errors[] = $frm_settings->admin_permission;
                } else {
                    if (is_array($items)) {
                        $frm_payment = new FrmBillplzPayment();
                        foreach ($items as $item_id) {
                            if ($frm_payment->destroy($item_id)) {
                                $message = __('Payments were Successfully Deleted', 'frmbz');
                            }
                        }
                    }
                }
            }
        }
        self::display_list($message, $errors);
    }

    private static function get_new_vars($error = '')
    {
        global $wpdb;
        
        $defaults = array('completed' => 0, 'item_id' => '', 'receipt_id' => '', 'amount' => '', 'begin_date' => date('Y-m-d'), 'paysys' => 'manual');
        $payment = array();
        foreach ($defaults as $var => $default) {
            $payment[$var] = FrmAppHelper::get_param($var, $default);
        }
        
        $frm_payment_settings = new FrmBillplzPaymentSettings();
        $currency = FrmBillplzPaymentsHelper::get_currencies($frm_payment_settings->settings->currency);

        require(FRM_BILLPLZ_PATH . 'views/payments/new.php');
    }

    private static function get_edit_vars($id, $errors = '', $message = '')
    {
        if (! $id) {
            die(__('Please select a payment to view', 'frmbz'));
        }
            
        if (!current_user_can('frm_edit_entries')) {
            return self::show($id);
        }
            
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare("SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id), ARRAY_A);

        $frm_payment_settings = new FrmBillplzPaymentSettings();
        $currency = FrmBillplzPaymentsHelper::get_action_setting('currency', array( 'payment' => $payment ));
        $currency = FrmBillplzPaymentsHelper::get_currencies($currency);

        if (isset($_POST) and isset($_POST['receipt_id'])) {
            foreach ($payment as $var => $val) {
                if ($var == 'id') {
                    continue;
                }
                $payment[$var] = FrmAppHelper::get_param($var, $val);
            }
        }
        
        require(FRM_BILLPLZ_PATH . 'views/payments/edit.php');
    }

    public static function route()
    {
        $action = isset($_REQUEST['frm_action']) ? 'frm_action' : 'action';
        $action = FrmAppHelper::get_param($action, '', 'get', 'sanitize_title');

        if ($action == 'show') {
            return self::show(FrmAppHelper::get_param('id', false, 'get', 'sanitize_text_field'));
        } elseif ($action == 'new') {
            return self::new_payment();
        } elseif ($action == 'create') {
            return self::create();
        } elseif ($action == 'edit') {
            return self::edit();
        } elseif ($action == 'update') {
            return self::update();
        } elseif ($action == 'destroy') {
            return self::destroy();
        } else {
            $action = FrmAppHelper::get_param('action', '', 'get', 'sanitize_text_field');
            if ($action == -1) {
                $action = FrmAppHelper::get_param('action2', '', 'get', 'sanitize_text_field');
            }
            
            if (strpos($action, 'bulk_') === 0) {
                if ($_GET && $action) {
                    $_SERVER['REQUEST_URI'] = str_replace('&action=' . $action, '', $_SERVER['REQUEST_URI']);
                }

                return self::bulk_actions($action);
            } else {
                return self::display_list();
            }
        }
    }
}
