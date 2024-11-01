<?php
/*
  Plugin Name: Upload large file
  Description: Upload larger size files with one click.
  Author: reevs02
  Version: 2.0.0
  License: GPL2
  Text Domain: upload-large-file
 */

// main plugin class
class Wp_upload_large_file
{
  static function init()
  {
    if (is_admin()) {
      add_action('admin_menu', array(__CLASS__, 'ulf_upload_max_file_size_add_pages'));
      add_filter('install_plugins_table_api_args_featured', array(__CLASS__, 'ulf_featured_plugins_tab'));
      add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'ulf_plugin_action_links'));
      add_filter('plugin_row_meta', array(__CLASS__, 'ulf_plugin_meta_links'), 10, 2);
      add_filter('ulf_admin_footer_text', array(__CLASS__, 'ulf_admin_footer_text'));
      
      if (isset($_POST['upload_max_file_size_field']) 
          && wp_verify_nonce($_POST['upload_max_file_size_nonce'], 'upload_max_file_size_action')
          && is_numeric($_POST['upload_max_file_size_field'])) {
          $max_size = (int) $_POST['upload_max_file_size_field'] * 1024 * 1024;
          update_option('max_file_size', $max_size);
          wp_safe_redirect(admin_url('?page=upload_max_file_size&max-size-updated=true'));
      }
    }
      
    add_filter('upload_size_limit', array(__CLASS__, 'ulf_upload_max_increase_upload'));
  } // init
  
  
  // get plugin version from header
  static function ulf_get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');

    return $plugin_data['version'];
  } // get_plugin_version
  
  
  // test if we're on plugin's page
  static function ulf_is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'toplevel_page_upload_max_file_size') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page
  
  
  // add settings link to plugins page
  static function ulf_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=upload_max_file_size') . '" title="Adjust Max File Upload Size Settings">Settings</a>';

    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // add links to plugin's description in plugins table
  static function ulf_plugin_meta_links($links, $file) {
    $support_link = '<a target="_blank" href="mailto:reevs2020@hotmail.com" title="Get help">Support</a>';


    if ($file == plugin_basename(__FILE__)) {
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links
  
  
  // additional powered by text in admin footer; only on plugin's page
  static function ulf_admin_footer_text($text) {
    if (!self::ulf_is_plugin_page()) {
      return $text;
    }

    $text = '<i>Upload Large File v' . self::ulf_get_plugin_version() . ' ' . $text;

    return $text;
  } // admin_footer_text


  /**
   * Add menu pages
   *
   * @since 1.4
   * 
   * @return null
   * 
   */
  static function ulf_upload_max_file_size_add_pages()
  {
      // Add a new menu on main menu
      add_menu_page('Increase Max Upload File Size', 'Upload Large File', 'manage_options', 'upload_max_file_size', array(__CLASS__, 'ulf_upload_max_file_size_dash'), 'dashicons-upload');
  } // upload_max_file_size_add_pages


  /**
   * Get closest value from array
   *
   * @since 1.4
   * 
   * @param int search value
   * @param array to find closest value in
   * 
   * @return int in MB, closest value
   * 
   */
  static function ulf_get_closest($search, $arr)
  {
      $closest = null;
      foreach ($arr as $item) {
          if ($closest === null || abs($search - $closest) > abs($item - $search)) {
              $closest = $item;
          }
      }
      return $closest;
  } // get_closest


  /**
   * Dashboard Page
   *
   * @since 1.4
   * 
   * @return null
   * 
   */
  static function ulf_upload_max_file_size_dash()
  {
    echo '<style>';
    echo '.wrap, .wrap p { font-size: 15px; } .form-table th { width: 230px; }';
    echo '.gray-box { display: inline-block; padding: 15px; background-color: #e6e6e6; }';
    echo '</style>';
    
    if (isset($_GET['max-size-updated'])) {
        echo '<div class="notice-success notice is-dismissible"><p>Max Upload Size Saved!</p></div>';
    }

    $ini_size = ini_get('upload_max_filesize');
    if (!$ini_size) {
        $ini_size = 'unknown';
    } elseif (is_numeric($ini_size)) {
        $ini_size .= ' bytes';
    } else {
        $ini_size .= 'B';
    }

    $wp_size = wp_max_upload_size();
    if (!$wp_size) {
        $wp_size = 'unknown';
    } else {
        $wp_size = round(($wp_size / 1024 / 1024));
        $wp_size = $wp_size == 1024 ? '1GB' : $wp_size . 'MB';
    }

    $max_size = get_option('max_file_size');
    if (!$max_size) {
        $max_size = 64 * 1024 * 1024;
    }
    $max_size = $max_size / 1024 / 1024;


    $upload_sizes = array(2, 512, 1024);

    $current_max_size = self::ulf_get_closest($max_size, $upload_sizes);

    echo '<div class="wrap">';
    echo '<h1><span class="dashicons dashicons-upload" style="font-size: inherit; line-height: unset;"></span> Upload Large File Settings</h1><br>';

    echo '<p class="gray-box"><b>Do you like the plugin?</b>: If yes, give us a <a href="https://wordpress.org/plugins/upload-large-file/">five star rating</a>. If no, <u class="let_us_know">let us know</u><a class="form_show"></a> how we can make it better.';

    echo '<p>Maximum upload file size, set by your hosting provider: ' . $ini_size . '.<br>';
    echo 'Maximum upload file size, set by WordPress: ' . $wp_size . '.</p>';
    
    echo '<form method="post">';
    settings_fields("header_section");
    echo '<table class="form-table"><tbody><tr><th scope="row"><label for="upload_max_file_size_field">Choose Maximum Upload File Size</label></th><td>';
    echo '<select id="upload_max_file_size_field" name="upload_max_file_size_field">';
    foreach ($upload_sizes as $size) {
        echo '<option value="' . $size . '" ' . ($size == $current_max_size ? 'selected' : '') . '>' . ($size == 1024 ? '1GB' : $size . 'MB') . '</option>';
    }
    echo '</select>';
    echo '</td></tr></tbody></table>';
    echo wp_nonce_field('upload_max_file_size_action', 'upload_max_file_size_nonce');
    submit_button();
    echo '</form>';	
	echo '<br>';
	echo '<a href="https://shorturl.at/cnKO1">Buy Premium plugin</a> to Increase or Limit any specific Maxiumum upload file size, upto 1000 TB (Terabytes).<br>';
	echo '<b>Note:</b> If there is need to increase Maximum upload file size, set by your hosting provider, please contact your hosting provider.';
	echo '<br>';

	echo '<div class="let_us_know_form" id="let_us_know_form">';
	echo '<Form action ="" method="POST">';
	echo 'Your name: <input name="nam" type="text"><br>';
	echo 'Your email: <input name="email" type="email"><br>';
	echo 'Feedback: <textarea name="message" style="position: relative; left: 8px; width: 166px;"></textarea><br>';
	echo '<input type="submit" class="accept_feedback button button-primary" style="position: relative; left: 130px;">';
	echo '</div>';
	
	if(isset($_POST['nam']) && isset($_POST['email']) && isset($_POST['message'])){
		$name = $_POST['name'];
		$email = $_POST['email'];
		$message = $_POST['message'];
		$send_message = "Message received from: ".$email."<br><br>Message: ".$message;
		$subject = "Feedback received from ". $name ." for 'Upload Large File' plugin.";
		$to = 'reevs2020@hotmail.com';
		wp_mail($to, $subject, $send_message);
	}
    

    echo '</div>';
  } // upload_max_file_size_dash


  /**
   * Filter to increase max_file_size
   *
   * @since 1.4
   * 
   * @return int max_size in bytes
   * 
   */
  static function ulf_upload_max_increase_upload()
  {
      $max_size = (int) get_option('max_file_size');
      if (!$max_size) {
          $max_size = 64 * 1024 * 1024;
      }

      return $max_size;
  } // upload_max_increase_upload
  
  
  // add our plugins to recommended list
  static function ulf_plugins_api_result($res, $action, $args) {
    remove_filter('ulf_plugins_api_result', array(__CLASS__, 'ulf_plugins_api_result'), 10, 3);

    $res = self::ulf_add_plugin_favs('under-construction-page', $res);
    $res = self::ulf_add_plugin_favs('wp-reset', $res);
    $res = self::ulf_add_plugin_favs('eps-301-redirects', $res);

    return $res;
  } // plugins_api_result
  
  
  // helper function for adding plugins to fav list
  static function ulf_featured_plugins_tab($args) {
    add_filter('ulf_plugins_api_result', array(__CLASS__, 'ulf_plugins_api_result'), 10, 3);

    return $args;
  } // featured_plugins_tab


  // add single plugin to list of favs
  static function ulf_add_plugin_favs($plugin_slug, $res) {
    if (!empty($res->plugins) && is_array($res->plugins)) {
      foreach ($res->plugins as $plugin) {
        if (is_object($plugin) && !empty($plugin->slug) && $plugin->slug == $plugin_slug) {
          return $res;
        }
      } // foreach
    }

    if ($plugin_info = get_transient('wf-plugin-info-' . $plugin_slug)) {
      array_unshift($res->plugins, $plugin_info);
    } else {
      $plugin_info = plugins_api('plugin_information', array(
        'slug'   => $plugin_slug,
        'is_ssl' => is_ssl(),
        'fields' => array(
            'banners'           => true,
            'reviews'           => true,
            'downloaded'        => true,
            'active_installs'   => true,
            'icons'             => true,
            'short_description' => true,
        )
      ));
      if (!is_wp_error($plugin_info)) {
        $res->plugins[] = $plugin_info;
        set_transient('wf-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7);
      }
    }

    return $res;
  } // add_plugin_favs
} // class Wp_upload_large_file

add_action('init', array('Wp_upload_large_file', 'init'));

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
jQuery(document).ready(function(){
  jQuery(".accept_feedback").click(function(){
    alert("Thank you for your Feedback, it has been noted.");
  });
});

jQuery(document).ready(function(){
  jQuery(".let_us_know").click(function(){
	existingdiv1 = document.getElementById( "let_us_know_form" );
	jQuery(".let_us_know_form").css("display","block");
	jQuery( ".form_show" ).html( existingdiv1 );
  })
});
</script>
<style>
.let_us_know_form {
	position: relative;
    left: 93px;
    margin-top: 3px;
    border: solid 2px #007cba;
    border-radius: 5px;
    width: 246px;
    padding: 15px;
    padding-bottom: 0px;
	display:none;
}
.let_us_know{
	color:#0073aa;
}
.let_us_know:hover {
 cursor:pointer;
}
</style>
