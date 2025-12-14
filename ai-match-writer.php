<?php

/**
 * Plugin Name: Foco Sport - AI Match Writer
 * Version: 1.0
 * Author: FOCO SPORT
 * Description: Automatically generates a short preview for posts using the ChatGPT API.
 * Author URI: https://focosme.com/
 * Text Domain: ai-match-writer
 * Domain Path: /languages/
 * Requires at least: 5.7
 * Requires PHP: 7.2
 */

defined('ABSPATH') || exit;

// Path Constants ======================================================================================================

define('AMR_PLUGIN_URL',             plugins_url() . '/ai-match-writer/');
define('AMR_PLUGIN_DIR',             plugin_dir_path(__FILE__));
define('AMR_CSS_ROOT_URL',           AMR_PLUGIN_URL . 'css/');
define('AMR_JS_ROOT_URL',            AMR_PLUGIN_URL . 'js/');
define('AMR_JS_ROOT_DIR',            AMR_PLUGIN_DIR . 'js/');
define('AMR_TEMPLATES_ROOT_URL',     AMR_PLUGIN_URL . 'templates/');
define('AMR_TEMPLATES_ROOT_DIR',     AMR_PLUGIN_DIR . 'templates/');
define('AMR_BLOCKS_ROOT_URL',        AMR_PLUGIN_URL . 'blocks/');
define('AMR_BLOCKS_ROOT_DIR',        AMR_PLUGIN_DIR . 'blocks/');
define('AMR_VIEWS_ROOT_URL',         AMR_PLUGIN_URL . 'views/');
define('AMR_VIEWS_ROOT_DIR',         AMR_PLUGIN_DIR . 'views/');

// Require autoloader
require_once 'inc/autoloader.php';

// Run
require_once 'ai-match-writer.plugin.php';
$GLOBALS['amr'] = new AI_Match_Writer();
