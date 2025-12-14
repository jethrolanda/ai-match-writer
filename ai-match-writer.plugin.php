<?php
if (!defined('ABSPATH')) {
	exit;
}
// Exit if accessed directly


class AI_Match_Writer
{

	/*
    |------------------------------------------------------------------------------------------------------------------
    | Class Members
    |------------------------------------------------------------------------------------------------------------------
     */
	private static $_instance;

	public $scripts;
	public $ajax;
	public $settings;
	public $cron;
	public $api;

	const VERSION = '1.0';

	/*
  |------------------------------------------------------------------------------------------------------------------
  | Mesc Functions
  |------------------------------------------------------------------------------------------------------------------
  */

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{

		$this->scripts = \AMR\Plugin\Scripts::instance();
		$this->ajax = \AMR\Plugin\Ajax::instance();
		$this->settings = \AMR\Plugin\Settings::instance();
		$this->api = \AMR\Plugin\Api::instance();
		$this->cron = \AMR\Plugin\Cron::instance();


		// Register Activation Hook
		register_activation_hook(AMR_PLUGIN_DIR . 'ai-match-writer.php', array($this, 'activate'));

		// Register Deactivation Hook
		register_deactivation_hook(AMR_PLUGIN_DIR . 'ai-match-writer.php', array($this, 'deactivate'));
	}

	/**
	 * Singleton Pattern.
	 *
	 * @since 1.0.0
	 */
	public static function instance()
	{

		if (!self::$_instance instanceof self) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}


	/**
	 * Trigger on activation
	 *
	 * @since 1.0.0
	 */
	public function activate()
	{

		// Check if ActionSheduler class exists
		// This class exist in WooCommerce or ActionScheduler plugin
		if (class_exists('ActionScheduler')) {
			// Avoid scheduling duplicate recurring action
			if (!as_next_scheduled_action('rugbyexplorer_scheduled_events_update')) {
				as_schedule_recurring_action(time(), DAY_IN_SECONDS, 'rugbyexplorer_scheduled_events_update');
			}
		}
	}

	/**
	 * Trigger on deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate()
	{
		wp_clear_scheduled_hook('rugbyexplorer_schedule_update');
	}
}
