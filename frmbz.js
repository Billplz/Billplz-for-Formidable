function frmBZBuildJS(){
    function toggle_amount(){
        var $link = jQuery(this).closest('.frm_bz_toggle_new');
        $link.find('.frm_enternew, .frm_cancelnew').toggle();
        $link.find('input.frm_enternew, select.frm_cancelnew').val('');
        return false;
    }

    function addAfterPayRow(){
        var id = jQuery(this).data('emailkey');
        var rowNum = 0;
        var form_id = document.getElementById('form_id').value;
        if(jQuery('#frm_form_action_'+id+' .frmbz_after_pay_row').length){
            rowNum = 1 + parseInt(jQuery('#frm_form_action_'+id+' .frmbz_after_pay_row:last').attr('id').replace('frmbz_after_pay_row_'+id+'_', ''));   
        }
        jQuery.ajax({
            type:'POST',url:ajaxurl,
            data:{action:'frmbz_after_pay', email_id:id, form_id:form_id, row_num:rowNum, nonce:frmGlobal.nonce},
            success:function(html){
                var addButton = jQuery(document.getElementById('frmbz_after_pay_'+id));
                addButton.fadeOut('slow', function(){
                    var $logicRow = addButton.next('.frmbz_after_pay_rows');
                    $logicRow.find('tbody').append(html);
                    $logicRow.fadeIn('slow');
                });
            }
        });
        return false;
    }

    return{
        init: function(){
            var actions = document.getElementById('frm_notification_settings');
            jQuery(actions).on('click', '.frm_toggle_bz_opts', toggle_amount);
            jQuery('.frm_form_settings').on('click', '.frm_add_bz_logic', addAfterPayRow);
        }
    };
}

var frmBZBuild = frmBZBuildJS();

jQuery(document).ready(function($){
    frmBZBuild.init();
});