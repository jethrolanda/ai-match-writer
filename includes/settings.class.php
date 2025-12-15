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

  const OPTION_API_KEY   = 'spai_api_key';
  const OPTION_MODEL     = 'spai_model';
  const OPTION_PROMPT    = 'spai_prompt';
  const OPTION_DRAFT_CAT = 'spai_post_category';

  /**
   * Default prompt template.
   *
   * Tokens:
   * - {event_title}, {date}, {venue}, {teams}, {score}, {status}, {results_json}
   */
  const DEFAULT_PROMPT = "Write a 180-220 word match recap suitable for a WordPress post.\n\nEvent: {event_title}\nDate: {date}\nVenue: {venue}\nTeams: {teams}\nScore: {score}\nStatus: {status}\nRaw results data:\n{results_json}\n\nFocus on key moments, standout players, and context for fans. Keep it objective, lively, and avoid making up facts.";

  /**
   * Class constructor.
   *
   * @since 1.0.0
   */
  public function __construct()
  {
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_menu', array($this, 'register_menu'), 20);
    add_action('add_meta_boxes', array($this, 'add_meta_box'));
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
   * Register options.
   */
  public function register_settings()
  {
    // Register a new setting for "rugbyexplorer" page.
    register_setting('rugbyexplorer', 'rugbyexplorer_options');

    register_setting(
      'ai_match_writer_settings',
      self::OPTION_API_KEY,
      array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
      )
    );

    register_setting(
      'ai_match_writer_settings',
      self::OPTION_MODEL,
      array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'gpt-4o-mini',
      )
    );

    register_setting(
      'ai_match_writer_settings',
      self::OPTION_PROMPT,
      array(
        'type'              => 'string',
        'sanitize_callback' => 'wp_kses_post',
        'default'           => self::DEFAULT_PROMPT,
      )
    );

    // register_setting(
    //   'ai_match_writer_settings',
    //   self::OPTION_DRAFT_CAT,
    //   array(
    //     'type'              => 'integer',
    //     'sanitize_callback' => 'absint',
    //     'default'           => 0,
    //   )
    // );
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
    $api_key   = get_option(self::OPTION_API_KEY, '');
    $model     = get_option(self::OPTION_MODEL, 'gpt-4o-mini');
    $prompt    = get_option(self::OPTION_PROMPT, self::DEFAULT_PROMPT);
    $draft_cat = get_option(self::OPTION_DRAFT_CAT, 0);
