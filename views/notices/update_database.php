<div class="error" id="frmpay_install_message">
  <p><?php
    printf( __( 'Your Billplz for Formidable database needs to be updated.%1$sPlease deactivate and reactivate the plugin or %2$sUpdate Now%3$s.', 'frmbz' ),
    '<br/>',
    '<a id="frmbz_install_link" href="javascript:frmbz_install_now()">',
    '</a>' );?>
  </p>
</div>

<script type="text/javascript">
  function frmbz_install_now(){
    jQuery('#frmbz_install_link').replaceWith('<img src="<?php echo esc_url_raw( $url ) ?>/images/wpspin_light.gif" alt="<?php esc_attr_e( 'Loading&hellip;' ); ?>" />');
    jQuery.ajax({type:'POST',url:"<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ) ?>",data:'action=frmbz_install',
      success:function(msg){jQuery("#frmbz_install_message").fadeOut('slow');}
    });
  }
</script>