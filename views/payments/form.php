<div class="postbox">
<h3 class="hndle"><span><?php _e('Payment Details', 'frmbz') ?></span></h3>
<div class="inside">

<input type="hidden" name="action" value="<?php echo esc_attr($form_action) ?>"/>

<table class="form-table"><tbody>
    <tr class="form-field">
        <th scope="row"><?php _e('Completed', 'frmbz') ?></th>
        <td><input type="checkbox" value="1" name="completed" <?php checked($payment['completed'], 1) ?> /></td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Entry ID', 'frmbz') ?></th>
        <td>
            #<input type="number" name="item_id" value="<?php echo esc_attr($payment['item_id']) ?>" />
        </td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Receipt', 'frmbz') ?></th>
        <td><input type="text" name="receipt_id" value="<?php echo esc_attr($payment['receipt_id']) ?>" /></td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Amount', 'frmbz') ?></th>
        <td><?php echo $currency['symbol_left'] ?><input type="text" name="amount" value="<?php echo esc_attr($payment['amount']) ?>" /><?php echo $currency['symbol_right'] ?></td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Date', 'frmbz') ?></th>
        <td><input type="text" name="begin_date" class="" value="<?php echo esc_attr($payment['begin_date']) ?>" /></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Expiration Date', 'frmbz') ?></th>
        <td><input type="text" name="expire_date" class="" value="<?php echo esc_attr($payment['expire_date']) ?>" /></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Payment Method', 'frmbz') ?></th>
        <td><select name="paysys">
                <option value="billplz" <?php selected($payment['paysys'], 'billplz') ?>><?php _e('Billplz', 'frmbz'); ?></option>
                <option value="paypal" <?php selected($payment['paysys'], 'paypal') ?>><?php _e('PayPal', 'frmbz'); ?></option>
                <option value="manual" <?php selected($payment['paysys'], 'manual') ?>><?php _e('Manual', 'frmbz'); ?></option>
            </select>
    </tr>
</tbody></table>
</div>
</div>
