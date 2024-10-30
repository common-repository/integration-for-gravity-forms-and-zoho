<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

if ( ! function_exists( 'igzf_zoho_main_menu' ) ) {
    add_action( 'admin_menu', 'igzf_zoho_main_menu' );
    function igzf_zoho_main_menu() {
        add_menu_page( 'Gravity Forms - Zoho CRM', 'GF - Zoho CRM', 'manage_options', 'igzf_zoho_integration', 'igzf_zoho_integration_callback', 'dashicons-migrate' );

        add_submenu_page( 'igzf_zoho_integration', __( 'GF - Zoho CRM: Integration' ), __( 'Integration' ), 'manage_options', 'igzf_zoho_integration', 'igzf_zoho_integration_callback' );

        add_submenu_page( 'igzf_zoho_integration', __( 'GF - Zoho CRM: Authentication' ), __( 'Authentication' ), 'manage_options', 'igzf_zoho_configuration', 'igzf_zoho_authendation_callback' );

          add_submenu_page( 'igzf_zoho_integration', __( 'GF - Zoho CRM: API Error Logs' ), __( 'API Error Logs' ), 'manage_options', 'igzf_zoho_log', 'igzf_zoho_errorlog_callback' );
    }
}
$zoho_token = get_option('igzf_zoho_token');
$zoho = new ZGF_ZOHO_API('','','');
if ( ! function_exists( 'igzf_zoho_integration_callback' ) ) {
    function igzf_zoho_integration_callback() {
    global $wpdb;
    include_once ZGF_ZOHO_PLUGIN_PATH . 'admin/form-create-popup.php';
?>
<div class="wrap">

  <?php
  $reqID = filter_input(INPUT_GET, 'id', FILTER_CALLBACK, ['options' => 'esc_html']);
    if($reqID){
        $message='';
        $reqSave = filter_input(INPUT_POST, 'Save', FILTER_CALLBACK, ['options' => 'esc_html']);
      if($reqSave){
        $reqigzf_zoho_fields = filter_input(INPUT_POST, 'igzf_zoho_fields', FILTER_CALLBACK, ['options' => 'esc_html']);
        $igzf_zoho_fields = $reqigzf_zoho_fields ?: array() ;
        $form_id = sanitize_text_field($_REQUEST['formid']);
        $formtitle = sanitize_text_field($_REQUEST['formtitle']);
        update_option( 'igzf_zoho_fields_'.$form_id, $igzf_zoho_fields );
        $modulename = sanitize_text_field($_REQUEST['moduleList']);
        $layoutname = sanitize_text_field($_REQUEST['layoutlist']);
        $moduleData = get_option('zoho_crm_module_data'.$modulename);
        $igzf_zoho_fields =get_option( 'igzf_zoho_fields_'.$form_id );
        $duplicatemapchk[]=array();
        $message = '';
        $refields = get_option('zoho-form-'.$modulename.'-reqfields');
        foreach ($igzf_zoho_fields as $key =>$value){
          array_push($duplicatemapchk,$value['key']);
        }
        $duplicatevalue = false;
         if(count($duplicatemapchk) != count(array_unique($duplicatemapchk,SORT_REGULAR))){
           $duplicatevalue = true;
        }
        $found_key = array_search($refields,$duplicatemapchk);

        if($duplicatevalue ==true && $duplicatevalue !=null){
          $message = "Dulicate Field Mapping found";
        }elseif($found_key =='' && $found_key ==null){
          $message = "Required field missing";
        }else{
          $message = "Mapped successfully";
          $igzf_form_data = array();
          $igzf_form_data['module']         = $modulename;
          $igzf_form_data['layout']         = $layoutname;
          $igzf_form_data['mappingfields']  = $igzf_zoho_fields;
          $igzf_form_data['assigned']       = sanitize_text_field($_REQUEST['usertype']);
          $igzf_form_data_config            = "igzf_data_form_".$form_id;
          $igzf_data = serialize($igzf_form_data);
          update_option($igzf_form_data_config,serialize($igzf_form_data));
          global $wpdb;
          $fetchdata = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}igzfformdata  WHERE form_id = %s",sanitize_text_field($_REQUEST['id'])));
          $table_name = $wpdb->prefix . 'igzfformdata';
          if(!empty($fetchdata)){
            $execut= $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}igzfformdata SET form_data = %s WHERE form_id = %s", serialize($igzf_form_data), sanitize_text_field($_REQUEST['id']) ) );
          }else{
            $wpdb->insert($table_name, array(
              'form_id' =>$form_id,
              'formtitle' => $formtitle,
              'form_data' => serialize($igzf_form_data)

            ));
          }
        }


      }



  ?>
  <h3><?php _e( 'Field Mapping' ); ?></h3>
    <form  method='post'>
  <input value="<?php echo sanitize_text_field($_REQUEST['moduleList']);?>" id='modulename' name='modulename' type='hidden'/>
  <table class='widefat' style='border:solid 1px #ccc;width:500px'>
    <tbody>
    <tr><td><?php _e('Form Title');?></td>
      <td>
        <?php
        $form_id = sanitize_text_field($_REQUEST['id']);
        $form_meta = $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.'gf_form WHERE id='.$form_id.' LIMIT 1' );
        echo sanitize_text_field($form_meta->title);?>
      </td>
    </tr>
    <tr><td><?php _e('Module name');?></td><td><?php echo sanitize_text_field($_REQUEST['moduleList']);?></td></tr>
    <tr><td><?php _e('Layout name');?></td><td><?php echo sanitize_text_field($_REQUEST['layoutlist']);?></td></tr>

    <tr><td>Please select user</td>
      <td>
          <select name='usertype' id='usertype'>

        <?php
        $igzf_zoho_meta =get_option( 'igzf_data_form_'.$form_id );
        $igzf_zoho_meta_data = unserialize($igzf_zoho_meta);
        $assigned = $igzf_zoho_meta_data['assigned'];
        $userlist = get_option('zoho_users_list');
        $selectionOption ="<option value=''>Please select user </option>";
        foreach ($userlist['users'] as $value) {
            $usernname = $value['full_name'];
            $userid = $value['id'];
            $useremail = $value['email'];
            $userdetail = $usernname."(".$useremail.")";
            $selected = '';
            if ( isset( $assigned ) && $assigned == $userid ) {
                $selected = ' selected="selected"';
            }

            $selectionOption = $selectionOption ."<option value='".$userid."' $selected>".$userdetail."</option>";
        }
          printf($selectionOption);
        ?>
        </select>
    </td>
    </tr>


  </tbody>
  </table>
