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
	public $matchwriter;

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
		$this->matchwriter = \AMW\Plugin\MatchWriter::instance();


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

		if (empty($options['amw_enable_auto_generation'])) {
			$options['amw_enable_auto_generation'] = true;
		}

		if (empty($options['amw_time'])) {
			$timezone = wp_timezone();
			$time = DateTime::createFromFormat('H:i', '22:00', $timezone);
			$options['amw_time'] = $time->format('H:i'); // "22:00"
		}

		if (empty($options['amw_system_prompt'])) {
			$options['amw_system_prompt'] = 'You are a professional sports content writer.
					Your task:
					- Write short match previews for upcoming matches
					- Write concise match summaries for completed matches

					Rules:
					- Use ONLY the data provided by the user
					- Do NOT invent scores, players, outcomes, or statistics
					- Do NOT predict winners or scores
					- If information is missing, omit it
					- Use a neutral, professional sports journalism tone';
			$options['amw_system_prompt'] = sanitize_textarea_field(
				preg_replace('/\t+/', '', wp_unslash($options['amw_system_prompt']))
			);
		}

		if (empty($options['amw_user_prompt'])) {
			$user_prompt = "Matches for the specified day:\n";
			$user_prompt .= "{matches}\n";
			$user_prompt .= "Writing instructions:\n";
			$user_prompt .= "- Write exactly TWO short paragraph per match\n";
			$user_prompt .= "- Do not include headings, bullet points, or lists\n";
			$user_prompt .= "- Separate each paragraph with a single blank line\n";
			$user_prompt .= "- Return plain text only\n";
			$user_prompt .= "- For completed matches:\n";
			$user_prompt .= "\t- Use past tense\n";
			$user_prompt .= "\t- Mention the final score\n";
			$user_prompt .= "- For upcoming matches:\n";
			$user_prompt .= "\t- Use future tense\n";
			$user_prompt .= "\t- Do NOT predict results\n";
			$user_prompt .= "\t- Mention kickoff time and venue if provided";

			$options['amw_user_prompt'] = $user_prompt;
		}

		if (empty($options['amw_season'])) {
			$options['amw_season'] = date('Y');
		}

		if (empty($options['amw_tageted_teams'])) {
			$options['amw_tageted_teams'] = array();
		}

		error_log(print_r($options, true));
		update_option('amw_options', $options);
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
