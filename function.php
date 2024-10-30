<?php
add_action( 'wp_ajax_zgfgetCrmFields', 'zgfgetCrmFields' );
function zgfgetCrmFields() {
  $zoho                   = new ZGF_ZOHO_API('','','');
  $modulename             = sanitize_text_field($_POST['modulename']);
  $zoho_token             = get_option('igzf_zoho_token');
  $zoho_refresh           = $zoho->zgfgetRefreshToken($zoho_token->access_token);
  $getlayouts             = $zoho->zgfgetCrmLayout($modulename,$zoho_token->access_token);
  $zohocrmlayoutdata      = "zoho_crm_module_layout_".$modulename;
  $getfields              = $zoho->zgfgetCrmFields($modulename,$zoho_token->access_token);
  $zohocrmfieldata        = "zoho_crm_module_data".$modulename;
  update_option($zohocrmfieldata,$getfields);
  update_option($zohocrmlayoutdata,$getlayouts);
  $selectionOption        = "<option value=''>Select layout</option>";
  foreach ($getlayouts['layouts'] as $value) {
      $layoutname         = $value['name'];
      $layoutid           = $value['id'];
      $selectionOption    = $selectionOption ."<option value='".$layoutid."'>".$layoutname."</option>";
  }
printf($selectionOption);
wp_die();
}
add_action( 'wp_ajax_zgfmodulelist', 'zgfmodulelist' );
function zgfmodulelist() {
  $zoho_token           = get_option('igzf_zoho_token');
  $zoho                 = new ZGF_ZOHO_API('','','');
  $zoho_refresh         = $zoho->zgfgetRefreshToken($zoho_token->access_token);
  $modulename           = sanitize_text_field($_POST['modulename']);
  $getmodules           = $zoho->zgfgetModules($zoho_refresh->access_token);
  $selectionOption      = "<option value=''>Select Module</option>";
  foreach ($getmodules as $value) {
    $selectionOption    = $selectionOption ."<option value='".$value."'>".$value."</option>";
  }
printf($selectionOption);
  wp_die();
}
add_action( 'wp_ajax_zgfgetuserlist', 'zgfgetuserlist' );
function zgfgetuserlist() {
  $zoho_token           = get_option('igzf_zoho_token');
  $zoho                 = new ZGF_ZOHO_API('','','');
  $zoho_refresh         = $zoho->zgfgetRefreshToken($zoho_token->access_token);
  $usertype             = sanitize_text_field($_REQUEST['usertype']);
  $getuserlist          = $zoho->zgfgetZohoUserList($zoho_token->access_token);
  $selectionOption      = "";
  foreach ($getuserlist['users'] as $value) {
      $usernname        = $value['full_name'];
      $userid           = $value['id'];
      $useremail        = $value['email'];
      $userdetail       = $usernname."(".$useremail.")";
      $selectionOption  = $selectionOption ."<option value='".$userid."'>".$userdetail."</option>";
  }
  printf($selectionOption);
  wp_die();
}

add_action( 'wp_ajax_zgfdeleteForm', 'zgfdeleteForm' );
function zgfdeleteForm() {
    $form_id = sanitize_text_field($_POST['formId']);
    if(!empty($form_id)){
      global $wpdb;
      $table_name = $wpdb->prefix . 'igzfformdata';
      $response = $wpdb->query( "DELETE  FROM {$table_name} WHERE form_id = '{$form_id}'" );
      delete_option( 'igzf_zoho_fields_'.$form_id );
      echo _e("Successfully Deleted");
    }
    wp_die();
}

