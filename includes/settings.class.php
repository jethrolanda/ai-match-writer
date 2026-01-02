<?php

namespace AMW\Plugin;


/** 
 * @since   1.0
 */

defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Settings
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
    add_action('admin_menu', array($this, 'register_menu'), 20);
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
   * Admin menu entry under RugbyExplorer.
   */
  public function register_menu()
  {
    add_submenu_page(
      'rugbyexplorer',
      __('AI Match Writer', 'ai-match-writer'),
      __('AI Match Writer', 'ai-match-writer'),
      'manage_options',
      'ai-match-writer-settings',
      array($this, 'render_settings_page')
    );
  }


  /**
   * Settings page.
   */
  public function render_settings_page()
  {
?>
    <div class="wrap">
      <h1><?php esc_html_e('AI Match Writer', 'ai-match-writer'); ?></h1>
      <div id="ai-match-writer"></div>
    </div>
<?php
  }
}
