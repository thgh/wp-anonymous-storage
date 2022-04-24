<?php

class AS_Database
{
    public static $storage_db_version = '1.0';

    public function table(){
      global $wpdb;
      $dbPrefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
      return $dbPrefix . 'anonymous_storage';
    }

    public function cleanup()
    {
        global $wpdb;

        $table_name = $this->table();

        $rows = $wpdb->get_results("SELECT * FROM {$table_name}");

        foreach ($rows as $row) {

            if ($row->type === '1' && ! file_exists(WP_PLUGIN_DIR . "/" . $row->package)) {
                $this->delete($row->id);
                continue;
            }

            if ($row->type === '2' && ! file_exists(get_theme_root() . "/" . $row->package)) {
                $this->delete($row->id);
                continue;
            }
        }
    }

    public function delete($id)
    {
        global $wpdb;
        $table_name = $this->table();

        $wpdb->delete($table_name, array('id' => sanitize_text_field($id)));
    }

    public function install()
    {
        global $wpdb;

        $table_name = $this->table();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            readkey varchar(32) NOT NULL,
            writekey varchar(32) NOT NULL,
            value json NOT NULL,
            author varchar(63) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  readkey (readkey),
            UNIQUE KEY  writekey (writekey),
            INDEX  updated_at (writekey)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function all(){
        global $wpdb;
        $table_name = $this->table();
        return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY updated_at DESC", OBJECT );
    }

    public function get($readkey){
        global $wpdb;
        $table_name = $this->table();
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE readkey = %s", $readkey);
        $row= $wpdb->get_row($sql);
        if ($row)        $row->value=json_decode($row->value);
        return $row;
    }
    
    public function post($input){
        global $wpdb;
        $table_name = $this->table();
        $value=is_string($input['value']) ? $input['value']: json_encode($input['value']);
        if (strlen($value) > 20000){
           throw new WP_Error( 'item_too_large', 'That is too much data!', array( 'status' => 400 ) );
        }
        $data=[
            'readkey'=>substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 20),
            'writekey'=>substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 20),
            'value'=> $value,
            'author'=>$input['author'],
        ];
        $row = $wpdb->insert($table_name, $data);

        $data['value']=json_decode($data['value']);
        return $data;
    }
    
    public function put($writekey, $input){
        global $wpdb;
        $table_name = $this->table();
        $value=is_string($input['value']) ? $input['value']: json_encode($input['value']);
        if (strlen($value) > 20000){
            throw new WP_Error( 'item_too_large', 'That is too much data!', array( 'status' => 400 ) );
         } 
         if (strlen($writekey) < 10){
            throw new WP_Error( 'invalid_writekey', 'That writekey is too small!', array( 'status' => 400 ) );
         }
        $updated = $wpdb->update($table_name, 
        ['value'=>$value],
         ['writekey'=> $writekey],['%s'],['%s']
          );

          $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE writekey = %s", $writekey);
          $row= $wpdb->get_row($sql);
          if ($row)        $row->value=json_decode($row->value);
          return $row;
    }


    public function uninstall()
    {
        global $wpdb;

        $table_name = $this->table();

        $sql = "DROP TABLE IF EXISTS $table_name;";

        $wpdb->query($sql);
    }
}