?>
    <div class="wrap">
      <h1><?php esc_html_e('AI Match Writer', 'ai-match-writer'); ?></h1>
      <form method="post" action="options.php" style="display:none;">
        <?php settings_fields('ai_match_writer_settings'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="spai_api_key"><?php esc_html_e('OpenAI API Key', 'ai-match-writer'); ?></label></th>
            <td>
              <input type="password" class="regular-text" name="<?php echo esc_attr(self::OPTION_API_KEY); ?>" id="spai_api_key" value="<?php echo esc_attr($api_key); ?>" autocomplete="off" />
              <p class="description"><?php esc_html_e('Stored in WordPress options. Required to call the OpenAI API.', 'ai-match-writer'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="spai_model"><?php esc_html_e('Model', 'ai-match-writer'); ?></label></th>
            <td>
              <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_MODEL); ?>" id="spai_model" value="<?php echo esc_attr($model); ?>" />
              <p class="description"><?php esc_html_e('Any chat completion model name your key can access (e.g., gpt-4o-mini).', 'ai-match-writer'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="spai_prompt"><?php esc_html_e('Prompt Template', 'ai-match-writer'); ?></label></th>
            <td>
              <textarea name="<?php echo esc_attr(self::OPTION_PROMPT); ?>" id="spai_prompt" rows="10" class="large-text code"><?php echo esc_textarea($prompt); ?></textarea>
              <p class="description"><?php esc_html_e('Tokens: {event_title}, {date}, {venue}, {teams}, {score}, {status}, {results_json}', 'ai-match-writer'); ?></p>
            </td>
          </tr>
          <tr style="display: none;">
            <th scope="row"><label for="spai_post_category"><?php esc_html_e('Draft Category (optional)', 'ai-match-writer'); ?></label></th>
            <td>
              <?php
              wp_dropdown_categories(
                array(
                  'show_option_none' => __('None', 'ai-match-writer'),
                  'name'             => self::OPTION_DRAFT_CAT,
                  'orderby'          => 'name',
                  'hide_empty'       => false,
                  'selected'         => $draft_cat,
                )
              );
              ?>
              <p class="description"><?php esc_html_e('Draft posts will be created with this category if selected.', 'ai-match-writer'); ?></p>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <div id="ai-match-writer"></div>
    </div>
  <?php
  }

  /**
   * Add meta box to SportsPress events.
   */
  public function add_meta_box()
  {
    add_meta_box(
      'spai-summary-box',
      __('AI Match Summary', 'ai-match-writer'),
      array($this, 'render_meta_box'),
      'sp_event',
      'side',
      'high'
    );
  }

  /**
   * Meta box UI.
   *
   * @param WP_Post $post Event post.
   */
  public function render_meta_box($post)
  {
    wp_nonce_field('spai_meta_box', 'spai_meta_box_nonce');
    $api_key = get_option(self::OPTION_API_KEY, '');
  ?>
    <p><?php esc_html_e('Generate a draft post with an AI-written match report using the current event data.', 'ai-match-writer'); ?></p>
    <?php if (empty($api_key)) : ?>
      <p><strong><?php esc_html_e('Add your OpenAI API key in SportsPress → AI Summaries settings to enable.', 'ai-match-writer'); ?></strong></p>
    <?php else : ?>
      <button type="button" class="button button-primary spai-generate" data-event="<?php echo esc_attr($post->ID); ?>">
        <?php esc_html_e('Generate AI Summary Draft', 'ai-match-writer'); ?>
      </button>
      <div class="spai-status" style="margin-top:10px;"></div>
    <?php endif; ?>
<?php
    $last_draft = get_post_meta($post->ID, '_spai_last_draft', true);
    if ($last_draft) {
      $edit_link = get_edit_post_link($last_draft);
      if ($edit_link) {
        echo '<p style="margin-top:10px;">' . sprintf(esc_html__('Last draft: %s', 'ai-match-writer'), '<a href="' . esc_url($edit_link) . '">' . esc_html(get_the_title($last_draft)) . '</a>') . '</p>';
      }
    }
  }

  /**
   * AJAX handler.
   */
  public function ajax_generate_summary()
  {
    check_ajax_referer('spai_nonce', 'nonce');

    $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

    if (! $event_id || 'sp_event' !== get_post_type($event_id)) {
      wp_send_json_error(array('message' => __('Invalid event.', 'ai-match-writer')), 400);
    }

    if (! current_user_can('edit_post', $event_id)) {
      wp_send_json_error(array('message' => __('You are not allowed to do this.', 'ai-match-writer')), 403);
    }

    $api_key = get_option(self::OPTION_API_KEY, '');

    if (empty($api_key)) {
      wp_send_json_error(array('message' => __('OpenAI API key not configured.', 'ai-match-writer')), 400);
    }

    $prompt = $this->build_prompt($event_id);

    if (is_wp_error($prompt)) {
      wp_send_json_error(array('message' => $prompt->get_error_message()), 400);
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

  /**
   * Build prompt text from event data.
   *
   * @param int $event_id Event ID.
   * @return string|WP_Error
   */
  private function build_prompt($event_id)
  {
    $event = get_post($event_id);

    if (! $event) {
      return new WP_Error('spai_no_event', __('Event not found.', 'ai-match-writer'));
    }

    $teams      = $this->get_event_teams($event_id);
    $score_line = $this->get_event_scoreline($event_id, $teams);
    $status     = sp_get_status($event_id);
    $date       = get_post_meta($event_id, 'sp_date', true);
    $venue      = $this->get_event_venue($event_id);
    $results    = sp_get_results($event_id);

    $template = get_option(self::OPTION_PROMPT, self::DEFAULT_PROMPT);

    $replacements = array(
      '{event_title}'  => $event->post_title,
      '{date}'         => $date ? $date : get_post_time('Y-m-d H:i', false, $event),
      '{venue}'        => $venue ? $venue : __('Not set', 'ai-match-writer'),
      '{teams}'        => $teams ? implode(' vs ', wp_list_pluck($teams, 'name')) : __('Teams not set', 'ai-match-writer'),
      '{score}'        => $score_line ? $score_line : __('N/A', 'ai-match-writer'),
      '{status}'       => $status,
      '{results_json}' => wp_json_encode($results, JSON_PRETTY_PRINT),
    );

    return strtr($template, $replacements);
  }

  /**
   * Retrieve teams with names.
   *
   * @param int $event_id Event ID.
   * @return array<int,array{name:string,id:int}>
   */
  private function get_event_teams($event_id)
  {
    $team_ids = array_filter((array) sp_get_teams($event_id));
    $teams    = array();

    foreach ($team_ids as $team_id) {
      $teams[] = array(
        'id'   => $team_id,
        'name' => get_the_title($team_id),
      );
    }

    return $teams;
  }

  /**
   * Build human-friendly scoreline.
   *
   * @param int   $event_id Event ID.
   * @param array $teams    Teams array.
   * @return string|null
   */
  private function get_event_scoreline($event_id, $teams)
  {
    if (count($teams) < 2) {
      return null;
    }

    $event        = new SP_Event($event_id);
    $main_results = $event->main_results();

    if (! is_array($main_results) || count($main_results) < 2) {
      return null;
    }

    $team_a = $teams[0]['name'];
    $team_b = $teams[1]['name'];
    $score_a = isset($main_results[0]) ? $main_results[0] : null;
    $score_b = isset($main_results[1]) ? $main_results[1] : null;

    if (null === $score_a || null === $score_b) {
      return null;
    }

    return sprintf('%s %s - %s %s', $team_a, $score_a, $score_b, $team_b);
  }

  /**
   * Get venue name.
   *
   * @param int $event_id Event ID.
   * @return string|null
   */
  private function get_event_venue($event_id)
  {
    $venues = sp_get_venues($event_id, false);
    if (! empty($venues) && isset($venues[0]->name)) {
      return $venues[0]->name;
    }
    return null;
  }

  /**
   * Call OpenAI chat completions.
   *
   * @param string $api_key API key.
   * @param string $prompt  Prompt text.
   * @return string|WP_Error
   */
  private function call_openai($api_key, $prompt)
  {
    $model = get_option(self::OPTION_MODEL, 'gpt-4o-mini');

    $payload = array(
      'model'       => $model,
      'messages'    => array(
        array(
          'role'    => 'system',
          'content' => 'You are a sports writer creating concise, factual match summaries.',
        ),
        array(
          'role'    => 'user',
          'content' => $prompt,
        ),
      ),
      'temperature' => 0.6,
    );

    $response = wp_remote_post(
      'https://api.openai.com/v1/chat/completions',
      array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode($payload),
        'timeout' => 30,
      )
    );

    if (is_wp_error($response)) {
      return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
      $message = wp_remote_retrieve_body($response);
      return new WP_Error('spai_api_error', sprintf(__('OpenAI API returned HTTP %s: %s', 'ai-match-writer'), $code, $message));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['choices'][0]['message']['content'])) {
      return new WP_Error('spai_empty', __('OpenAI response did not include content.', 'ai-match-writer'));
    }

    return trim($body['choices'][0]['message']['content']);
  }

  /**
   * Create draft post from summary content.
   *
   * @param int    $event_id Event ID.
   * @param string $summary  Summary content.
   * @return int|WP_Error
   */
  private function create_draft_post($event_id, $summary)
  {
    $event = get_post($event_id);

    if (! $event) {
      return new WP_Error('spai_no_event', __('Event not found.', 'ai-match-writer'));
    }

    $category = absint(get_option(self::OPTION_DRAFT_CAT, 0));
    $postarr  = array(
      'post_title'   => sprintf(__('%s – Match Summary', 'ai-match-writer'), $event->post_title),
      'post_content' => $summary,
      'post_status'  => 'draft',
      'post_type'    => 'post',
    );

    if ($category) {
      $postarr['post_category'] = array($category);
    }

    $post_id = wp_insert_post($postarr, true);

    if (is_wp_error($post_id)) {
      return $post_id;
    }

    update_post_meta($event_id, '_spai_last_draft', $post_id);

    return $post_id;
  }
}