<br>
  <?php
    $form_id = sanitize_text_field($_REQUEST['id']);
    $form_meta = $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.'gf_form_meta WHERE form_id='.$form_id.' LIMIT 1' );

    $gfformmeta = json_decode( $form_meta->display_meta );
    $form_title = $gfformmeta->title;
    $igzf_fields = array();
    if ( $gfformmeta->fields != null ) {
        foreach ( $gfformmeta->fields as $field ) {
            if ( $field->inputs != '' && ( $field->type == 'name' || $field->type == 'address' ) ) {
                foreach ( $field->inputs as $input ) {
                    if ( isset( $input->isHidden ) && $input->isHidden ) {
                        //
                    } else {
                        $igzf_fields[$input->id] = array(
                            'key'   => $input->id,
                            'type'  => $field->type,
                            'label' => $input->label.' ('.$field->label.')',
                        );
                    }
                }
            } else {
                $igzf_fields[$field->id] = array(
                    'key'   => $field->id,
                    'type'  => $field->type,
                    'label' => $field->label,
                );
            }
        }
    }

    $modulename = sanitize_text_field($_REQUEST['moduleList']);
    $layoutname = sanitize_text_field($_REQUEST['layoutlist']);
    $moduleData = get_option('zoho_crm_module_data'.$modulename);

    $igzf_zoho_fields =get_option( 'igzf_zoho_fields_'.$form_id );



  ?>

  <table class='widefat striped' style='border:solid 1px #ccc' >
    <input name='formid' value='<?php echo sanitize_text_field($form_id); ?>' id='formid'/ type='hidden'>
    <input name='formtitle' value='<?php echo sanitize_text_field($form_title); ?>' id='formtitle'/ type='hidden'>
  <thead><tr><th>Gravity form fields </th><th>Zoho CRM fields</th></tr></thead>
