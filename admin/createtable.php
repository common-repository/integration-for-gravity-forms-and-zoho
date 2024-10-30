<?php
global $wpdb;
$wpdb->query("
    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}igzfformdata (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `form_id` int(11) NOT NULL,
      `form_data` longtext NOT NULL,
       PRIMARY KEY ( id )
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
  ");

 ?>
