<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\CTF_Ability_Response;
use TwitterFeed\Abilities\Services\CTF_Feed_Settings_Service;
use TwitterFeed\Abilities\Services\CTF_Settings_Schema;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/update-feed-settings
 *
 * Gated by CTF_Write_Gate, then capability. Free has no license tiers, so
 * there is no `feature_unavailable` path; `ignored` reasons are only
 * `read_only` or `unknown_setting` (Pro's `tier_check()` is not ported —
 * see projects/custom-twitter-feeds/abilities-free-plan.md §4).
 *
 * @since 2.10.1
 */
class CTF_Update_Feed_Settings extends CTF_Ability
{
	public function slug()
	{
		return 'update-feed-settings';
	}

	public function args()
	{
		$writable = CTF_Settings_Schema::writable(CTF_Settings_Schema::feed_settings());
		$props    = array(
			'feed_id'  => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Feed id from list-feeds.',
			),
			'settings' => array(
				'type'                 => 'object',
				'properties'           => CTF_Settings_Schema::to_json_properties($writable, 'write'),
				'minProperties'        => 1,
				'additionalProperties' => false,
				'description'          => 'Settings to change. Only the keys you pass are changed; '
					. 'other stored values are re-saved as-is. To change what the feed shows, use '
					. 'set-feed-type-and-terms instead.',
			),
		);

		return array(
			'label'               => __('Update a Twitter Feed', 'custom-twitter-feeds'),
			'description'         => __('Changes one or more display settings of a feed (tweet count, header and bio, load-more button, colours, tweet card style, text length, height). Only the keys you pass are changed. Requires AI write access to be enabled in Twitter Feed → Settings → General. Returns updated and ignored keys. A setting that is not in this schema may be a Pro-only feature: call describe-settings-schema (pro_only) or get-pro-features.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => $props,
				'required'             => array('feed_id', 'settings'),
				'additionalProperties' => false,
			),
			'output_schema'       => $this->write_output_schema(array('feed_id' => array('type' => 'integer'))),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_write'),
			'meta'                => $this->write_meta(false, true),
		);
	}

	public function execute($input = null)
	{
		$input   = $this->normalize_input($input);
		$feed_id = isset($input['feed_id']) ? (int) $input['feed_id'] : 0;
		if ($feed_id <= 0) {
			return CTF_Ability_Response::invalid_feed_id();
		}
		$settings = isset($input['settings']) ? $this->normalize_input($input['settings']) : array();

		$service = new CTF_Feed_Settings_Service();
		$result  = $service->update_feed($feed_id, $settings);

		return $result === null ? CTF_Ability_Response::feed_not_found($feed_id) : $result;
	}
}
