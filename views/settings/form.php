    <table class="form-table">
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Is Sandbox', 'frmbz') ?></label></td>
            <td>
                <select name="frm_billplz_is_sandbox" id="frm_billplz_is_sandbox">
                <option value="sandbox" <?php selected($frm_payment_settings->settings->is_sandbox, 'sandbox') ?>>sandbox</option>
                <option value="production" <?php selected($frm_payment_settings->settings->is_sandbox, 'production') ?>>production</option>
                </select>
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('API Secret Key', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_api_key" id="frm_billplz_api_key" value="<?php echo esc_attr($frm_payment_settings->settings->api_key) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Collection ID', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_collection_id" id="frm_billplz_collection_id" value="<?php echo esc_attr($frm_payment_settings->settings->collection_id) ?>" class="frm_short_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('X Signature Key', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_x_signature" id="frm_billplz_x_signature" value="<?php echo esc_attr($frm_payment_settings->settings->x_signature) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Description', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_description" id="frm_billplz_description" value="<?php echo esc_attr($frm_payment_settings->settings->description) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Reference 1 Label', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_reference_1_label" id="frm_billplz_reference_1_label" value="<?php echo esc_attr($frm_payment_settings->settings->reference_1_label) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Reference 1', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_reference_1" id="frm_billplz_reference_1" value="<?php echo esc_attr($frm_payment_settings->settings->reference_1) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Reference 2 Label', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_reference_2_label" id="frm_billplz_reference_2_label" value="<?php echo esc_attr($frm_payment_settings->settings->reference_2_label) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('Reference 2', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_reference_2" id="frm_billplz_reference_2" value="<?php echo esc_attr($frm_payment_settings->settings->reference_2) ?>" class="frm_long_input" />
                    
            </td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Currency', 'frmbz') ?></label></td>
            <td>
                <select name="frm_billplz_currency" id="frm_billplz_currency">
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
                <input type="text" name="frm_billplz_return_url" id="frm_billplz_return_url" value="<?php echo esc_attr($frm_payment_settings->settings->return_url) ?>" class="frm_long_input"  />
                <div class="howto"><?php _e('The URL for Billplz to send users after purchase', 'frmbz') ?></div>
            </td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Cancel URL', 'frmbz') ?></label></td>
            <td>
                <input type="text" name="frm_billplz_cancel_url" id="frm_billplz_cancel_url" value="<?php echo esc_attr($frm_payment_settings->settings->cancel_url) ?>" class="frm_long_input"  />
                <div class="howto"><?php _e('The URL for Billplz to send users if they cancel the transaction', 'frmbz') ?></div>
            </td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Log Results', 'frmbz') ?></label></td>
            <td>
                <p><label for="frm_billplz_callback_log"><input type="checkbox" name="frm_billplz_callback_log" id="frm_billplz_callback_log" value="1" <?php checked($frm_payment_settings->settings->callback_log, 1) ?> /> <?php _e('Log results from Callback notifications', 'frmbz') ?></label></p>
                <p><input type="text" name="frm_billplz_callback_log_file" value="<?php echo esc_attr($frm_payment_settings->settings->callback_log_file) ?>" class="frm_long_input" /><br/>
                    <span class="howto"><?php _e('The location of the error log', 'frmbz') ?></span>
                </p>
            </td>
        </tr>
    </table>
