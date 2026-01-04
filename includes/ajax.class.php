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


  /**
   * AJAX handler.
   */
  public function ajax_generate_summary()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {
      @set_time_limit(0);

      $post_type = array('sp_event', 'sp_team', 'sp_player', 'sp_staff', 'sp_official');
      $batch_size = 100;

      while (true) {
        $posts = get_posts([
          'post_type'      => $post_type,
          'post_status'    => 'any',
          'numberposts'    => $batch_size,
          'fields'         => 'ids',
        ]);

        if (empty($posts)) break;

        foreach ($posts as $post_id) {
          wp_delete_post($post_id, true); // force delete
        }

        // optional: short sleep to prevent timeout
        // sleep(1);
      }

      $taxonomies = array('sp_season', 'sp_league', 'sp_venue', 'sp_role', 'sp_duty');

      foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
          'taxonomy'   => $taxonomy,
          'hide_empty' => false,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
          foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
          }
        }
      }

      wp_send_json(array(
        'status' => 'success',
        'data' => array(),
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }

    $summary = $this->call_openai($api_key, $prompt);

    if (is_wp_error($summary)) {
      wp_send_json_error(array('message' => $summary->get_error_message()), 500);
    }

    $post_id = $this->create_draft_post($event_id, $summary);

    if (is_wp_error($post_id)) {
      wp_send_json_error(array('message' => $post_id->get_error_message()), 500);
    }

    $response = array(
      'message'  => __('Draft created successfully.', 'ai-match-writer'),
      'postId'   => $post_id,
      'editLink' => get_edit_post_link($post_id, ''),
    );

    wp_send_json_success($response);
  }
}
