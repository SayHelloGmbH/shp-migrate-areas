<?php

namespace SayHello\Plugin\MigrateAreas;

use WP_CLI\Utils;
use WP_Query;

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
		\WP_CLI::add_command('sht migrate areas', [$this, 'migrateAreas']);
		\WP_CLI::add_command('sht fix migrated areas', [$this, 'fixMigratedAreas']);
		\WP_CLI::add_command('sht switch-language', [$this, 'switchLanguages']);
	}

	/**
	 * Migrates sht/accordion-block blocks into sht_areas posts.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The ID of the post to process.
	 *
	 * [--term_id=<term_id>]
	 * : The term_id to assign to the new sht_areas posts.
	 *
	 * @param array $args Command arguments.
	 */
	public function migrateAreas($args, $params)
	{
		$post_id = (int) $args[0];

		if (! $post_id || ! get_post($post_id)) {
			\WP_CLI::error("Invalid post ID: $post_id");
		}

		$term_id = $params['term_id'] ?? null;

		if (!$term_id) {
			\WP_CLI::error("Specify a term_id using --term_id");
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

				if ($new_post_id && !is_wp_error($new_post_id)) {
					wp_set_object_terms($new_post_id, [(int) $term_id], 'sht_areas_category');
				}

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

	public function fixMigratedAreas($args, $params)
	{

		$query_args = [
			'post_type'      => 'sht_areas',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];

		$query = new WP_Query($query_args);

		if (! $query->have_posts()) {
			echo "No posts found.\n";
			return;
		}

		$level = (int) $params['level'] ?? 2;

		foreach ($query->posts as $post) {
			$content = get_post_field('post_content', $post->ID);
			$blocks  = parse_blocks($content);
			$new_blocks = [];

			foreach ($blocks as $block) {
				if ($block['blockName'] === 'sht/accordion-block' && ! empty($block['attrs']['title'])) {
					// Insert heading block
					$title = $block['attrs']['title'];

					// replace "u003cstrongu003e" with ""
					$title = str_replace('u003cstrongu003e', '', $title);
					$title = str_replace('u003c/strongu003e', '', $title);

					$new_blocks[] = [
						'blockName'    => 'core/heading',
						'attrs'        => ['level' => $level, 'className' => 'wp-block-heading'],
						'innerBlocks'  => [],
						'innerHTML'    => '<h' . $level . ' class="wp-block-heading">' . esc_html($title) . '</h' . $level . '>',
						'innerContent' => ['<h' . $level . ' class="wp-block-heading">' . esc_html($title) . '</h' . $level . '>'],
					];

					// Insert inner blocks from accordion
					$new_blocks = array_merge($new_blocks, $block['innerBlocks']);
				} else {
					// Keep non-accordion blocks
					$new_blocks[] = $block;
				}
			}

			// Serialize and update post
			$new_content = serialize_blocks($new_blocks);

			wp_update_post([
				'ID'           => $post->ID,
				'post_content' => $new_content,
			]);

			echo "Updated post ID: {$post->ID}\n";
		}
	}

	public function switchLanguages($args, $params)
	{
		$today = current_time('Y-m-d');
		$cutoff = "$today 17:30:00";

		$query = new WP_Query([
			'post_type'      => 'sht_areas',
			'post_status'    => 'publish',
			'date_query'     => [
				[
					'after'     => $cutoff,
					'inclusive' => false,
				],
			],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => -1,
		]);

		if ($query->have_posts()) {
			foreach ($query->posts as $post) {

				// switch WPML language to french
				$language_args = [
					'element_id' => $post->ID,
					'element_type' => "post_{$post->post_type}",
					'trid' => null,
					'language_code' => 'fr',
					'source_language_code' => null,
				];
				do_action('wpml_set_element_language_details', $language_args);

				\WP_CLI::log("ID: {$post->ID} | {$post->post_title} | {$post->post_date} | UPDATED");
			}
		} else {
			\WP_CLI::success('No posts found.');
		}
	}
}