<?php foreach ( $igzf_fields as $igzf_field_key => $igzf_field_value ) { ?>
  <tr>
    <td><?php echo sanitize_text_field($igzf_field_value['label']); ?></td>
    <td>
      <select id='crm-field' name="igzf_zoho_fields[<?php echo sanitize_text_field($igzf_field_key); ?>][key]">
        <option value=''><?php _e('Select a Fields');?></option>
    <?php
      $type='';
        $system_mandatoryfields = array();
      foreach ($moduleData['fields'] as $key => $value) {

        if($value['system_mandatory'] ==1){
          $system_mandatoryfields = $value['api_name'];
          update_option('zoho-form-'.$modulename.'-reqfields',$system_mandatoryfields);
        }
        $selected = '';
        if ( isset( $igzf_zoho_fields[$igzf_field_key]['key'] ) && $igzf_zoho_fields[$igzf_field_key]['key'] == $value['api_name'] ) {
            $selected = ' selected="selected"';
            $type = $value['json_type'];
        }
    ?>

    <option value='<?php echo sanitize_text_field($value['api_name']); ?>' <?php echo sanitize_text_field($selected); ?>>
        <?php echo sanitize_text_field($value['field_label']); ?> (<?php _e( 'Data Type:' ); ?> <?php echo sanitize_text_field($value['json_type']); echo ( sanitize_text_field($value['system_mandatory']) ? __( ' and Field: Required' ) : '' ); ?>)
    </option>
  <?php }



  ?>
  </select>
  </td>
</tr>
<input type="hidden" name="igzf_zoho_fields[<?php echo sanitize_text_field($igzf_field_key); ?>][type]" value="<?php echo sanitize_text_field($type); ?>" />

<input type="hidden" name="igzf_zoho_fields[<?php echo sanitize_text_field($igzf_field_key); ?>][field_type]" value="<?php echo sanitize_text_field($igzf_field_value['type']); ?>" />
<?php


 } ?>
<tr><td>
  <input class='button-primary' name='Save' id='save-data' type='submit' value='Save Data'>
  <a href="<?php echo menu_page_url( 'igzf_zoho_integration', 0 ); ?>" ><?php _e('Cancel');?></a>

</td><td><?php echo sanitize_text_field($message);?></td></tr>
<tfoot><tr><th><?php _e('Gravity form fields');?> </th><th><?php _e('Zoho CRM fields');?></th></tr></tfoot>
  </table>
