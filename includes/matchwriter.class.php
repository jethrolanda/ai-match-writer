<?php

namespace AMW\Plugin;


defined('ABSPATH') || exit;

class MatchWriter
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
    // Ajax request for performing per team events import
    add_action('wp_ajax_check_games', array($this, 'check_games'));
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

  public function check_games()
  {
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in() || !is_admin()) {
      wp_die();
    }
    $posts = array(66081, 65119, 57195, 56432);

    $matches = '';
    $count = 1;
    foreach ($posts as $post_id) {
      $post = get_post($post_id);
      $status = '';
      $post_timestamp = get_post_time('U', false, $post);
      $formatted_time = date('F j, Y, g:i a', $post_timestamp);
      $venues = get_the_terms($post_id, 'sp_venue');
      $term_names = wp_list_pluck($venues, 'name');
      $term_names = implode(', ', $term_names);

      if (in_array($post->post_status, array('future', 'publish'))) {
        $status =  $post->post_status == 'future' ? 'upcoming' : 'completed';
      } else {
        continue;
      }
      if ($status == 'completed') {
        $matches .= "Match {$count}:\n";
        $matches .= "Status {$status}\n";
        $matches .= "Teams: {$post->post_title}\n";
        $matches .= "Kickoff Time: {$formatted_time}\n";
        $matches .= "Venue: {$term_names}\n";
        $matches .= "Final Score: {$this->get_scores($post_id)}\n";
        $matches .= "Key Performers:\n";
        $matches .= $this->get_players_key_performers($post_id);
      } else {
        $matches .= "Match {$count}:\n";
        $matches .= "Status {$status}\n";
        $matches .= "Teams: {$post->post_title}\n";
        $matches .= "Kickoff Time: {$formatted_time}\n";
        $matches .= "Venue: {$term_names}\n";
        $matches .= "Players to Watch:\n";
        $matches .= $this->get_players_to_watch($post_id);
      }
      $count++;
    }
    error_log(print_r($matches, true));
    //     $matches = 'Match 1:
    // Status: completed
    // Teams: NSW Samoa vs Fiji Ravouvou
    // Final Score: 16–12
    // Key Performers:
    // - NSW Samoa: John Doe (2 tries), Mike Lee (5 tackles)
    // - Fiji Ravouvou: Sam Tui (1 try)

    // Match 2:
    // Status: upcoming
    // Teams: Queensland Reds vs Auckland Blues
    // Kickoff Time: 18:30
    // Venue: Eden Park
    // Players to Watch:
    // - Queensland Reds: Tom Smith
    // - Auckland Blues: James Wong';
    // error_log(print_r($this->call_openai($matches), true));
    // $this->check_fixtures();


  }
  public function check_fixtures()
  {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $args = [
      'post_type'      => 'sp_event',        // or your CPT
      'post_status'    => 'future',      // scheduled posts only
      'posts_per_page' => -1,
      'fields'         => 'ids',
      'date_query'     => [
        [
          'after'     => $tomorrow . ' 00:00:00',
          'before'    => $tomorrow . ' 23:59:59',
          'inclusive' => true,
        ],
      ],
    ];

    $query = get_posts($args);

    error_log(print_r($tomorrow, true));
    error_log(print_r($query, true));
  }

  public function get_scores($id)
  {
    $scores = get_post_meta($id, 'sp_results', true);
    $final_scores = array();
    foreach ($scores as $score) {
      $final_scores[] = $score['points'];
    }
    return implode('-', $final_scores);
  }

  public function get_players_key_performers($id)
  {
    $matches = "";
    $match_details = get_post_meta($id, 'rugby_explorer_match_details_data', true);
    $points = array();

    if (empty($match_details)) {
      return '';
    }

    if ($match_details && isset($match_details['allMatchStatsSummary']['pointsSummary'])) {
      $points_summary = $match_details['allMatchStatsSummary']['pointsSummary'];

      foreach ($points_summary as $id => $pp) {

        if (!in_array($id, array('tries', 'conversions', 'penaltyGoals', 'fieldGoals'))) {
          continue;
        }

        foreach ($pp as $p) {
          if (in_array($p['playerName'], array('Name Withheld', 'Not Player Event'))) {
            continue;
          }
          if ($p['isHome']) {
            if (!isset($points[0][$p['playerName']]) || !isset($points[0][$p['playerName']][$id])) {
              $points[0][$p['playerName']][$id] = 1;
            } else {
              $points[0][$p['playerName']][$id] += 1;
            }
          } else {
            if (!isset($points[1][$p['playerName']]) || !isset($points[1][$p['playerName']][$id])) {
              $points[1][$p['playerName']][$id] = 1;
            } else {
              $points[1][$p['playerName']][$id] += 1;
            }
          }
        }
      }
    }

    if (!empty($points)) {
      $matches .= "- {$match_details['getFixtureItem']['homeTeam']['name']}: ";
      if (isset($points[0])) {
        $m = array();
        foreach ($points[0] as $name => $p) {
          foreach ($p as $method => $score) {
            $m[] = "{$name} ({$score} {$method})";
          }
        }
        $matches .= implode(', ', $m);
        $matches .= "\n";
      }

      $matches .= "- {$match_details['getFixtureItem']['awayTeam']['name']}: ";
      if (isset($points[1])) {
        $m = array();
        foreach ($points[1] as $name => $p) {
          foreach ($p as $method => $score) {
            $m[] = "{$name} ({$score} {$method})";
          }
        }
        $matches .= implode(', ', $m);
        $matches .= "\n";
      }
    }
    return $matches;
    // error_log(print_r($points, true));
  }

  public function get_players_to_watch($id)
  {
    $teams = get_post_meta($id, 'sp_team');

    $matches = "";
    foreach ($teams as $team_id) {
      $top_scorers = $this->my_get_top_rugby_scorers(0, 0, $team_id, 3);
      $team_name = get_the_title($team_id);
      $team_name = html_entity_decode($team_name, ENT_QUOTES, 'UTF-8');
      $matches .= "- {$team_name}: ";
      $player_names = array();
      foreach ($top_scorers as $scorer) {
        $player_names[] = $scorer['name'];
      }
      $matches .= implode(', ', $player_names);
      $matches .= "\n";
    }

    return $matches;
  }

  public function my_get_top_rugby_scorers($league_id = 0, $season_id = 0, $team_id = 0, $limit = 10)
  {
    // Get all players, filtered by league/season/team
    $args = array(
      'post_type'      => 'sp_player',
      'numberposts'    => -1,
      'posts_per_page' => -1,
      'tax_query'      => array(),
      'meta_query'     => array(),
    );

    if ($league_id) {
      $args['tax_query'][] = array(
        'taxonomy' => 'sp_league',
        'field'    => 'term_id',
        'terms'    => (array) $league_id,
      );
    }

    if ($season_id) {
      $args['tax_query'][] = array(
        'taxonomy' => 'sp_season',
        'field'    => 'term_id',
        'terms'    => (array) $season_id,
      );
    }

    if ($team_id) {
      // current team only; change to sp_team / sp_past_team if needed
      $args['meta_query'][] = array(
        'key'   => 'sp_current_team',
        'value' => (array) $team_id,
      );
    }

    $players = get_posts($args);
    $rows    = array();

    foreach ($players as $player_post) {
      $player = new \SP_Player($player_post);

      // admin = true so we get totals/placeholders back
      list($labels, $data, $placeholders, $merged) = $player->data($league_id, true);

      // Career totals row is copied into $placeholders[0]
      $points = isset($placeholders[0]['pts']) ? floatval($placeholders[0]['pts']) : 0;

      $rows[] = array(
        'id'     => $player_post->ID,
        'name'   => get_the_title($player_post),
        'points' => $points,
      );
    }

    // Sort by points desc
    usort($rows, function ($a, $b) {
      if ($a['points'] == $b['points']) {
        return 0;
      }
      return ($a['points'] < $b['points']) ? 1 : -1;
    });

    return array_slice($rows, 0, $limit);
  }

  /**
   * Call OpenAI chat completions.
   *
   * @param string $api_key API key.
   * @param string $prompt  Prompt text.
   * @return string|WP_Error
   */
  private function call_openai($matches)
  {
    $settings = get_option('amw_options');
    $model = get_option($settings['amw_model'], 'gpt-4o-mini');

    $payload = array(
      'model'       => $model,
      'messages'    => array(
        array(
          'role'    => 'system',
          'content' => 'You are a professional sports content writer.

                        Your task:
                        - Write short match previews for upcoming matches
                        - Write concise match summaries for completed matches

                        Rules:
                        - Use ONLY the data provided by the user
                        - Do NOT invent scores, players, outcomes, or statistics
                        - Do NOT predict winners or scores
                        - If information is missing, omit it
                        - Use a neutral, professional sports journalism tone',
        ),
        array(
          'role'    => 'user',
          'content' => "Matches for the specified day:

          {$matches}

Writing instructions:
- Write exactly TWO short paragraph per match
- For completed matches:
  - Use past tense
  - Mention the final score
- For upcoming matches:
  - Use future tense
  - Do NOT predict results
  - Mention kickoff time and venue if provided
- Do not include headings, bullet points, or lists
- Separate each paragraph with a single blank line
- Return plain text only",
        ),
      ),
      'temperature' => 0.6,
    );

    $response = wp_remote_post(
      'https://api.openai.com/v1/chat/completions',
      array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $settings['amw_api_key'],
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
      return new \WP_Error('spai_api_error', sprintf(__('OpenAI API returned HTTP %s: %s', 'sportspress'), $code, $message));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['choices'][0]['message']['content'])) {
      return new \WP_Error('spai_empty', __('OpenAI response did not include content.', 'sportspress'));
    }

    return trim($body['choices'][0]['message']['content']);
  }
}
