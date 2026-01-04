<?php

/**
 * Plugin Name: Foco Sport - AI Match Writer
 * Version: 1.0
 * Author: FOCO SPORT
 * Description: Automatically generates a short preview for posts using ChatGPT API.
 * Author URI: https://focosme.com/
 * Text Domain: ai-match-writer
 * Domain Path: /languages/
 * Requires at least: 5.7
 * Requires PHP: 7.2
 */

defined('ABSPATH') || exit;

// Path Constants ======================================================================================================

define('AMW_PLUGIN_URL',             plugins_url() . '/ai-match-writer/');
define('AMW_PLUGIN_DIR',             plugin_dir_path(__FILE__));
define('AMW_CSS_ROOT_URL',           AMW_PLUGIN_URL . 'css/');
define('AMW_JS_ROOT_URL',            AMW_PLUGIN_URL . 'js/');
define('AMW_JS_ROOT_DIR',            AMW_PLUGIN_DIR . 'js/');
define('AMW_TEMPLATES_ROOT_URL',     AMW_PLUGIN_URL . 'templates/');
define('AMW_TEMPLATES_ROOT_DIR',     AMW_PLUGIN_DIR . 'templates/');
define('AMW_BLOCKS_ROOT_URL',        AMW_PLUGIN_URL . 'blocks/');
define('AMW_BLOCKS_ROOT_DIR',        AMW_PLUGIN_DIR . 'blocks/');
define('AMW_VIEWS_ROOT_URL',         AMW_PLUGIN_URL . 'views/');
define('AMW_VIEWS_ROOT_DIR',         AMW_PLUGIN_DIR . 'views/');

// Require autoloader
require_once 'inc/autoloader.php';

// Run
require_once 'ai-match-writer.plugin.php';
$GLOBALS['amw'] = new AI_Match_Writer();
