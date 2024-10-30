
<div class=' createPopup'>
  <span class="closeform " style='cursor: pointer;position:absolute;right:10px;top:10px;background:#f1f1f1;padding:5px 10px;border-radius:16px' onclick="jQuery('.createPopup').hide()">X</span>

<?php
$forms = $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'gf_form WHERE is_trash=0' );

?>
<form name='form-sub' method="get"  action="<?php echo menu_page_url( 'igzf_zoho_integration', 0 ); ?>">
  <input name='page' value='igzf_zoho_integration' type='hidden'/>
        <table class='form-table'>
        <tr>
            <th><?php _e('Choose Form');?></th>
            <td>
            <select id='id' name='id' onchange="zgfgetModuleList(this)">
            <option value=''><?php _e('Select form');?></option>
        <?php
        if ( $forms != null ) {
        foreach ( $forms as $form ) {
        $form_id = $form->id;
        ?>
          <option value="<?php echo sanitize_text_field($form->id);?>"><?php echo sanitize_text_field($form->title); ?></option>
        <?php
        }
        }
        ?>
        </select>
        </td>
        </tr>
        <tr>
          <th><?php _e('Module Name');?></th>
          <td>
            <select id='moduleList' name='moduleList' onchange="zgfupdateLayout(this);" disabled>
              <option value='Please select form'><?php _e('Please select module');?></option>
            </select> <span class='moduleloading' id='moduleloading' style='display:none'></span>
          </td>
        </tr>
        <tr>
          <th><?php _e('Layout Name');?></th>
          <td>
            <select id='layoutlist' name='layoutlist'  disabled>
              <option value='Please select module'><?php _e('Please select layout');?></option>
            </select><span id='layoutloading' style='display:none'></span>
          </td>
        </tr>
        <tr>
          <th></th>
          <td>
                <button id='saveform'  class='button-primary' style='cursor: pointer;' disabled><?php _e('Next');?></button>

          </td>
        </tr>
        </table>
</form>
</div>
