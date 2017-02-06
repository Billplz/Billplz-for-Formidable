<table class="form-table">
    <tr class="form-field" valign="top">
        <td width="200px"><label><?php _e('API Secret Key', 'frmbz') ?></label></td>
        <td>
            <input type="text" name="frm_pay_api_key" id="frm_pay_api_key" value="<?php echo esc_attr($frm_payment_settings->settings->api_key) ?>" class="frm_long_input" />

        </td>
    </tr>

    <tr class="form-field" valign="top">
        <td width="200px"><label><?php _e('Collection ID', 'frmbz') ?></label></td>
        <td>
            <input type="text" name="frm_pay_collection_id" id="frm_pay_collection_id" value="<?php echo esc_attr($frm_payment_settings->settings->collection_id) ?>" class="frm_long_input" />

        </td>
    </tr>

    <tr class="form-field" valign="top">
        <td><label><?php _e('Currency', 'frmbz') ?></label></td>
        <td>
            <select name="frm_pay_currency" id="frm_pay_currency">
                <?php foreach (FrmBillplzPaymentsHelper::get_currencies() as $code => $currency) { ?>
                    <option value="<?php echo esc_attr($code) ?>" <?php selected($frm_payment_settings->settings->currency, $code) ?>><?php echo esc_html($currency['name'] . ' (' . $code . ')'); ?></option>
                    <?php
                    unset($currency);
                    unset($code);
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="form-field" valign="top">
        <td><label><?php _e('Return URL', 'frmbz') ?></label></td>
        <td>
            <input type="text" name="frm_pay_return_url" id="frm_pay_return_url" value="<?php echo esc_attr($frm_payment_settings->settings->return_url) ?>" class="frm_long_input"  />
            <div class="howto"><?php _e('The URL for Billplz to send users after purchase', 'frmbz') ?></div>
        </td>
    </tr>

    <tr class="form-field" valign="top">
        <td><label><?php _e('Cancel URL', 'frmbz') ?></label></td>
        <td>
            <input type="text" name="frm_pay_cancel_url" id="frm_pay_cancel_url" value="<?php echo esc_attr($frm_payment_settings->settings->cancel_url) ?>" class="frm_long_input"  />
            <div class="howto"><?php _e('The URL for Billplz to send users if they cancel the transaction', 'frmbz') ?></div>
        </td>
    </tr>

    <tr class="form-field" valign="top">
        <td><label><?php _e('Log Results', 'frmbz') ?></label></td>
        <td>
            <p><label for="frm_pay_ipn_log"><input type="checkbox" name="frm_pay_ipn_log" id="frm_pay_ipn_log" value="1" <?php checked($frm_payment_settings->settings->ipn_log, 1) ?> /> <?php _e('Log results from IPN notifications', 'frmbz') ?></label></p>
            <p><input type="text" name="frm_pay_ipn_log_file" value="<?php echo esc_attr($frm_payment_settings->settings->ipn_log_file) ?>" class="frm_long_input" /><br/>
                <span class="howto"><?php _e('The location of the error log', 'frmbz') ?></span>
            </p>
        </td>
    </tr>
</table>