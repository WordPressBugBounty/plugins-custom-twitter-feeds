<?php

namespace TwitterFeed\Abilities;

use WP_Error;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * WP_Error factories shared by every Custom Twitter Feeds ability.
 *
 * Every error carries an HTTP status in `data.status` so the core Abilities
 * REST controller and the MCP adapter surface the right code.
 *
 * @since 2.9
 */
class CTF_Ability_Response
{
	public static function forbidden()
	{
		return new WP_Error(
			'ctf_forbidden',
			__('You do not have permission to manage Custom Twitter Feeds settings.', 'custom-twitter-feeds'),
			array('status' => 403)
		);
	}

	public static function writes_disabled()
	{
		return new WP_Error(
			'ctf_writes_disabled',
			__(
				'AI write access is disabled. Enable "MCP Write Access" under Twitter Feed → Settings → General.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
				'custom-twitter-feeds'
			),
			array('status' => 403)
		);
	}

	public static function invalid_feed_id()
	{
		return new WP_Error(
			'ctf_invalid_feed_id',
			__('A valid feed_id is required.', 'custom-twitter-feeds'),
			array('status' => 400)
		);
	}

	public static function feed_not_found($feed_id)
	{
		return new WP_Error(
			'ctf_feed_not_found',
			sprintf(
				/* translators: %d: feed id */
				__('Feed %d was not found.', 'custom-twitter-feeds'),
				(int) $feed_id
			),
			array('status' => 404)
		);
	}
}
