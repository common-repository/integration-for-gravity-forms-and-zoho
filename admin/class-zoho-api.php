<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}
if ( ! class_exists( 'ZGF_ZOHO_API' ) ) {
    class ZGF_ZOHO_API {
      var $zoho_url;
      var $zoho_client_id;
      var $zoho_client_secret;
      function __construct( $zoho_url, $zoho_client_id, $zoho_client_secret) {
          $this->zoho_url            = "https://www.zohoapis.com";
          $this->zoho_client_id      = get_option('igzf_zoho_client_id');
          $this->zoho_client_secret  = get_option('igzf_zoho_client_secret');
          $this->token               = get_option('igzf_zoho_token');
          $this->datacenter          = get_option('igzf_zoho_datacenter');
          $this->zoho_url            = "https://www.zohoapis".$this->datacenter;
          $this->accounts_url        = "https://accounts.zoho".$this->datacenter;

      }
      function zgfgetToken($code,$redirection_uri) {
          $data = array(
              'client_id'     => $this->zoho_client_id,
              'client_secret' => $this->zoho_client_secret,
              'code'          => $code,
              'grant_type'    => 'authorization_code',
              'redirect_uri'  => $redirection_uri,
          );
          $zoho_url           = $this->accounts_url.'/oauth/v2/token';
          $args = array(
              'timeout'       => 30,
              'httpversion'   => '1.0',
              'body'          => $data,
              'sslverify'     => false,
          );

          $wp_remote_response = wp_remote_post( $zoho_url, $args );
          $json_response = '';
          if ( ! is_wp_error( $wp_remote_response ) ) {
              $json_response = $wp_remote_response['body'];
          }
          $response          = json_decode( $json_response );
          return $response;
      }
      function zgfgetRefreshToken( ) {
          $refresh_token            = $this->token->refresh_token;
          $access_token             = $this->token->access_token;
          $data = array(
              'client_id'     => $this->zoho_client_id,
              'client_secret' => $this->zoho_client_secret,
              'grant_type'    => 'refresh_token',
              'refresh_token' => $refresh_token,
          );

          $url = $this->accounts_url.'/oauth/v2/token';
          $args = array(
              'timeout'       => 30,
              'httpversion'   => '1.0',
              'body'          => $data,
              'sslverify'     => false,
          );
          $wp_remote_response = wp_remote_post( $url, $args );
          $json_response = '';
          if ( ! is_wp_error( $wp_remote_response ) ) {
              $json_response = $wp_remote_response['body'];
          }

          $response = json_decode( $json_response );
          $zoho_old_token = get_option( 'igzf_zoho' );
            if ( isset( $response->access_token ) ) {
              $resultdata = array(
                  'client_id'     => $this->zoho_client_id,
                  'client_secret' => $this->zoho_client_secret,
                  'refresh_token' => $refresh_token,
                  'access_token' => $response->access_token,
              );
              $token = $resultdata;
              update_option( 'igzf_zoho_token', json_decode(json_encode($token)) );

          }


          return $response;
      }
      function zgfinsertRecord( $token, $module, $data, $form_id ) {
            $filter_data = array();
            foreach ( $data as $key => $value ) {
                if ( $value == "true" ) {
                    $filter_data[$key] = true;
                } else if ( $value == "false" ) {
                    $filter_data[$key] = false;
                } else {
                    $filter_data[$key] = $value;
                }
            }

            $data = $filter_data;
            $url = $this->zoho_url.'/crm/v2/'.$module;
            $header = array(
                'Authorization' => 'Zoho-oauthtoken '.$token,
                'Content-Type'  => 'application/json',
            );
            $data = array(
                'data'  => array(
                    $data,
                ),
            );


            $data = json_encode( $data );
            $args = array(
                'timeout'       => 30,
                'httpversion'   => '1.0',
                'headers'       => $header,
                'body'          => $data,
                'sslverify'     => false,
            );
            $wp_remote_response = wp_remote_post( $url, $args );
            $json_response = '';
            if ( ! is_wp_error( $wp_remote_response ) ) {
                $json_response = $wp_remote_response['body'];
            }

            $response = json_decode( $json_response );
            if ( isset( $response->data[0]->status ) && $response->data[0]->status == 'error' ) {
                $log = "Form ID: ".$form_id."\n";
                $log .= "Error Code: ".$response->data[0]->code."\n";
                $log .= "Message: ".$response->data[0]->message."\n";
                $log .= "Response: ".$json_response."\n";
                $log .= "Date: ".date( 'Y-m-d H:i:s' )."\n\n";

                $send_to = get_option( 'gf_zoho_notification_send_to' );
                if ( $send_to ) {
                    $to = $send_to;
                    $subject = get_option( 'gf_zoho_notification_subject' );
                    $body = "Form ID: ".$form_id."<br>";
                    $body .= "Error Code: ".$response->data[0]->code."<br>";
                    $body .= "Message: ".$response->data[0]->message."<br>";
                    $body .= "Response: ".$json_response."\n";
                    $body .= "Date: ".date( 'Y-m-d H:i:s' );
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                    );
                }

                file_put_contents( ZGF_ZOHO_PLUGIN_PATH.'debug.log', $log, FILE_APPEND );
            }

            return $response;
        }
        function zgfinsertTags( $token, $module, $record_id, $tags ) {

            $url = $this->zoho_url.'/crm/v2/'.$module.'/'.$record_id.'/actions/add_tags?tag_names='.$tags;
            $header = array(
                'Authorization' => 'Zoho-oauthtoken '.$token,
            );
            $args = array(
                'timeout'       => 30,
                'httpversion'   => '1.0',
                'headers'       => $header,
                'body'          => array(),
                'sslverify'     => false,
            );
            $wp_remote_response = wp_remote_post( $url, $args );
            $json_response = '';
            if ( ! is_wp_error( $wp_remote_response ) ) {
                $json_response = $wp_remote_response['body'];
            }

            $response = json_decode( $json_response );
            if ( isset( $response->status ) && $response->status == 'error' ) {
                $log = "Error Code: ".$response->code."\n";
                $log .= "Message: ".$response->message."\n";
                $log .= "Response: ".$json_response."\n";
                $log .= "Date: ".date( 'Y-m-d H:i:s' )."\n\n";

                $send_to = get_option( 'gf_zoho_notification_send_to' );
                if ( $send_to ) {
                    $to = $send_to;
                    $subject = get_option( 'gf_zoho_notification_subject' );
                    $body = "Error Code: ".$response->code."<br>";
                    $body .= "Message: ".$response->message."<br>";
                    $body .= "Response: ".$json_response."\n";
                    $body .= "Date: ".date( 'Y-m-d H:i:s' );
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                    );
                }

                file_put_contents( ZGF_ZOHO_PLUGIN_PATH.'debug.log', $log, FILE_APPEND );
            }

            return $response;
        }
        function zgfuploadPhoto( $token, $module, $data, $record_id ) {

            $url = $this->zoho_url.'/crm/v2/'.$module.'/'.$record_id.'/photo';
            $header = array(
                'Authorization' => 'Zoho-oauthtoken '.$token,
            );
            $args = array(
                'timeout'       => 30,
                'httpversion'   => '1.0',
                'headers'       => $header,
                'body'          => array(),
                'sslverify'     => false,
            );
            $json_response = wp_remote_post( $url, $data );
            $response = json_decode( $json_response );
            if ( isset( $response->status ) && $response->status == 'error' ) {
                $log = "Error Code: ".$response->code."\n";
                $log .= "Message: ".$response->message."\n";
                $log .= "Response: ".$json_response."\n";
                $log .= "Date: ".date( 'Y-m-d H:i:s' )."\n\n";

                $send_to = get_option( 'gf_zoho_notification_send_to' );
                if ( $send_to ) {
                    $to = $send_to;
                    $subject = get_option( 'gf_zoho_notification_subject' );
                    $body = "Error Code: ".$response->code."<br>";
                    $body .= "Message: ".$response->message."<br>";
                    $body .= "Response: ".$json_response."\n";
                    $body .= "Date: ".date( 'Y-m-d H:i:s' );
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                    );
                }

                file_put_contents( ZGF_ZOHO_PLUGIN_PATH.'debug.log', $log, FILE_APPEND );
            }

            return $response;
        }
        function zgfuploadFile( $token, $module, $data, $record_id ) {
            $url = $this->zoho_url.'/crm/v2/'.$module.'/'.$record_id.'/Attachments';
            $header = array(
                'Authorization' => 'Zoho-oauthtoken '.$token,
            );
            $args = array(
                'timeout'       => 30,
                'httpversion'   => '1.0',
                'headers'       => $header,
                'body'          => array(),
                'sslverify'     => false,
            );
            $json_response = wp_remote_post( $url, $data );
            $response = json_decode( $json_response );
            if ( isset( $response->status ) && $response->status == 'error' ) {
                $log = "Error Code: ".$response->code."\n";
                $log .= "Message: ".$response->message."\n";
                $log .= "Response: ".$json_response."\n";
                $log .= "Date: ".date( 'Y-m-d H:i:s' )."\n\n";

                $send_to = get_option( 'gf_zoho_notification_send_to' );
                if ( $send_to ) {
                    $to = $send_to;
                    $subject = get_option( 'gf_zoho_notification_subject' );
                    $body = "Error Code: ".$response->code."<br>";
                    $body .= "Message: ".$response->message."<br>";
                    $body .= "Response: ".$json_response."\n";
                    $body .= "Date: ".date( 'Y-m-d H:i:s' );
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                    );
                }
                file_put_contents( ZGF_ZOHO_PLUGIN_PATH.'debug.log', $log, FILE_APPEND );
            }

            return $response;
        }
      function zgfgetModules($token){
        global $wpdb;
        $zfformbaseurl      = $this->zoho_url."/crm/v2/settings/modules";
        $args = array(
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        'authorization' => 'Zoho-oauthtoken ' . $token
                    ),
                //'cookies' => array()
                );
       $zfformsresponse     = wp_remote_get( $zfformbaseurl, $args );
       $responsedata = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
       $moduledataarray     = array();
       if(isset($responsedata['status']) !='error'){
         foreach($responsedata['modules'] as $modulevalue){
           if($modulevalue['api_supported'] == 1){
             $apiname                       = $modulevalue['api_name'];
             $moduledataarray[$apiname]     = $apiname;
            }
         }
         update_option('zoho_module_data',$moduledataarray);
     }
     return $moduledataarray;
    }
    public function zgfgetCrmLayout($modulename,$token){
          $url   = $this->zoho_url."/crm/v2/settings/layouts?module=".$modulename ;
          $args  = array(
          'timeout' => '5',
          'redirection' => '5',
          'httpversion' => '1.0',
          'blocking' => true,
          'headers' => array(
                  'authorization' => 'Zoho-oauthtoken ' . $token
           ),
          'cookies' => array()
          );
         $zfformsresponse    =  wp_remote_get( $url, $args );
         $result_array       = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
         return $result_array;
    }
    public function zgfgetCrmFields($modulename,$token){
          $url = $this->zoho_url."/crm/v2/settings/fields?module=".$modulename ;
          $args = array(
          'timeout' => '5',
          'redirection' => '5',
          'httpversion' => '1.0',
          'blocking' => true,
          'headers' => array(
                  'authorization' => 'Zoho-oauthtoken ' . $token
           ),
          'cookies' => array()
          );
         $zfformsresponse =  wp_remote_get( $url, $args );
         $result_array = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
         $result_array['attachment_field'] = array(
                     'label'     => 'Attachments',
                     'type'      => 'relate',
                     'required'  => 0,
                 );

         return $result_array;
    }
    public function zgfgetZohoUserList($authtoken){
      $zfformbaseurl =  $this->zoho_url."/crm/v2/users?type=AllUsers";
        $args = array(
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(
                'authorization' => 'Zoho-oauthtoken ' . $authtoken
         ),
        'cookies' => array()
        );

      $zfformsresponse =  wp_remote_get( $zfformbaseurl, $args );
      $result_array = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
      update_option("zoho_users_list", $result_array);
      return $result_array;
    }
}
}
?>
