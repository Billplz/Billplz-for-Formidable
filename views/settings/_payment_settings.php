<table class="form-table frm-no-margin">
    <tr><td class="frm-no-margin">
        <input type="hidden" value="<?php echo absint($form_action->ID) ?>" name="<?php echo esc_attr($this->get_field_name('action_id')) ?>" />
        <p><label class="frm_left_label"><?php _e('API Key', 'frmbz') ?></label>
            <input type="text" name="<?php echo esc_attr($this->get_field_name('api_key')) ?>" id="api_key" value="<?php echo esc_attr($form_action->post_content['api_key']); ?>" class="frm_not_email_subject frm_with_left_label" />
            <span class="clear"></span>
        </p>

        <p><label class="frm_left_label"><?php _e('Collection ID', 'frmbz') ?></label>
            <input type="text" name="<?php echo esc_attr($this->get_field_name('collection_id')) ?>" id="<?php echo esc_attr($this->get_field_id('collection_id')) ?>" value="<?php echo esc_attr($form_action->post_content['collection_id']); ?>" class="frm_not_email_to frm_with_left_label" />
            <span class="clear"></span>
        </p>

        <p><label class="frm_left_label"><?php _e('X Signature Key', 'frmbz') ?></label>
            <input type="text" name="<?php echo esc_attr($this->get_field_name('x_signature')) ?>" id="<?php echo esc_attr($this->get_field_id('x_signature')) ?>" value="<?php echo esc_attr($form_action->post_content['x_signature']); ?>" class="frm_not_email_to frm_with_left_label" />
            <span class="clear"></span>
        </p>

        <!---Name field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Payer Name', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('name_field')) ?>" class="frm_cancelnew <?php echo $show_name ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['name_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['name_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="<?php echo esc_attr($form_action->post_content['name']) ?>" name="<?php echo esc_attr($this->get_field_name('name')) ?>" class="frm_enternew <?php echo $show_name ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_name ? 'frm_hidden' : ''; ?>"><?php _e('Set Name', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_name ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

        <!---Email field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Payer Email', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('email_field')) ?>" class="frm_cancelnew <?php echo $show_email ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['email_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['email_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="email" value="<?php echo esc_attr($form_action->post_content['email']) ?>" name="<?php echo esc_attr($this->get_field_name('email')) ?>" class="frm_enternew <?php echo $show_email ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_email ? 'frm_hidden' : ''; ?>"><?php _e('Set Email', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_email ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

        <!---Mobile field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Payer Mobile', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('mobile_field')) ?>" class="frm_cancelnew <?php echo $show_mobile ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['mobile_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['mobile_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="<?php echo esc_attr($form_action->post_content['mobile']) ?>" name="<?php echo esc_attr($this->get_field_name('mobile')) ?>" class="frm_enternew <?php echo $show_mobile ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_mobile ? 'frm_hidden' : ''; ?>"><?php _e('Set Mobile', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_mobile ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

        <!---Description field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Description', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('description_field')) ?>" class="frm_cancelnew <?php echo $show_description ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['description_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['description_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="textarea" value="<?php echo esc_attr($form_action->post_content['description']) ?>" name="<?php echo esc_attr($this->get_field_name('description')) ?>" class="frm_enternew <?php echo $show_description ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_description ? 'frm_hidden' : ''; ?>"><?php _e('Set Description', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_description ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

        <!---Amount field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Amount', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('amount_field')) ?>" class="frm_cancelnew <?php echo $show_amount ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['amount_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['amount_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="<?php echo esc_attr($form_action->post_content['amount']) ?>" name="<?php echo esc_attr($this->get_field_name('amount')) ?>" class="frm_enternew <?php echo $show_amount ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_amount ? 'frm_hidden' : ''; ?>"><?php _e('Set Amount', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_amount ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

        <p><label class="frm_left_label"><?php _e('Reference 1 Label', 'frmbz') ?></label>
            <input type="text" name="<?php echo esc_attr($this->get_field_name('reference_1_label')) ?>" id="<?php echo esc_attr($this->get_field_id('reference_1_label')) ?>" value="<?php echo esc_attr($form_action->post_content['reference_1_label']); ?>" class="frm_not_email_to frm_with_left_label frm_short_input" />
            <span class="clear"></span>
        </p>

        <!---Reference 1 field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Reference 1', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('reference_1_field')) ?>" class="frm_cancelnew <?php echo $show_reference_1 ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['reference_1_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['reference_1_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="<?php echo esc_attr($form_action->post_content['reference_1']) ?>" name="<?php echo esc_attr($this->get_field_name('reference_1')) ?>" class="frm_enternew <?php echo $show_reference_1 ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_reference_1 ? 'frm_hidden' : ''; ?>"><?php _e('Set Reference 1', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_reference_1 ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

        <p><label class="frm_left_label"><?php _e('Reference 2 Label', 'frmbz') ?></label>
            <input type="text" name="<?php echo esc_attr($this->get_field_name('reference_2_label')) ?>" id="<?php echo esc_attr($this->get_field_id('reference_2_label')) ?>" value="<?php echo esc_attr($form_action->post_content['reference_2_label']); ?>" class="frm_not_email_to frm_with_left_label frm_short_input" />
            <span class="clear"></span>
        </p>

        <!---Reference 2 field -->
        <p class="frm_bz_toggle_new">
            <label class="frm_left_label"><?php _e('Reference 2', 'frmbz') ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('reference_2_field')) ?>" class="frm_cancelnew <?php echo $show_reference_2 ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e('&mdash; Select &mdash;') ?></option>
                <?php
                $selected = false;
                foreach ($form_fields as $field) {
                    if ($form_action->post_content['reference_2_field'] == $field->id) {
                        $selected = true;
                    }
                    ?>
                    <option value="<?php echo esc_attr($field->id) ?>" <?php selected($form_action->post_content['reference_2_field'], $field->id) ?>><?php
                    echo esc_attr(FrmAppHelper::truncate($field->name, 50, 1));
                    unset($field);
                    ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="<?php echo esc_attr($form_action->post_content['reference_2']) ?>" name="<?php echo esc_attr($this->get_field_name('reference_2')) ?>" class="frm_enternew <?php echo $show_reference_2 ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_bz_opts">
                <span class="frm_enternew <?php echo $show_reference_2 ? 'frm_hidden' : ''; ?>"><?php _e('Set Reference 2', 'frmbz'); ?></span>
                <span class="frm_cancelnew <?php echo $show_reference_2 ? '' : 'frm_hidden'; ?>"><?php _e('Select Field', 'frmbz'); ?></span>
            </a>
            <span class="clear"></span>
        </p>

            <p>
                <label class="frm_left_label"><?php _e('Notifications', 'frmbz') ?></label>
                <label for="<?php echo esc_attr($this->get_field_id('stop_email')) ?>">
                    <input type="checkbox" value="1" name="<?php echo esc_attr($this->get_field_name('stop_email')) ?>" <?php checked($form_action->post_content['stop_email'], 1) ?> id="<?php echo esc_attr($this->get_field_id('stop_email')) ?>" />
                    <?php _e('Hold email notifications until payment is received.', 'frmbz') ?>
                    <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e('Stop all emails set up with this form, including the registration email if applicable. Send them when the successful payment notification is received from Billplz.', 'frmbz') ?>" ></span>
                </label>
            </p>

            <p><label class="frm_left_label"><?php _e('Currency', 'frmbz') ?></label>
                <select name="<?php echo esc_attr($this->get_field_name('currency')) ?>" id="frm_billplz_currency">
                    <?php foreach (FrmBillplzPaymentsHelper::get_currencies() as $code => $currency) { ?>
                        <option value="<?php echo esc_attr($code) ?>" <?php selected($form_action->post_content['currency'], $code) ?>><?php echo esc_html($currency['name'] . ' (' . $code . ')'); ?></option>
                        <?php
                        unset($currency, $code);
                    }
?>
                </select>
            </p>

            <p><label class="frm_left_label"><?php _e('Return URL', 'frmbz') ?></label>
                <input type="text" name="<?php echo esc_attr($this->get_field_name('return_url')) ?>" id="billplz_return_url" value="<?php echo esc_attr($form_action->post_content['return_url']); ?>" class="frm_not_email_subject frm_with_left_label" />
                <span class="clear"></span>
            </p>

            <p><label class="frm_left_label"><?php _e('Cancel URL', 'frmbz') ?></label>
                <input type="text" name="<?php echo esc_attr($this->get_field_name('cancel_url')) ?>" id="billplz_cancel_url" value="<?php echo esc_attr($form_action->post_content['cancel_url']); ?>" class="frm_not_email_subject frm_with_left_label" />
                <span class="clear"></span>
            </p>

            <h3><?php _e('After Payment', 'frmbz') ?>
                <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e('Change a field value when the status of a payment changes.', 'frmbz') ?>" ></span>
            </h3>
            <div class="frm_add_remove">
                <p id="frmbz_after_pay_<?php echo absint($form_action->ID) ?>" <?php echo empty($form_action->post_content['change_field']) ? '' : 'class="frm_hidden"'; ?>>
                    <a href="#" class="frm_add_bz_logic button" data-emailkey="<?php echo absint($form_action->ID) ?>">+ <?php _e('Add', 'frmbz') ?></a>
                </p>
                <div id="postcustomstuff" class="frmbz_after_pay_rows <?php echo empty($form_action->post_content['change_field']) ? 'frm_hidden' : ''; ?>">
                    <table id="list-table">
                        <thead>
                            <tr>
                                <th><?php _e('Payment Status') ?></th>
                                <th><?php _e('Field', 'frmbz') ?></th>
                                <th><?php _e('Value', 'frmbz') ?></th>
                                <th style="max-width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody data-wp-lists="list:meta">
                            <?php
                            foreach ($form_action->post_content['change_field'] as $row_num => $vals) {
                                $this->after_pay_row(array(
                                    'form_id' => $args['form']->id, 'row_num' => $row_num, 'form_action' => $form_action,
                                ));
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </td></tr>
</table>