</form>
<?php
  } else {
?>
  <div>
    <span style="display: inline-flex;vertical-align: middle;">
      <h3><?php _e( 'Gravity Forms - Zoho CRM' ); ?> </h3>
    </span>
    <span style="display: inline-flex;vertical-align: middle;padding: 10px;gap: 20px;">
  <input type="button" class="button-secondary" value="Add New Form" onclick="zgfcreatePopup()">  <button id='fetchmodule'  class='button-secondary' type="submit" style='cursor: pointer;' onclick='zgfgetModuleList(this);' ><?php _e('Sync Modules');?></button></span><span class='moduleloading' id='moduleloading' style='display:none'></span></div>
  <table class="widefat striped">
      <thead>
          <tr>
              <th><?php _e( 'Form ID' ); ?></th>
              <th><?php _e( 'Form Title' ); ?></th>
              <th><?php _e( 'Module Name' ); ?></th>
              <th><?php _e( 'Action' ); ?></th>
          </tr>
      </thead>
      <tfoot>
          <tr>
            <th><?php _e( 'Form ID' ); ?></th>
            <th><?php _e( 'Form Title' ); ?></th>
            <th><?php _e( 'Module Name' ); ?></th>
            <th><?php _e( 'Action' ); ?></th>
          </tr>
      </tfoot>
      <tbody>
        <tr>
          <?php
          global $wpdb;
            $fetchconfigdata = $wpdb->get_results("SELECT * FROM wp_igzfformdata");
              if ( $fetchconfigdata != null ) {
                  foreach ( $fetchconfigdata as $value ) {
                    $modulename= unserialize($value->form_data)['module'];
                    $layout= unserialize($value->form_data)['layout'];
                      ?>
                          <tr>
                              <td><?php echo sanitize_text_field($value->form_id); ?></td>
                              <td><?php echo sanitize_text_field($value->formtitle); ?></td>
                              <td><?php echo sanitize_text_field($modulename); ?></td>
                              <td><a href="<?php echo menu_page_url( 'igzf_zoho_integration', 0 ); ?>&id=<?php echo sanitize_text_field($form_id); ?>&moduleList=<?php echo sanitize_text_field($modulename); ?>&layoutlist=<?php echo sanitize_text_field($layout); ?>"><span class="dashicons dashicons-edit"></span></a>
                              <a><span style='cursor:pointer' class="dashicons dashicons-trash" data-id='<?php echo sanitize_text_field($value->form_id); ?>' onclick='zgfDeleteForm(this)'></span></a></td>
                          </tr>
                      <?php
                  }
              } else {
                  ?>
                      <tr>
                          <td colspan="3"><?php _e( 'No forms found.' ); ?></td>
                      </tr>
                  <?php
              }
              wp_reset_postdata();
          ?>
        </tr>
      </tbody>
    </table>
</div>
<?php
}
}
}
if(!function_exists('igzf_zoho_authendation_callback')){
  function igzf_zoho_authendation_callback(){
    global $wpdb;
    $reqsubmit = filter_input(INPUT_POST, 'submit', FILTER_CALLBACK, ['options' => 'esc_html']);
    $reqcode = filter_input(INPUT_GET, 'code', FILTER_CALLBACK, ['options' => 'esc_html']);

      if ( $reqsubmit ) {
        $datacenter =  sanitize_text_field($_POST['datacenter']);
        update_option('igzf_zoho_datacenter',  $datacenter);
        $client_id = sanitize_text_field($_POST['igzf_zoho_client_id']);
        update_option('igzf_zoho_client_id',  $client_id);
        $client_secret = sanitize_text_field($_POST['igzf_zoho_client_secret']);
        update_option('igzf_zoho_client_secret',  $client_secret);
        $redirection_url = menu_page_url( 'igzf_zoho_configuration', 0 );
        update_option('igzf_zoho_redirection_url',  $redirection_url);
        $datacenterUrl = "https://accounts.zoho".$datacenter;
        $url = "$datacenterUrl/oauth/v2/auth?client_id=$client_id&redirect_uri=$redirection_url&response_type=code&scope=ZohoCRM.modules.all,ZohoCRM.users.all,ZohoCRM.settings.all&access_type=offline";
        ?>
            <script type="text/javascript">
                jQuery( document ).ready( function( $ ) {
                    window.location.replace( '<?php echo __($url); ?>' );
                });
            </script>
<?php
}else if($reqcode){
      $domain = get_option( 'igzf_zoho_domain' );
      $domain = rtrim( $domain, '/' );
      $client_id = get_option( 'igzf_zoho_client_id' );
      $client_secret = get_option( 'igzf_zoho_client_secret' );
      $code = sanitize_text_field($_REQUEST['code']);
      $redirect_uri = menu_page_url( 'igzf_zoho_configuration', 0 );
      $zoho = new ZGF_ZOHO_API( $domain, $client_id, $client_secret );
      $token = $zoho->zgfgetToken( $code, $redirect_uri );

      if ( isset( $token->error ) ) {
?>
      <div class="notice notice-error is-dismissible">
          <p><strong><?php _e( 'Error' ); ?></strong>: <?php echo sanitize_text_field($token->error); ?></p>
      </div>
    <?php
      } else {
          $zoho_old_token = get_option( 'igzf_zoho' );
          if ( ! isset( $token->refresh_token ) && $zoho_old_token ) {
              $zoho_old_token->access_token = $token->access_token;
              $token = $zoho_old_token;
          }

          update_option( 'ignbk_zohobooks_token', $token );
          $redirect_uri = menu_page_url( 'igzf_zoho_integration', 0 );
          ?>
          <div class="notice notice-success is-dismissible">
              <p><?php _e( 'Configuration successful.' ); ?></p>
          </div>
          <script type="text/javascript">
              jQuery( document ).ready( function( $ ) {
                  window.setTimeout(function(){
                      window.location.replace( '<?php echo esc_url($redirect_uri); ?>' );
                  }, 3000);
              });
          </script>

    <?php
    }
  }
  $client_id = get_option( 'igzf_zoho_client_id' );
  $client_secret = get_option( 'igzf_zoho_client_secret' );
?>
<div class="wrap">
  <h1><?php _e( 'Zoho CRM Authentication' ); ?></h1>
  <form name='authenticationform' method='post'>
    <table class="form-table">
      <tr>
          <th scope="row"><?php _e('Data Center');?></th>
          <td>
              <select name='datacenter' id='datacenter'>
                <option value='.com'>https://accounts.zoho.com</option>
                <option value='.au'>https://accounts.zoho.au</option>
                <option value='.eu'>https://accounts.zoho.eu</option>
                <option value='.in'>https://accounts.zoho.in</option>
              </select>

      </tr>
      <tr>
        <th scope="row"><?php _e('Client id');?></th>
        <td>
          <input class="regular-text" type="text" name="igzf_zoho_client_id" value="<?php echo sanitize_text_field($client_id); ?>" required />
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Client Secret');?></th>
        <td>
          <input class="regular-text" type="text" name="igzf_zoho_client_secret" value="<?php echo sanitize_text_field($client_secret); ?>" required />
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Redirection Url');?></th>
        <td>
          <p disabled><?php echo menu_page_url( 'igzf_zoho_configuration', 0 ); ?></p>
        </td>
      </tr>
      <tr>
        <th>
          <input type='submit' class='button-primary' name="submit" value="<?php _e( 'Authentication' ); ?>" />
        </th>
      </tr>
    </table>
  </form>
</div>
<?php
}
}
if(!function_exists('igzf_zoho_errorlog_callback')){
  function igzf_zoho_errorlog_callback(){
?>
  <div class="wrap">
  <h1><?php _e( 'Failure Logs' ); ?></h1>
  <?php
    $file_path = ZGF_ZOHO_PLUGIN_PATH.'debug.log';
    $reqapisubmit = filter_input(INPUT_POST, 'apisubmit', FILTER_CALLBACK, ['options' => 'esc_html']);

      if ( $reqapisubmit ) {
            $file = fopen( $file_path, 'w' );
            fclose( $file );
        }
    $file = fopen( $file_path, 'r' );
    $file_size = filesize( $file_path );
    if ( $file_size ) {
      $file_data = fread( $file, $file_size );
        if ( $file_data ) {
          echo '<pre style="overflow: scroll;">'; print_r( $file_data ); echo '</pre>';
   ?>
   <form method="post">
    <p>
    <input type='submit' class='button-primary' name="apisubmit" value="<?php _e( 'Clear API Error Logs' ); ?>" />
    </p>
  </form>
  <?php
  }
  } else {
  ?><p><?php _e( 'No API error logs found.' ); ?></p>
  <?php }
  fclose( $file );

  }
}
?>
