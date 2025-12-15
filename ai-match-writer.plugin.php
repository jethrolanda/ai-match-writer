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

		$this->scripts = \AMW\Plugin\Scripts::instance();
		$this->ajax = \AMW\Plugin\Ajax::instance();
		$this->settings = \AMW\Plugin\Settings::instance();
		$this->api = \AMW\Plugin\Api::instance();
		$this->cron = \AMW\Plugin\Cron::instance();


		// Register Activation Hook
		register_activation_hook(AMW_PLUGIN_DIR . 'ai-match-writer.php', array($this, 'activate'));

		// Register Deactivation Hook
		register_deactivation_hook(AMW_PLUGIN_DIR . 'ai-match-writer.php', array($this, 'deactivate'));
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

		$options = get_option('amw_options', array());
		if (empty($options)) {
			$options = array();
		}

		if (empty($options['amw_model'])) {
			$options['amw_model'] = 'gpt-4o-mini';
		}

		if (empty($options['amw_frequency'])) {
			$options['amw_frequency'] = 'daily';
		}

		if (empty($options['amw_time'])) {
			$timezone = wp_timezone();
			$time = DateTime::createFromFormat('H:i', '20:00', $timezone);
			$options['amw_time'] = $time->format('H:i'); // "20:00"
		}


		if (empty($options['amw_prompt_template'])) {
			$options['amw_prompt_template'] = "Write a 180-220 word match recap suitable for a WordPress post.\n\nEvent: {event_title}\nDate: {date}\nVenue: {venue}\nTeams: {teams}\nScore: {score}\nStatus: {status}\nRaw results data:\n{results_json}\n\nFocus on key moments, standout players, and context for fans. Keep it objective, lively, and avoid making up facts.";
		}
		error_log(print_r($options, true));
		update_option('amw_options', $options);

		// Check if ActionSheduler class exists
		// This class exist in WooCommerce or ActionScheduler plugin
		if (class_exists('ActionScheduler')) {
			// Avoid scheduling duplicate recurring action
			if (!as_next_scheduled_action('ai_match_writer_events_checker')) {
				as_schedule_recurring_action($this->get_next_8pm_timestamp(), DAY_IN_SECONDS, 'ai_match_writer_events_checker');
			}
		}
	}

	public function get_next_8pm_timestamp()
	{
		$timezone = wp_timezone(); // WP site timezone
		$now = new DateTime('now', $timezone);

		$run_time = new DateTime('today 20:00', $timezone);

		if ($now >= $run_time) {
			$run_time->modify('+1 day');
		}

		return $run_time->getTimestamp();
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
