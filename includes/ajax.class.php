<?php

namespace AMW\Plugin;

/**
 * Plugins custom settings page that adheres to wp standard
 * see: https://developer.wordpress.org/plugins/settings/custom-settings-page/
 *
 * @since   1.0
 */

defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Ajax
{
  /**
   * The single instance of the class.
   *
   * @since 1.0
   */
  protected static $_instance = null;

  /**
   * Class constructor.
   *
   * @since 1.0.0
   */
  public function __construct()
  {
    // Save settings via ajax 
    add_action("wp_ajax_save_settings", array($this, 'save_settings'));

    // Generate Summary via ajax 
    add_action('wp_ajax_spai_generate_summary', array($this, 'ajax_generate_summary'));

    // Get team by season
    add_action('wp_ajax_get_season_teams', array($this, 'get_season_teams'));
  }

  /**
   * Main Instance.
   * 
   * @since 1.0
   */
  public static function instance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * Save settings ajax handler
   * 
   * @since 1.0
   */
  public function save_settings()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      $data = json_decode(stripslashes($_POST['data']), true);

      // if ($data['amw_time']) {
      //   $dt = new \DateTime($data['amw_time']);
      //   $data['amw_time'] = $dt->format('H:i');
      // }


      error_log(print_r($data, true));
      // update_option('amw_options', $data);

      wp_send_json(array(
        'status' => 'success',
        'data' => $data,
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }
  }

  public function get_season_teams()
  {
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {
      global $amw;

      $data = array();
      $season = sanitize_text_field($_POST['season']);

      if ($season) {
        $data = $amw->scripts->get_all_teams($season);
      }
      error_log(print_r($season, true));
      error_log(print_r($data, true));

      wp_send_json(array(
        'status' => 'success',
        'data' => $data,
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }
  }
}
