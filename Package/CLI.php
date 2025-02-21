<?php

namespace SayHello\Plugin\MigrateAreas;

/**
 * Registers a custom WP CLI command.
 *
 * @package SayHello\Plugin\MigrateAreas
 *
 * @property \WP_CLI $wp_cli
 */
class CLI
{

	/**
	 * Registers the custom WP CLI command.
	 */
	public function register()
	{
		/** @var \WP_CLI $wp_cli */
		\WP_CLI::add_command('sht migrate areas', [$this, 'migrateAreas']);
	}

	/**
	 * Migrates sht/accordion-block blocks into sht_areas posts.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The ID of the post to process.
	 *
	 * @param array $args Command arguments.
	 */
	public static function migrateAreas($args)
	{
		$post_id = (int) $args[0];

		if (! $post_id || ! get_post($post_id)) {
			\WP_CLI::error("Invalid post ID: $post_id");
		}

		$content = get_post_field('post_content', $post_id);
		$blocks = parse_blocks($content);
		$count = 0;

		foreach ($blocks as $block) {
			if ($block['blockName'] === 'sht/accordion-block' && !empty($block['attrs']['title'])) {

				$title = strip_tags(sanitize_text_field($block['attrs']['title']));
				$new_post_id = wp_insert_post([
					'post_title' => $title,
					'post_content' => serialize_blocks($block['innerBlocks']),
					'post_status' => 'publish',
					'post_type' => 'sht_areas',
				]);

				if (is_wp_error($new_post_id)) {
					\WP_CLI::warning("Failed to create post for: {$title}");
				} else {
					$count++;
					\WP_CLI::success("Created post ID $new_post_id for: {$title}");
				}
			}
		}

		if ($count === 0) {
			\WP_CLI::warning('No accordion blocks found.');
		} else {
			\WP_CLI::success("Migrated $count blocks into sht_areas posts.");
		}
	}
}
