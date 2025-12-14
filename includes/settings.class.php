<?php

namespace AMR\Plugin;


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

  private $option_key = 'sp_ai_openai_api_key';
  /**
   * Class constructor.
   *
   * @since 1.0.0
   */
  public function __construct()
  {
    add_action('admin_menu', [$this, 'register_settings_page']);
    add_action('admin_init', [$this, 'register_settings']);

    add_action('add_meta_boxes', [$this, 'add_generate_summary_box']);
    add_action('admin_post_sp_generate_match_summary', [$this, 'handle_generate_summary']);

    // optional: auto-generate when match is updated
    // add_action('save_post_sp_event', [$this, 'auto_generate_on_save'], 20, 2);
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
  /* ----------------------------------------------
        SETTINGS PAGE
    ---------------------------------------------- */

  public function register_settings_page()
  {
    add_options_page(
      'AI Match Summary Settings',
      'AI Match Summary',
      'manage_options',
      'sp-ai-match-summary',
      [$this, 'settings_page_html']
    );
  }

  public function register_settings()
  {
    register_setting('sp_ai_match_summary_group', $this->option_key);
  }

  public function settings_page_html()
  {
?>
    <div class="wrap">
      <h1>SportsPress AI Match Summary</h1>
      <form method="post" action="options.php">
        <?php settings_fields('sp_ai_match_summary_group'); ?>
        <table class="form-table">
          <tr>
            <th>OpenAI API Key</th>
            <td>
              <input
                type="password"
                name="<?php echo esc_attr($this->option_key); ?>"
                value="<?php echo esc_attr(get_option($this->option_key)); ?>"
                style="width: 400px;">
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
  }

  /* ----------------------------------------------
        META BOX (Button inside SportsPress Event)
    ---------------------------------------------- */

  public function add_generate_summary_box()
  {
    add_meta_box(
      'sp_ai_summary_box',
      'AI Match Summary',
      [$this, 'render_generate_summary_box'],
      'sp_event',
      'side'
    );
  }

  public function render_generate_summary_box($post)
  {
  ?>
    <p>Generate AI-powered match preview/summary post.</p>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <input type="hidden" name="action" value="sp_generate_match_summary">
      <input type="hidden" name="event_id" value="<?php echo esc_attr($post->ID); ?>">
      <?php submit_button("Generate Match Summary", "primary"); ?>
    </form>
<?php
  }

  /* ----------------------------------------------
        MAIN HANDLER: CREATE MATCH SUMMARY POST
    ---------------------------------------------- */

  public function handle_generate_summary()
  {
    if (!current_user_can('edit_posts')) wp_die("Unauthorized");

    $event_id = intval($_POST['event_id']);
    $post = get_post($event_id);

    if (!$post || $post->post_type !== 'sp_event') wp_die("Invalid event");

    $summary = $this->generate_summary_from_event($event_id);

    if (!$summary) {
      wp_die("Failed to generate summary.");
    }

    $new_post = [
      'post_title'   => 'Match Summary: ' . $post->post_title,
      'post_content' => wp_kses_post($summary),
      'post_status'  => 'publish',
      'post_type'    => 'post'
    ];

    $post_id = wp_insert_post($new_post);

    wp_redirect(admin_url("post.php?post={$post_id}&action=edit"));
    exit;
  }

  /* ----------------------------------------------
        READ SPORTSPRESS EVENT DATA + GENERATE SUMMARY
    ---------------------------------------------- */

  public function generate_summary_from_event($event_id)
  {
    $api_key = get_option($this->option_key);
    if (!$api_key) return false;

    // Teams
    $teams = get_post_meta($event_id, 'sp_team', true);
    $home_team = get_the_title($teams[0] ?? 0);
    $away_team = get_the_title($teams[1] ?? 0);

    // Scores
    $results = get_post_meta($event_id, 'sp_results', true);
    $home_score = $results[$teams[0]] ?? 0;
    $away_score = $results[$teams[1]] ?? 0;

    // Players Stats Table (optional)
    $players = get_post_meta($event_id, 'sp_player', true);

    // Build readable stats
    $stats_text = "";
    if (is_array($players)) {
      foreach ($players as $team_id => $player_list) {
        $stats_text .= get_the_title($team_id) . " Players:\n";

        foreach ($player_list as $player_id => $player_stats) {
          $stats_text .= "- " . get_the_title($player_id);

          if (is_array($player_stats)) {
            foreach ($player_stats as $stat => $val) {
              if ($val) {
                $stats_text .= " | $stat: $val";
              }
            }
          }
          $stats_text .= "\n";
        }
        $stats_text .= "\n";
      }
    }

    // Build AI Prompt
    $prompt = "
Create a professional, engaging match preview or summary.
Use clear sports journalism tone.

Match:
Home Team: $home_team
Away Team: $away_team

Score:
$home_team: $home_score
$away_team: $away_score

Player Stats:
$stats_text

Write 2–4 short paragraphs summarizing:
- Match setup or match recap
- Key players and moments
- Impact on standings (general only, no fake data)
        ";

    // OpenAI API Call
    $body = [
      "model" => "gpt-4.1-mini",
      "messages" => [
        ["role" => "user", "content" => $prompt]
      ],
      "max_tokens" => 300
    ];

    $response = wp_remote_post(
      "https://api.openai.com/v1/chat/completions",
      [
        'headers' => [
          'Content-Type'  => 'application/json',
          'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode($body),
        'timeout' => 30
      ]
    );

    if (is_wp_error($response)) {
      return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return $data['choices'][0]['message']['content'] ?? false;
  }
}
