<?php

class FrmBillplzPaymentsController {

    public static $min_version = '2.0';
    public static $db_version = 2;
    public static $db_opt_name = 'frm_pay_db_version';

    public static function load_hooks() {
        //add_action('plugins_loaded', 'FrmBillplzPaymentsController::load_lang');
        register_activation_hook(dirname(dirname(__FILE__)) . '/formidable-billplz.php', 'FrmBillplzPaymentsController::install');

        if (is_admin()) {
            // Need to use priority less than 27 because it should not be same with PayPal
            add_action('admin_menu', 'FrmBillplzPaymentsController::menu', 25);
            add_filter('frm_nav_array', 'FrmBillplzPaymentsController::frm_nav', 30);
            add_filter('plugin_action_links_formidable-billplz/formidable-billplz.php', 'FrmBillplzPaymentsController::settings_link', 10, 2);
            add_action('after_plugin_row_formidable-billplz/formidable-billplz.php', 'FrmBillplzPaymentsController::min_version_notice');
            add_action('admin_notices', 'FrmBillplzPaymentsController::get_started_headline');
            add_action('wp_ajax_frmpay_install', 'FrmBillplzPaymentsController::install');
            add_filter('set-screen-option', 'FrmBillplzPaymentsController::save_per_page', 10, 3);
            add_action('wp_ajax_frm_payments_billplz_ipn', 'FrmBillplzPaymentsController::billplz_ipn');
            add_action('wp_ajax_nopriv_frm_payments_billplz_ipn', 'FrmBillplzPaymentsController::billplz_ipn');

            add_filter('frm_form_options_before_update', 'FrmBillplzPaymentsController::update_options', 15, 2);
            add_action('frm_show_entry_sidebar', 'FrmBillplzPaymentsController::sidebar_list');
        }

        // 2.0 hook
        add_action('frm_trigger_billplz_create_action', 'FrmBillplzPaymentsController::create_payment_trigger', 10, 3);

        // < 2.0 hook
        add_action('frm_after_create_entry', 'FrmBillplzPaymentsController::pre_v2_maybe_redirect', 30, 2);

        add_filter('frm_csv_columns', 'FrmBillplzPaymentsController::add_payment_to_csv', 20, 2);
    }

    public static function path() {
        return dirname(dirname(__FILE__));
    }

    public static function load_lang() {
        //load_plugin_textdomain('frmbz', false, 'formidable-billplz/languages/');
    }

    public static function menu() {
        $frm_settings = FrmBillplzPaymentsHelper::get_settings();
        $menu = $frm_settings ? $frm_settings->menu : 'Formidable';
        remove_action('admin_menu', 'FrmPaymentsController::menu', 26);
        add_submenu_page('formidable', $menu . ' | Billplz', 'Billplz', 'frm_view_entries', 'formidable-payments', 'FrmBillplzPaymentsController::route');

        add_filter('manage_' . sanitize_title($menu) . '_page_formidable-payments_columns', 'FrmBillplzPaymentsController::payment_columns');
        add_filter('manage_' . sanitize_title($menu) . '_page_formidable-entries_columns', 'FrmBillplzPaymentsController::entry_columns', 20);
        add_filter('frm_entries_payments_column', 'FrmBillplzPaymentsController::entry_payment_column', 10, 2);
        add_filter('frm_entries_current_payment_column', 'FrmBillplzPaymentsController::entry_current_payment_column', 10, 2);
        add_filter('frm_entries_payment_expiration_column', 'FrmBillplzPaymentsController::entry_payment_expiration_column', 10, 2);
    }

    public static function frm_nav($nav) {
        if (current_user_can('frm_view_entries')) {
            $nav['formidable-payments'] = 'Billplz';
        }

        return $nav;
    }

    public static function payment_columns($cols = array()) {
        add_screen_option('per_page', array(
            'label' => __('Payments', 'frmbz'), 'default' => 20,
            'option' => 'formidable_page_formidable_payments_per_page',
        ));

        return array(
            'cb' => '<input type="checkbox" />',
            'receipt_id' => __('Receipt ID', 'frmbz'),
            'user_id' => __('User', 'frmbz'),
            'item_id' => __('Entry', 'frmbz'),
            'form_id' => __('Form', 'frmbz'),
            'completed' => __('Completed', 'frmbz'),
            'amount' => __('Amount', 'frmbz'),
            'created_at' => __('Date', 'frmbz'),
            'begin_date' => __('Begin Date', 'frmbz'),
            'expire_date' => __('Expire Date', 'frmbz'),
            'paysys' => __('Processor', 'frmbz'),
        );
    }

