<tr id="<?php echo $id ?>" class="frmbz_after_pay_row frmbz_after_pay_row_<?php echo absint($atts['form_action']->ID) ?>">
    <td><?php echo FrmBillplzPaymentsHelper::after_payment_status($atts) ?></td>
    <td><?php echo FrmBillplzPaymentsHelper::after_payment_field_dropdown($atts) ?></td>
    <td><input type="text" name="<?php echo esc_attr($atts['name']) ?>[<?php echo absint($atts['row_num']) ?>][value]" value="<?php echo esc_attr($atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['value']) ?>"/></td>
    <td style="vertical-align:middle;">
        <a href="#" class="frm_remove_tag frm_icon_font" data-removeid="<?php echo esc_attr($id) ?>" data-showlast="#frmbz_after_pay_<?php echo absint($atts['form_action']->ID) ?>"></a>
        <a href="#" class="frm_add_tag frm_icon_font frm_add_bz_logic" data-emailkey="<?php echo absint($atts['form_action']->ID) ?>"></a>
    </td>
</tr>
