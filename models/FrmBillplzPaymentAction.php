<?php

class FrmBillplzPaymentAction extends FrmFormAction
{

    function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        $action_ops = array(
            'classes'   => 'frm_billplz_icon',
            'active'    => true,
            'event'     => array('create'),
            'priority'  => 9, // trigger before emails are sent so they can be stopped
            'color'     => '#3784F4',
            'limit'     => 99,
        );
        
        $this->FrmFormAction('billplz', __('Billplz', 'frmbz'), $action_ops);
        add_action('wp_ajax_frmbz_after_pay', array( $this, 'add_new_pay_row' ));
    }

    public function admin_enqueue_scripts()
    {
        if ( is_callable( array( 'FrmAppHelper', 'plugin_url' ) ) ) {
            wp_enqueue_style( 'frm_billplz_admin', FRM_BILLPLZ_URL . 'assets/css/admin.css', array( 'formidable-admin' ), FRM_BILLPLZ_VER );
        }
    }

    public static function get_payment_action($action_id)
    {
        $action_control = FrmFormActionsController::get_form_actions('billplz');
        return $action_control->get_single_action($action_id);
    }

    public static function get_payment_actions_for_form($form_id)
    {
        if (is_callable('FrmFormAction::get_action_for_form')) {
            $actions = self::get_action_for_form($form_id, 'billplz');
        } else {
            $actions = FrmFormActionsHelper::get_action_for_form($form_id, 'billplz');
        }
        return $actions;
    }

    public static function form_has_payment_action($form_id)
    {
        $payment_actions = self::get_payment_actions_for_form($form_id);
        return ! empty($payment_actions);
    }

    function form($form_action, $args = array())
    {
        $form_fields = $this->get_field_options($args['form']->id);
        $show_amount = ($form_action->post_content['amount'] != '');
        $show_name = ($form_action->post_content['bill_name'] != '');
        $show_email = ($form_action->post_content['email'] != '');
        $show_mobile = ($form_action->post_content['mobile'] != '');
        $show_description = ($form_action->post_content['bill_description'] != '');
        $show_reference_1 = ($form_action->post_content['reference_1'] != '');
        $show_reference_2 = ($form_action->post_content['reference_2'] != '');
        
        include(FRM_BILLPLZ_PATH . 'views/settings/_payment_settings.php');
    }

    function add_new_pay_row()
    {
        $form_id = FrmAppHelper::get_post_param('form_id', '', 'absint');
        $row_num = FrmAppHelper::get_post_param('row_num', '', 'absint');
        $action_id = FrmAppHelper::get_post_param('email_id', '', 'absint');
        $form_action = $this->get_single_action($action_id);
        if (empty($form_action)) {
            $form_action = new stdClass();
            $form_action->ID = $action_id;
            $this->_set($action_id);
        }
        $form_action->post_content['change_field'][ $row_num ] = array( 'id' => '', 'value' => '', 'status' => '' );
        $this->after_pay_row(compact('form_id', 'row_num', 'form_action'));
        wp_die();
    }

    function after_pay_row($atts)
    {
        $id = 'frmbz_after_pay_row_' . absint($atts['form_action']->ID) . '_' . $atts['row_num'];
        $atts['name'] = $this->get_field_name('change_field');
        $atts['form_fields'] = $this->get_field_options($atts['form_id']);
        include(FRM_BILLPLZ_PATH . 'views/settings/_after_pay_row.php');
    }

    function get_defaults()
    {
        return FrmBillplzPaymentsHelper::get_default_options();
    }

    public function migrate_values($action, $form)
    {
        if (isset($action->post_content['conditions']['hide_field']) && ! empty($action->post_content['conditions']['hide_field'])) {
            $new_conditions = array();
            $action->post_content['conditions']['send_stop'] = 'send';
            foreach ($action->post_content['conditions']['hide_field'] as $k => $field_id) {
                $new_conditions[] = array(
                    'hide_field'        => $field_id,
                    'hide_field_cond'   => isset($action->post_content['conditions']['hide_field_cond'][$k]) ? $action->post_content['conditions']['hide_field_cond'][$k] : '==',
                    'hide_opt'          => isset($action->post_content['conditions']['hide_opt'][$k]) ? $action->post_content['conditions']['hide_opt'][$k] : '',
                );
            }
            $action->post_content['conditions'] = $new_conditions;
        }

        $action->post_content['event'] = array('create');
        return $action;
    }

    private function get_field_options($form_id)
    {
        $form_fields = FrmField::getAll(array(
            'fi.form_id' => absint($form_id),
            'fi.type not' => array( 'divider', 'end_divider', 'html', 'break', 'captcha', 'rte', 'form' ),
        ), 'field_order');
        return $form_fields;
    }
}
