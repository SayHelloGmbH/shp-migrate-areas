<?php

/**
 * Plugin Name: Migrate Areas CLI
 * Description: Registers a WP-CLI command to migrate sht/accordion-block blocks into sht_areas posts.
 * Version: 1.0.1
 * Author: Mark Howells-Mead
 */

namespace SayHello\Plugin\MigrateAreas;

use WP_CLI;

if (defined('WP_CLI') && WP_CLI) {
	include_once __DIR__ . '/Package/CLI.php';
	$cli = new CLI();

	WP_CLI::add_hook('after_wp_load', [$cli, 'register']); // Add backslash here too
}
