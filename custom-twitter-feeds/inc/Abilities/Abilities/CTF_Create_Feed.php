<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\Services\CTF_Feed_Settings_Service;
use TwitterFeed\Abilities\Services\CTF_Settings_Schema;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/create-feed
 *
 * `feedtemplate` is validated against the full `CTF_Feed_Settings_Service::FEED_TEMPLATES`
 * enum, then narrowed plugin-side to `CREATE_FEED_TEMPLATES` (list layouts only);
 * masonry/carousel templates would store a layout the free front-end cannot render
 * (the refusal carries a Pro upgrade hint) — see
 * projects/custom-twitter-feeds/abilities-free-plan.md §3a row `feedtemplate`.
 *
 * @since 2.10.1
 */
class CTF_Create_Feed extends CTF_Ability
{
	public function slug()
	{
		return 'create-feed';
	}

	public function args()
	{
		$writable = CTF_Settings_Schema::writable(CTF_Settings_Schema::feed_settings());
		// feed_name has a dedicated top-level property; a nested copy would be
		// silently written into the settings blob instead of the feeds row.
		unset($writable['feed_name']);

		return array(
			'label'               => __('Create a Twitter Feed', 'custom-twitter-feeds'),
			'description'         => __('Creates a new feed showing the connected account\'s own timeline, like the Feed Builder does: an optional feed template and optional quick settings. Use set-feed-type-and-terms afterwards to point it at a specific handle. Returns the new feed id and its shortcode. The free plugin supports one feed: once a feed exists this is refused (edit it with update-feed-settings). Requires AI write access. A setting that is not in this schema may be a Pro-only feature: call describe-settings-schema (pro_only) or get-pro-features.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'feed_name'    => array(
						'type'        => 'string',
						'maxLength'   => 200,
						'description' => 'Optional name; defaults to "Twitter Feed".',
					),
					'feedtemplate' => array(
						'type'        => 'string',
						'enum'        => CTF_Feed_Settings_Service::FEED_TEMPLATES,
						'description' => 'Optional feed template preset. Only list-layout templates are offered '
							. 'in the free plugin.',
					),
					'settings'     => array(
						'type'                 => 'object',
						'properties'           => CTF_Settings_Schema::to_json_properties($writable, 'write'),
						'additionalProperties' => false,
						'description'          => 'Quick settings applied on top of the template.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => $this->write_output_schema(array(
				'feed_id'   => array('type' => 'integer'),
				'feed_name' => array('type' => 'string'),
				'shortcode' => array('type' => 'string'),
			)),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_write'),
			'meta'                => $this->write_meta(false, false),
		);
	}

	public function execute($input = null)
	{
		return (new CTF_Feed_Settings_Service())->create_feed($this->normalize_input($input));
	}
}