    // Adds a settings link to the plugins page
    public static function settings_link($links, $file) {
        $settings = '<a href="' . esc_url(admin_url('admin.php?page=formidable-settings')) . '">' . __('Settings', 'frmbz') . '</a>';
        array_unshift($links, $settings);

        return $links;
    }

    public static function min_version_notice() {
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

    public static function get_started_headline() {
        // Don't display this error as we're upgrading
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        if ($action == 'upgrade-plugin' && !isset($_GET['activate'])) {
            return;
        }

        $db_version = get_option(self::$db_opt_name);
        if ((int) $db_version < self::$db_version) {
            if (is_callable('FrmAppHelper::plugin_url')) {
                $url = FrmAppHelper::plugin_url();
            } else if (defined('FRM_URL')) {
                $url = FRM_URL;
            } else {
                return;
            }
            ?>
            <div class="error" id="frmpay_install_message" style="padding:7px;"><?php _e('Your Formidable Payments database needs to be updated.<br/>Please deactivate and reactivate the plugin to fix this or', 'frmbz'); ?> <a id="frmpay_install_link" href="javascript:frmpay_install_now()"><?php _e('Update Now', 'frmbz') ?></a></div>
            <script type="text/javascript">
                function frmpay_install_now() {
                    jQuery('#frmpay_install_link').replaceWith('<img src="<?php echo esc_url_raw($url) ?>/images/wpspin_light.gif" alt="<?php esc_attr_e('Loading&hellip;'); ?>" />');
                    jQuery.ajax({type: 'POST', url: "<?php echo esc_url_raw(admin_url('admin-ajax.php')) ?>", data: 'action=frmpay_install',
                        success: function (msg) {
                            jQuery("#frmpay_install_message").fadeOut('slow');
                        }
                    });
                }
                ;
            </script>
            <?php
        }
    }

    public static function install($old_db_version = false) {
        $frm_payment_db = new FrmBillplzDb();
        $frm_payment_db->upgrade($old_db_version);
        $frm_payment_db->billplz();
    }

    private static function show($id) {
        if (!$id) {
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

        include( self::path() . '/views/payments/show.php' );
    }

    private static function display_list($message = '', $errors = array()) {
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

        include( self::path() . '/views/payments/list.php' );
    }

    public static function save_per_page($save, $option, $value) {
        if ($option == 'formidable_page_formidable_payments_per_page') {
            $save = absint($value);
        }
        return $save;
    }

    public static function create_payment_trigger($action, $entry, $form) {
        if (!isset($action->v2)) {
            // 2.0 fallback - prevent extra processing
            remove_action('frm_after_create_entry', 'FrmBillplzPaymentsController::pre_v2_maybe_redirect', 30);
        }

        return self::maybe_redirect_for_payment($action->post_content, $entry, $form);
    }

    public static function pre_v2_maybe_redirect($entry_id, $form_id) {
        if (!$_POST || !isset($_POST['frm_payment']) || ( is_admin() && !defined('DOING_AJAX') )) {
            return;
        }

        $form = FrmForm::getOne($form_id);

        // make sure Billplz is enabled
        if (!isset($form->options['billplz']) || !$form->options['billplz']) {
            return;
        }

        _deprecated_function(__FUNCTION__, '2.04', 'Please update your payment settings for this form');

        if (is_callable('FrmProEntriesHelper::get_field') && FrmProEntriesHelper::get_field('is_draft', $entry_id)) {
            // don't send to Billplz if this is a draft
            return;
        }

        //check conditions
        $redirect = true;
        if (isset($form->options['billplz_list']['hide_field']) && is_array($form->options['billplz_list']['hide_field']) && class_exists('FrmProFieldsHelper')) {
            foreach ($form->options['billplz_list']['hide_field'] as $hide_key => $hide_field) {
                $observed_value = ( isset($_POST['item_meta'][$hide_field]) ) ? sanitize_text_field($_POST['item_meta'][$hide_field]) : '';

                if (is_callable('FrmFieldsHelper::value_meets_condition')) {
                    // 2.0+
                    $class_name = 'FrmFieldsHelper';
                } else {
                    // < 2.0 falback
                    $class_name = 'FrmProFieldsHelper';
                }
                $redirect = call_user_func_array(array($class_name, 'value_meets_condition'), array($observed_value, $form->options['billplz_list']['hide_field_cond'][$hide_key], $form->options['billplz_list']['hide_opt'][$hide_key]));
                if (!$redirect) {
                    break;
                }
            }
        }

        if (!$redirect) {
            // don't pay if conditional logic is not met
            return;
        }

        // turn into an object to match with 2.0
        $entry = new stdClass();
        $entry->id = $entry_id;

        return self::maybe_redirect_for_payment($form->options, $entry, $form);
    }

    public static function maybe_redirect_for_payment($settings, $entry, $form) {
        if (is_admin() && !defined('DOING_AJAX')) {
            // don't send to Billplz if submitting from the back-end
            return;
        }

        $amount = self::get_amount($form, $settings, $entry);
        if (empty($amount)) {
            return;
        }

        // stop the emails if payment is required
        if (FrmBillplzPaymentsHelper::stop_email_set($form, $settings)) {
            remove_action('frm_trigger_email_action', 'FrmNotification::trigger_email', 10, 3);
            add_filter('frm_to_email', 'FrmBillplzPaymentsController::stop_the_email', 20, 4);
            add_filter('frm_send_new_user_notification', 'FrmBillplzPaymentsController::stop_registration_email', 10, 3);
        }

        // save in global for use building redirect url
        global $frm_pay_form_settings;
        $frm_pay_form_settings = $settings;

        // trigger payment redirect after other functions have a chance to complete
        add_action('frm_after_create_entry', 'FrmBillplzPaymentsController::redirect_for_payment', 50, 2);

        return true;
    }

    public static function redirect_for_payment($entry_id, $form_id) {
        global $frm_pay_form_settings;
        $form = FrmForm::getOne($form_id);

        $atts = array(
            'action_settings' => $frm_pay_form_settings,
            'form' => $form,
            'entry_id' => $entry_id,
        );

        $entry = FrmEntry::getOne($entry_id, true);
        $atts['amount'] = self::get_amount($atts['form'], $atts['action_settings'], $entry);
        $atts['name'] = self::get_name($atts['form'], $atts['action_settings'], $entry);
        $atts['email'] = self::get_email($atts['form'], $atts['action_settings'], $entry);
        $atts['mobile'] = self::get_mobile($atts['form'], $atts['action_settings'], $entry);

        if (empty($atts['amount'])) {
            return;
        }

        $md5hash = substr(md5(strtolower($atts['name'] . $atts['email'] .
                                $atts['mobile'] . number_format($atts['amount']) .
                                $atts['entry_id'] . $atts['action_settings']['action_id'])), 1, 5);

        global $wpdb;

        // Check if the bills have already created or not
        $sql = "SELECT * FROM `" . $wpdb->prefix . "frm_billplz` WHERE item_id='" . $atts['entry_id'] . "'";
        $billplz_table = $wpdb->get_results($sql);

        foreach ($billplz_table as $value) {
            if ($md5hash === $value->hash) {
                $billplz_url = $value->url;
            }
        }

        // Bills is not create yet. Create Bills
        if (!isset($billplz_url)) {
            $billplz_data = self::get_billplz_data($atts);
            $billplz_url = $billplz_data['url'];
            $array_billplz_db = [
                'form_id' => $atts['form']->id,
                'item_id' => $atts['entry_id'],
                'action_id' => $atts['action_settings']['action_id'],
                'bill_id' => $billplz_data['id'],
                'url' => $billplz_url,
                'hash' => $md5hash,
                'api_key' => FrmBillplzPaymentsHelper::get_action_setting('api_key', ['settings' => $atts['action_settings']]),
                'mode' => FrmBillplzPaymentsHelper::get_action_setting('billplz_mode', ['settings' => $atts['action_settings']]),
            ];
            $wpdb->insert($wpdb->prefix . 'frm_billplz', $array_billplz_db);
        }

        if (empty($billplz_url)) {
            return;
        }

        if (is_callable('FrmAppHelper::plugin_version')) {
            $frm_version = FrmAppHelper::plugin_version();
        } else {
            global $frm_version; //global fallback
        }

        add_filter('frm_redirect_url', 'FrmBillplzPaymentsController::redirect_url', 9, 3);
        // Array 'billplz_url' => 'http://billplz.com' is important, because to differentiate the source call
        $conf_args = array('billplz_url' => $billplz_url);
        if (defined('DOING_AJAX') && isset($form->options['ajax_submit']) && $form->options['ajax_submit'] && $_POST && isset($_POST['action']) && in_array($_POST['action'], array('frm_entries_create', 'frm_entries_update'))) {
            $conf_args['ajax'] = true;
        }

        if (is_callable('FrmProEntriesController::confirmation')) {
            FrmProEntriesController::confirmation('redirect', $form, $form->options, $entry_id, $conf_args);
        } else {
            $conf_args['id'] = $entry_id;
            self::confirmation($form, $conf_args);
        }
    }

    /**
     * Redirect to Billplz when the pro version isn't installed
     */
    public static function confirmation($form, $args) {
        global $frm_vars;

        add_filter('frm_use_wpautop', '__return_false');

        $success_url = apply_filters('frm_redirect_url', '', $form, $args);
        if (!headers_sent()) {
            wp_redirect(esc_url_raw($success_url));
            die();
        } else {
            add_filter('frm_use_wpautop', '__return_true');

            $success_msg = isset($form->options['success_msg']) ? $form->options['success_msg'] : __('Please wait while you are redirected.', 'formidable');

            $redirect_msg = '<div class="' . esc_attr(FrmFormsHelper::get_form_style_class($form)) . '"><div class="frm-redirect-msg frm_message">' . $success_msg . '<br/>' .
                    sprintf(__('%1$sClick here%2$s if you are not automatically redirected.', 'formidable'), '<a href="' . esc_url($success_url) . '">', '</a>') .
                    '</div></div>';

            $redirect_msg = apply_filters('frm_redirect_msg', $redirect_msg, array(
                'entry_id' => $args['id'], 'form_id' => $form->id, 'form' => $form
            ));

            echo $redirect_msg;
            echo "<script type='text/javascript'>jQuery(document).ready(function(){ setTimeout(window.location='" . esc_url_raw($success_url) . "', 8000); });</script>";
        }
    }

    public static function get_name($form, $settings = array(), $entry = array()) {
        if (empty($settings)) {
            // for reverse compatability
            $settings = $form->options;
        }
        $name_field = isset($settings['billplz_name_field']) ? $settings['billplz_name_field'] : '';
        $name = '';
        if (empty($entry) && !empty($name_field) && isset($_POST['item_meta'][$name_field])) {
            // for reverse compatibility for custom code
            $name = sanitize_text_field($_POST['item_meta'][$name_field]);
        } else if (!empty($name_field) && isset($entry->metas[$name_field])) {
            $name = $entry->metas[$name_field];
        }

        if (empty($name)) {
            // no name has been set
            return 'John Doe';
        }
        return $name;
    }

    public static function get_email($form, $settings = array(), $entry = array()) {
        if (empty($settings)) {
            // for reverse compatability
            $settings = $form->options;
        }
        $email_field = isset($settings['billplz_email_field']) ? $settings['billplz_email_field'] : '';
        $email = '';
        if (empty($entry) && !empty($email_field) && isset($_POST['item_meta'][$email_field])) {
            // for reverse compatibility for custom code
            $email = sanitize_text_field($_POST['item_meta'][$email_field]);
        } else if (!empty($email_field) && isset($entry->metas[$email_field])) {
            $email = $entry->metas[$email_field];
        }

        if (empty($email)) {
            // no email has been set
            return 'fakeemail@yahoo.com';
        }
        return $email;
    }

    public static function get_mobile($form, $settings = array(), $entry = array()) {
        if (empty($settings)) {
            // for reverse compatability
            $settings = $form->options;
        }
        $mobile_field = isset($settings['billplz_mobile_field']) ? $settings['billplz_mobile_field'] : '';
        $mobile = '';
        if (empty($entry) && !empty($email_field) && isset($_POST['item_meta'][$email_field])) {
            // for reverse compatibility for custom code
            $mobile = sanitize_text_field($_POST['item_meta'][$email_field]);
        } else if (!empty($email_field) && isset($entry->metas[$email_field])) {
            $mobile = $entry->metas[$email_field];
        }

        if (empty($mobile)) {
            // no name has been set
            return '';
        }
        return $mobile;
    }

    public static function get_amount($form, $settings = array(), $entry = array()) {
        if (empty($settings)) {
            // for reverse compatability
            $settings = $form->options;
        }
        $amount_field = isset($settings['billplz_amount_field']) ? $settings['billplz_amount_field'] : '';
        $amount = 0;
        if (empty($entry) && !empty($amount_field) && isset($_POST['item_meta'][$amount_field])) {
            // for reverse compatibility for custom code
            $amount = sanitize_text_field($_POST['item_meta'][$amount_field]);
        } else if (!empty($amount_field) && isset($entry->metas[$amount_field])) {
            $amount = $entry->metas[$amount_field];
        } else if (isset($settings['billplz_amount'])) {
            $amount = $settings['billplz_amount'];
        }

        if (empty($amount)) {
            // no amount has been set
            return 0;
        }

        $frm_payment_settings = new FrmBillplzSettings();
        $currency = isset($settings['currency']) ? $settings['currency'] : $frm_payment_settings->settings->currency;
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

    private static function maybe_use_decimal($amount, $currencies) {
        if ($currencies['thousand_separator'] == '.') {
            $amount_parts = explode('.', $amount);
            $used_for_decimal = ( count($amount_parts) == 2 && strlen($amount_parts[1]) == 2 );
            if ($used_for_decimal) {
                $amount = str_replace('.', $currencies['decimal_separator'], $amount);
            }
        }
        return $amount;
    }

    public static function get_billplz_data($atts) {

        require_once __DIR__ . '/billplz.php';

        $api_key = FrmBillplzPaymentsHelper::get_action_setting('api_key', ['settings' => $atts['action_settings']]);
        $collection_id = FrmBillplzPaymentsHelper::get_action_setting('collection_id', ['settings' => $atts['action_settings']]);
        $description = FrmBillplzPaymentsHelper::get_action_setting('billplz_item_name', ['settings' => $atts['action_settings']]);
        $mode = FrmBillplzPaymentsHelper::get_action_setting('billplz_mode', ['settings' => $atts['action_settings']]);
        //echo '<pre>' . print_r($description, true) . '</pre>';
        //exit;
        $return_url = admin_url('admin-ajax.php?action=frm_payments_billplz_ipn');
        $callback_url = admin_url('admin-ajax.php?action=frm_payments_billplz_ipn');

        $obj = new billplz();
        $obj
                ->setCollection($collection_id)
                ->setEmail($atts['email'])
                ->setName($atts['name'])
                ->setMobile($atts['mobile'])
                ->setAmount($atts['amount'])
                ->setDeliver(false)
                ->setReference_1($atts['entry_id'])
                ->setReference_1_Label('ID')
                ->setDescription($description)
                ->setPassbackURL($return_url, $callback_url)
                ->create_bill($api_key, $mode);

        self::create_invoice_for_payment($atts, $obj->getID());
        return [
            'url' => $obj->getURL(),
            'id' => $obj->getID(),
        ];
    }

    public static function create_invoice_for_payment($atts, $billplz_id) {

        $frm_payment = new FrmBillplz();
        $invoice = $frm_payment->create(array(
            'receipt_id' => $billplz_id,
            'item_id' => $atts['entry_id'],
            'amount' => $atts['amount'],
            'action_id' => isset($atts['action_settings']['action_id']) ? $atts['action_settings']['action_id'] : 0,
        ));

        return $invoice;
    }

    public static function redirect_url($url, $form, $args = array()) {
        if (isset($args['billplz_url'])) {
            //only change it if it came from this plugin
            $url = $args['billplz_url'];
        }

        return $url;
    }

    public static function stop_registration_email($send_it, $form, $entry_id) {
        if (!is_callable('FrmRegAppController::send_paid_user_notification')) {
            // don't stop the registration email unless the function exists to send it later
            return $send_it;
        }

        if (!isset($_POST['payment_completed']) || empty($_POST['payment_completed'])) {
            // stop the email if payment is not completed
            $send_it = false;
        }

        return $send_it;
    }

    public static function stop_the_email($emails, $values, $form_id, $args = array()) {
        if (isset($_POST['payment_completed']) && absint($_POST['payment_completed'])) {
            // always send the email if the payment was just completed
            return $emails;
        }

        $action = FrmAppHelper::get_post_param('action', '', 'sanitize_title');
        $frm_action = FrmAppHelper::get_post_param('frm_action', '', 'sanitize_title');
        if (isset($args['entry']) && $action == 'frm_entries_send_email') {
            // if resending, make sure the payment is complete first
            global $wpdb;
            $complete = FrmDb::get_var($wpdb->prefix . 'frm_payments', array('item_id' => $args['entry']->id, 'completed' => 1), 'completed');
        } else {
            // send the email when resending the email, and we don't know if the payment is complete
            $complete = (!isset($args['entry']) && ( $frm_action == 'send_email' || $action == 'frm_entries_send_email' ) );
        }

        //do not send if payment is not complete
        if (!$complete) {
            $emails = array();
        }

        return $emails;
    }

    //Trigger the email to send after a payment is completed:
    public static function send_email_now($vars, $payment, $entry) {
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
        } else if (is_callable('FrmRegAppController::send_paid_user_notification')) {
            FrmRegAppController::send_paid_user_notification($entry);
        }
    }

    public static function billplz_ipn() {
        $ipn_data = FrmBillplzPaymentsHelper::get_ipn_data();

        // Insert Response Type
        $ipn_data['data']['Response Type'] = ($ipn_data['signal'] === 'Return') ? 'Return' : 'Callback';

        $entry_id = $ipn_data['item_id'];

        $entry = FrmEntry::getOne($entry_id);
        if (!$entry) {
            FrmBillplzPaymentsHelper::log_message(__('The IPN does not match an existing entry.', 'frmbz'));
            wp_die();
        }

        global $wpdb;
        if ($ipn_data['status']) {

            $pay_vars = [
                'completed' => true,
                'meta_value' => maybe_serialize($ipn_data['data']),
                'status' => $ipn_data['data']['state'],
            ];

            $redirect_url = $ipn_data['return_url'];

            // Query table for frm_payments
            $item_action_db = $ipn_data['item_action_db'];

            if ($ipn_data['completed'] == '0') {
                // Update to payments table that the order has been paid
                $u = $wpdb->update($wpdb->prefix . 'frm_payments', $pay_vars, array('receipt_id' => $ipn_data['bill_id']));

                if (!$u) {
                    FrmBillplzPaymentsHelper::log_message(sprintf(__('Payment %d was complete, but failed to update.', 'frmpp'), $ipn_data['payment_id']));
                    wp_die();
                } else {
                    FrmBillplzPaymentsHelper::log_message(__('Payment successfully updated to PAID.', 'frmbz'));
                }

                if (FrmBillplzPaymentsHelper::stop_email_set($entry->form_id)) {
                    self::send_email_now($pay_vars, $item_action_db, $entry);
                }

                do_action('frm_payment_billplz_ipn', compact('pay_vars', 'item_action_db', 'entry'));

                self::actions_after_ipn($pay_vars, $entry);
            }
        } else {
            $pay_vars = [
                'completed' => false,
                'meta_value' => maybe_serialize($ipn_data['data']),
                'status' => $ipn_data['data']['state'],
            ];
            // Update to payments table that the order has been NOT PAID
            $wpdb->update($wpdb->prefix . 'frm_payments', $pay_vars, array('receipt_id' => $ipn_data['bill_id']));
            FrmBillplzPaymentsHelper::log_message(__('Payment successfully updated to NOT PAID.', 'frmbz'));

            $redirect_url = $ipn_data['cancel_url'];
        }

        if ($ipn_data['signal'] === 'Return') {
            wp_redirect($redirect_url);
        } else {
            echo 'HI';
        }

        wp_die();
    }

    private static function actions_after_ipn($pay_vars, $entry) {
        $trigger_actions = is_callable('FrmFormActionsController::trigger_actions');
        if (!$trigger_actions) {
            return;
        }

        $form_action = false;
        if (isset($pay_vars['action_id']) && $pay_vars['action_id']) {
            $form_action = FrmBillplzAction::get_payment_action($pay_vars['action_id']);
        }

        self::set_fields_after_payment($form_action, $pay_vars);

        $trigger_event = ( $pay_vars['completed'] ) ? 'billplz' : 'billplz-failed';
        FrmFormActionsController::trigger_actions($trigger_event, $entry->form_id, $entry->id);
    }

    public static function set_fields_after_payment($form_action, $pay_vars) {
        if (!is_callable('FrmProEntryMeta::update_single_field') || empty($form_action) || empty($form_action->post_content['change_field'])) {
            return;
        }

        foreach ($form_action->post_content['change_field'] as $change_field) {
            $completed = ( $change_field['status'] == 'complete' && $pay_vars['completed'] );
            $failed = ( $change_field['status'] == 'failed' && !$pay_vars['completed'] );
            if ($completed || $failed) {
                FrmProEntryMeta::update_single_field(array(
                    'entry_id' => $pay_vars['item_id'],
                    'field_id' => $change_field['id'],
                    'value' => $change_field['value'],
                ));
            }
        }
    }

    public static function hidden_payment_fields($form) {
        //_deprecated_function( __FUNCTION__, '2.04', 'Please update your payment settings for this form' );
        if (isset($form->options['billplz']) && $form->options['billplz']) {
            echo '<input type="hidden" name="frm_payment[item_name]" value="' . esc_attr($form->options['billplz_item_name']) . '"/>' . "\n";
        }
    }

    // Replace default option with modified one. Only if modified.
    public static function update_options($options, $values) {
        $defaults = FrmBillplzPaymentsHelper::get_default_options();

        foreach ($defaults as $opt => $default) {
            $options[$opt] = isset($values['options'][$opt]) ? $values['options'][$opt] : $default;
            unset($default, $opt);
        }

        return $options;
    }

    public static function sidebar_list($entry) {
        global $wpdb;

        $payments = $wpdb->get_results($wpdb->prepare("SELECT id,begin_date,amount,completed FROM {$wpdb->prefix}frm_payments WHERE item_id=%d ORDER BY created_at DESC", $entry->id));

        if (!$payments)
            return;

        $date_format = get_option('date_format');
        $currencies = FrmBillplzPaymentsHelper::get_currencies();

        include(self::path() . '/views/payments/sidebar_list.php');
    }

    public static function entry_columns($columns) {
        if (is_callable('FrmForm::get_current_form_id')) {
            $form_id = FrmForm::get_current_form_id();
        } else {
            $form_id = FrmEntriesHelper::get_current_form_id();
        }

        if ($form_id) {
            $columns[$form_id . '_payments'] = __('Payments', 'frmbz');
            $columns[$form_id . '_current_payment'] = __('Paid', 'frmbz');
            $columns[$form_id . '_payment_expiration'] = __('Expiration', 'frmbz');
        }

        return $columns;
    }

    public static function entry_payment_column($value, $atts) {
        $value = '';

        $payments = FrmBillplzEntry::get_completed_payments($atts['item']);
        foreach ($payments as $payment) {
            $value .= '<a href="' . esc_url(admin_url('admin.php?page=formidable-payments&action=show&id=' . $payment->id)) . '">' . FrmBillplzPaymentsHelper::formatted_amount($payment) . '</a><br/>';
            unset($payment);
        }
        return $value;
    }

    public static function entry_current_payment_column($value, $atts) {
        $payments = FrmBillplzEntry::get_completed_payments($atts['item']);
        $is_current = !empty($payments) && !FrmBillplzEntry::is_expired($atts['item']);
        $value = $is_current ? __('Paid', 'frmbz') : __('Not Paid', 'frmbz');
        return $value;
    }

    public static function entry_payment_expiration_column($value, $atts) {
        $expiration = FrmBillplzEntry::get_entry_expiration($atts['item']);
        return $expiration ? $expiration : '';
    }

    public static function add_payment_to_csv($headings, $form_id) {
        if (FrmBillplzAction::form_has_payment_action($form_id)) {
            $headings['billplz'] = __('Payments', 'frmbz');
            $headings['billplz_expiration'] = __('Expiration Date', 'frmbz');
            $headings['billplz_complete'] = __('Paid', 'frmbz');
            add_filter('frm_csv_row', 'FrmBillplzPaymentsController::add_payment_to_csv_row', 20, 2);
        }
        return $headings;
    }

    public static function add_payment_to_csv_row($row, $atts) {
        $row['billplz'] = 0;
        $atts['item'] = $atts['entry'];
        $row['billplz_expiration'] = self::entry_payment_expiration_column('', $atts);

        $payments = FrmBillplzEntry::get_completed_payments($atts['entry']);
        foreach ($payments as $payment) {
            $row['billplz'] += $payment->amount;
        }

        $row['billplz_complete'] = !empty($payments) && !FrmBillplzEntry::is_expired($atts['entry']);

        return $row;
    }

    private static function new_payment() {
        self::get_new_vars();
    }

    private static function create() {
        $message = $error = '';

        $frm_payment = new FrmBillplz();
        if ($id = $frm_payment->create($_POST)) {
            $message = __('Payment was Successfully Created', 'frmbz');
            self::get_edit_vars($id, '', $message);
        } else {
            $error = __('There was a problem creating that payment', 'frmbz');
            return self::get_new_vars($error);
        }
    }

    private static function edit() {
        $id = FrmAppHelper::get_param('id');
        return self::get_edit_vars($id);
    }

    private static function update() {
        $frm_payment = new FrmBillplz();
        $id = FrmAppHelper::get_param('id');
        $message = $error = '';
        if ($frm_payment->update($id, $_POST))
            $message = __('Payment was Successfully Updated', 'frmbz');
        else
            $error = __('There was a problem updating that payment', 'frmbz');
        return self::get_edit_vars($id, $error, $message);
    }

    private static function destroy() {
        if (!current_user_can('administrator')) {
            $frm_settings = FrmBillplzPaymentsHelper::get_settings();
            wp_die($frm_settings->admin_permission);
        }

        $frm_payment = new FrmBillplz();
        $message = '';
        if ($frm_payment->destroy(FrmAppHelper::get_param('id')))
            $message = __('Payment was Successfully Deleted', 'frmbz');

        self::display_list($message);
    }

    private static function bulk_actions($action) {
        $errors = array();
        $message = '';
        $bulkaction = str_replace('bulk_', '', $action);

        $items = FrmAppHelper::get_param('item-action', '');
        if (empty($items)) {
            $errors[] = __('No payments were selected', 'frmbz');
        } else {
            if (!is_array($items))
                $items = explode(',', $items);

            if ($bulkaction == 'delete') {
                if (!current_user_can('frm_delete_entries')) {
                    $frm_settings = FrmBillplzPaymentsHelper::get_settings();
                    $errors[] = $frm_settings->admin_permission;
                } else {
                    if (is_array($items)) {
                        $frm_payment = new FrmBillplz();
                        foreach ($items as $item_id) {
                            if ($frm_payment->destroy($item_id))
                                $message = __('Payments were Successfully Deleted', 'frmbz');
                        }
                    }
                }
            }
        }
        self::display_list($message, $errors);
    }

    private static function get_new_vars($error = '') {
        global $wpdb;

        $defaults = array('completed' => 0, 'item_id' => '', 'receipt_id' => '', 'amount' => '', 'begin_date' => date('Y-m-d'), 'paysys' => 'manual');
        $payment = array();
        foreach ($defaults as $var => $default)
            $payment[$var] = FrmAppHelper::get_param($var, $default);

        $frm_payment_settings = new FrmBillplzSettings();
        $currency = FrmBillplzPaymentsHelper::get_currencies($frm_payment_settings->settings->currency);

        require(self::path() . '/views/payments/new.php');
    }

    private static function get_edit_vars($id, $errors = '', $message = '') {
        if (!$id) {
            die(__('Please select a payment to view', 'frmbz'));
        }

        if (!current_user_can('frm_edit_entries'))
            return self::show($id);

        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare("SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id), ARRAY_A);

        $frm_payment_settings = new FrmBillplzSettings();
        $currency = FrmBillplzPaymentsHelper::get_action_setting('currency', array('payment' => $payment));
        $currency = FrmBillplzPaymentsHelper::get_currencies($currency);

        if (isset($_POST) and isset($_POST['receipt_id'])) {
            foreach ($payment as $var => $val) {
                if ($var == 'id')
                    continue;
                $payment[$var] = FrmAppHelper::get_param($var, $val);
            }
        }

        require(self::path() . '/views/payments/edit.php');
    }

    public static function route() {
        $action = isset($_REQUEST['frm_action']) ? 'frm_action' : 'action';
        $action = FrmAppHelper::get_param($action, '', 'get', 'sanitize_title');

        if ($action == 'show') {
            return self::show(FrmAppHelper::get_param('id', false, 'get', 'sanitize_text_field'));
        } else if ($action == 'new') {
            return self::new_payment();
        } else if ($action == 'create') {
            return self::create();
        } else if ($action == 'edit') {
            return self::edit();
        } else if ($action == 'update') {
            return self::update();
        } else if ($action == 'destroy') {
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