if ( ! function_exists( 'zgf_zoho_integration' ) ) {
    add_action( 'gform_after_submission', 'zgf_zoho_integration', 20, 2 );
    function zgf_zoho_integration( $entry, $gf ) {

            $form_id = 0;
            if ( isset( $gf['id'] ) ) {
                $form_id = intval( $gf['id'] );
            }

            if ( $form_id ) {
                $gf_zoho = get_option( 'igzf_data_form_'.$form_id );

                if ( $gf_zoho ) {
                    $posted_data = $entry;
                    $gf_zoho_fields = get_option( 'igzf_zoho_fields_'.$form_id );
                    if ( $gf_zoho_fields != null ) {
                        $data = array();
                        $attachment_fields = array();
                        $profileimage = '';
                        $tags = '';
                        foreach ( $gf_zoho_fields as $gf_field_name => $gf_zoho_field ) {
                            if ( isset( $gf_zoho_field['key'] ) && $gf_zoho_field['key'] ) {
                                $zoho_field_name = $gf_zoho_field['key'];
                                if ( is_array( $posted_data[$gf_field_name] ) ) {
                                    $posted_data[$gf_field_name] = implode( ';', $posted_data[$gf_field_name] );
                                }

                                if ( isset( $gf_zoho_field['type'] ) && $gf_zoho_field['type'] == 'boolean' ) {
                                    if ( $posted_data[$gf_field_name] == '1' || $posted_data[$gf_field_name] == 'True' ) {
                                        $posted_data[$gf_field_name] = 'true';
                                    } else {
                                        $posted_data[$gf_field_name] = 'false';
                                    }
                                } else if ( isset( $gf_zoho_field['type'] ) && $gf_zoho_field['type'] == 'date' ) {
                                    $posted_data[$gf_field_name] = date( 'Y-m-d', strtotime( $posted_data[$gf_field_name] ) );
                                } else if ( isset( $gf_zoho_field['type'] ) && $gf_zoho_field['type'] == 'datetime' ) {
                                    $posted_data[$gf_field_name] = date( 'c', strtotime( $posted_data[$gf_field_name] ) );
                                } else if ( isset( $gf_zoho_field['type'] ) && $gf_zoho_field['type'] == 'integer' ) {
                                    $posted_data[$gf_field_name] = (int) $posted_data[$gf_field_name];
                                }
                                if ( $zoho_field_name == 'Record_Image1' ) {
                                    $attachment_fields[] = $gf_field_name;
                                } else if ( $zoho_field_name == 'Record_Image' ) {
                                    $profileimage = $gf_field_name;
                                } else if ( $zoho_field_name == 'Tag' ) {
                                    $tags = $posted_data[$gf_field_name];
                                    $tags = str_replace( ';', ',', $tags );
                                } else if ( isset( $gf_zoho_field['type'] ) && $gf_zoho_field['type'] == 'multiselectpicklist' ) {
                                    $data[$zoho_field_name] = explode( ';', $posted_data[$gf_field_name] );
                                } else {
                                    $data[$zoho_field_name] = ( isset( $posted_data[$gf_field_name] ) ? strip_tags( $posted_data[$gf_field_name] ) : '' );
                                }
                            }
                        }



                        $mapping_data= unserialize($gf_zoho);
                        if ( $mapping_data != null ) {
                            if ( isset( $mapping_data['assigned'] ) ) {
                                $data['Owner'] = array(
                                    'id'    => $mapping_data['assigned'],
                                );

                            }
                            $layout = $mapping_data['layout'];
                            if ( $layout ) {
                                $data['Layout'] = $layout;
                            }
                            $zoho_token  = get_option('igzf_zoho_token');
                            $module = $mapping_data['module'];
                            $domain = get_option( 'igzf_zoho_domain' );
                            $domain = rtrim( $domain, '/' );
                            $client_id = get_option( 'igzf_zoho_client_id' );
                            $client_secret = get_option( 'igzf_zoho_client_secret' );
                            $zoho = new ZGF_ZOHO_API( $domain, $client_id, $client_secret );
                            $zoho_refresh         = $zoho->zgfgetRefreshToken($zoho_token->access_token);
                            $action = get_option( 'igzf_zoho_action_'.$form_id );
                            $ids = array();
                            $record = $zoho->zgfinsertRecord( $zoho_token->access_token, $module, $data, $form_id );

                            if ( isset( $record->data[0]->details->id ) ) {
                                $ids[] = $record->data[0]->details->id;
                            }
                            if ( $tags && $ids != null ) {
                              foreach ( $ids as $id ) {
                                  $zoho->zgfinsertTags( $zoho_token->access_token, $module, $id, $tags );
                              }
                          }
                          if ( $profileimage && $ids != null ) {
                              if ( isset( $posted_data[$profileimage] ) && $posted_data[$profileimage] ) {
                                  $file = $posted_data[$profileimage];
                                  $file = str_replace( site_url( '/' ), ABSPATH, $file );
                                  if (function_exists('curl_file_create')) {
                                      $file = curl_file_create( $file );
                                  } else {
                                      $file = '@' . realpath( $file );
                                  }

                                  $profileimage_data = array( 'file' => $file );
                                  foreach ( $ids as $id ) {
                                      $zoho->zgfuploadPhoto($zoho_token->access_token, $module, $profileimage_data, $id );
                                  }
                              }
                          }
                          if ( $attachment_fields != null && $ids != null ) {
                                foreach ( $ids as $id ) {
                                    foreach ( $attachment_fields as $attachment_field ) {
                                        $files = json_decode( $posted_data[$attachment_field] );
                                        if ( $files != null ) {
                                            foreach ( $files as $file ) {
                                                $file = str_replace( site_url( '/' ), ABSPATH, $file );
                                                if ( $file ) {
                                                    $file = str_replace( site_url( '/' ), ABSPATH, $file );
                                                    if (function_exists('curl_file_create')) {
                                                        $file = curl_file_create( $file );
                                                    } else {
                                                        $file = '@' . realpath( $file );
                                                    }

                                                    $file_data = array( 'file' => $file );
                                                    $zoho->zgfuploadFile( $zoho_token->access_token, $module, $file_data, $id );
                                                }
                                            }
                                        } else {
                                            $file = $posted_data[$attachment_field];
                                            if ( $file ) {
                                                $file = str_replace( site_url( '/' ), ABSPATH, $file );
                                                if (function_exists('curl_file_create')) {
                                                    $file = curl_file_create( $file );
                                                } else {
                                                    $file = '@' . realpath( $file );
                                                }

                                                $file_data = array( 'file' => $file );
                                                $zoho->zgfuploadFile( $zoho_token->access_token, $module, $file_data, $id );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

    }
}
?>
