<?php
/**
 * Plugin Name
 *
 * @package           PluginPackage
 * @author            Your Name
 * @copyright         2019 Your Name or Company Name
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Anonymous Storage
 * Plugin URI:        https://github.com/thgh/anonymous-storage
 * Description:       Allow visitors to create a unique link to their data
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Thomas Ghysels
 * Author URI:        https://thomasg.be
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

require('database.php');


class AnonymousStorage {
  public function __construct(){
    $this->db = new AS_Database;
  }

  public function activate(){
    $this->db->install();

    wp_insert_post([
      'post_title' => 'Persoonlijke doelen',
      'post_content' => '<p>Dit zijn mijn leerdoelen:</p>',
      'post_type' => 'page',
      'post_name' => 'mijn-doelen',
      "post_status" => 'publish'
    ]);
  }

  public function deactivate(){
    $page = get_page_by_path('mijn-doelen', OBJECT, 'page');
    wp_delete_post($page->ID, true);
    wp_delete_post($page->id, true);
  }

  public function menu(){
    add_menu_page( 'Anonymous Storage', 'Bewaarde doelen', 'manage_options', 'anonymous-storage', [$this, 'admin'],'',50 );
  }

  public function admin(){
    $as_links=$this->db->all();
    require('admin-page.php');
  }

  public function rest(){
    register_rest_route(
      'anonymous-storage/v1',
      'item',
     [
      'methods' => 'POST',
      'callback' => [$this, 'post'] ,
      'permission_callback' => '__return_true'
     ]
    );
    register_rest_route(
      'anonymous-storage/v1',
      'item/(?P<writekey>.+)',
     [[
        'methods' => 'GET',
        'callback' => [$this, 'get'] ,
        'permission_callback' => '__return_true'
     ],[
      'methods' => 'PUT',
      'callback' => [$this, 'put'] ,
      'permission_callback' => '__return_true'
    ]]
    );
  }

  public function get($request){
    $readkey = $request->get_param('key');
    $ok = $this->db->get($readkey);
    if (empty($ok)){
      return new WP_Error( 'item_not_found', 'Invalid readkey', array( 'status' => 404 ) );
    }
    return rest_ensure_response([
      'readkey'=>$ok->readkey,
      'value'=>$ok->value,
      'author'=>$ok->author,
      'created_at'=>$ok->created_at,
      'updated_at'=>$ok->updated_at,
    ]);
  }

  public function post($request){
    // Get value
    $value = $request->get_param('value');
    if (empty($value)) {
      $value = $request->get_body();
    }

    // Get author
    $author = $request->get_param('author');
    if (empty($author) && is_array($value)) {
      $author = $value['author'];
    }
    if (empty($author) && is_object($value)) {
      $author = $value->author;
    }
    if (empty($author)) $author = '??';

    // Save!
    $ok = $this->db->post([
      'author'=>$author,
      'value'=>$value ?? [],
    ]);
    return rest_ensure_response($ok);
  }

  public function put($request){
    $writekey = $request->get_param('writekey');
    $value = $request->get_param('value');
      if (empty($value)) {
      $value = $request->get_body();
    }
    if (empty($value)) {
      return new WP_Error( 'empty_value', 'Invalid value', array( 'status' => 400 ) );
    }
    $ok = $this->db->put($writekey, [
      'value'=>$value ,
    ]);
    return rest_ensure_response($ok);
  }
}

$anonymous_storage = new AnonymousStorage;

// do_action('wppusher_register_dependency', $anonymous_storage);

register_activation_hook(__FILE__, [$anonymous_storage, 'activate']);
register_deactivation_hook(__FILE__, [$anonymous_storage, 'deactivate']);

add_action('admin_menu', [$anonymous_storage, 'menu']);
add_action('rest_api_init', [$anonymous_storage, 'rest']);