<?php

namespace AMW\Plugin;

/**
 * Scripts class
 *
 * @since   1.0
 */

defined('ABSPATH') || exit;

class Scripts
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

    // Load Backend CSS and JS
    add_action('admin_enqueue_scripts', array($this, 'backend_script_loader'));

    // Load Frontend CSS and JS
    add_action('wp_enqueue_scripts', array($this, 'frontend_script_loader'));
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
   * Load wp admin backend scripts
   *
   * @since 1.0
   */
  public function backend_script_loader()
  {
    $asset_file = AMW_JS_ROOT_DIR . 'ai-match-writer/build/index.asset.php';

    if (file_exists($asset_file) && isset($_GET['page']) && $_GET['page'] == "ai-match-writer-settings") {
      $asset = include $asset_file;
      $settings = get_option('amw_options');
      wp_register_script('amw-js', AMW_JS_ROOT_URL . 'ai-match-writer/build/index.js', $asset['dependencies'], $asset['version'], true);
      wp_localize_script('amw-js', 'amw_params', array(
        'rest_url'   => esc_url_raw(get_rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'settings' => $settings ? $settings : array(),
        'teams' => $this->getAllTeams()
      ));
      wp_register_style('amw-css', AMW_JS_ROOT_URL . 'ai-match-writer/build/style-index.css');

      wp_enqueue_style('amw-css');
      wp_enqueue_script('amw-js');
    }
  }

  public function getAllTeams()
  {
    $current_year = date('Y');

    $season = get_terms(array(
      'taxonomy'   => 'sp_season',
      'hide_empty' => false,
      'name__like' => $current_year,
      'number'     => 1
    ));

    $season_id = $season[0]->term_id ?? null;

    $teams = get_posts(array(
      'post_type'      => 'sp_team',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'orderby'        => 'title',
      'order'          => 'ASC',
      'tax_query'      => array(
        array(
          'taxonomy' => 'sp_season',
          'field'    => 'term_id',
          'terms'    => array($season_id),
        )
      )
    ));
    $teams_array = array();
    foreach ($teams as $team) {
      $seasons = wp_get_object_terms($team->ID, 'sp_season', array('fields' => 'names'));

      $seasons_played = "";
      if ($seasons) {
        $seasons_played = " (" . implode(", ", $seasons) . ")";
      }
      $teams_array[] = array(
        'label' => $team->post_title . $seasons_played,
        'value'   => $team->ID,
      );
    }
    return $teams_array;
  }

  /**
   * Load wp frontend scripts
   *
   * @since 1.0 
   */
  public function frontend_script_loader() {}
}
