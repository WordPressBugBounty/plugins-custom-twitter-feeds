<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\CTF_Ability_Response;
use TwitterFeed\Abilities\Services\CTF_Feed_Settings_Service;
use TwitterFeed\Abilities\Services\CTF_Settings_Schema;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/get-feed
 *
 * `warnings[]` (AgDR-9004/9005 downgrade rule) surfaces a stored source type
 * that does not render in the free plugin, e.g. after a Pro downgrade.
 *
 * @since 2.10.1
 */
class CTF_Get_Feed extends CTF_Ability
{
	public function slug()
	{
		return 'get-feed';
	}

	public function args()
	{
		return array(
			'label'               => __('Get a Twitter Feed', 'custom-twitter-feeds'),
			'description'         => __(
				'Returns one feed\'s customer-configurable settings (layout, columns, tweet count, header, captions, colors) and its configured source (type + terms). The connected account, tokens and app credentials are never returned.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
				'custom-twitter-feeds'
			),
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'feed_id' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => 'Feed id from list-feeds.',
					),
				),
				'required'             => array('feed_id'),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'            => array('type' => 'integer'),
					'feed_title'    => array('type' => 'string'),
					'status'        => array('type' => 'string'),
					'last_modified' => array('type' => 'string'),
					'shortcode'     => array('type' => 'string'),
					'source'        => array(
						'type'       => 'object',
						'properties' => CTF_Settings_Schema::to_json_properties(
							CTF_Settings_Schema::feed_source_readonly()
						),
					),
					'settings'      => array(
						'type'       => 'object',
						'properties' => CTF_Settings_Schema::to_json_properties(CTF_Settings_Schema::feed_settings()),
					),
					'warnings'      => array(
						'type'        => 'array',
						'items'       => array('type' => 'string'),
						'description' => 'Human-readable notices, e.g. a stored source type that does not '
							. 'render in the free plugin.',
					),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_manage'),
			'meta'                => $this->read_meta(true),
		);
	}

	public function execute($input = null)
	{
		$input   = $this->normalize_input($input);
		$feed_id = isset($input['feed_id']) ? (int) $input['feed_id'] : 0;
		if ($feed_id <= 0) {
			return CTF_Ability_Response::invalid_feed_id();
		}
		$feed = (new CTF_Feed_Settings_Service())->get_feed($feed_id);
		return $feed === null ? CTF_Ability_Response::feed_not_found($feed_id) : $feed;
	}
}
