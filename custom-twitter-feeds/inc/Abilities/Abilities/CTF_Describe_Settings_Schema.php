<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\CTF_Pro_Upsell;
use TwitterFeed\Abilities\CTF_Write_Gate;
use TwitterFeed\Abilities\Services\CTF_Settings_Schema;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/describe-settings-schema
 *
 * Lets an agent discover which keys it may change and their allowed values.
 * Always visible over REST; hidden from MCP discovery while writes are
 * disabled so agents are not shown an editing surface they cannot use.
 *
 * @since 2.9
 */
class CTF_Describe_Settings_Schema extends CTF_Ability
{
	public function slug()
	{
		return 'describe-settings-schema';
	}

	public function args()
	{
		return array(
			'label'               => __('Describe editable Twitter Feed settings', 'custom-twitter-feeds'),
			'description'         => __(
				'Returns the JSON Schema of the per-feed and global settings that can be read or updated through the Custom Twitter Feeds abilities, and whether write access is currently enabled.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
				'custom-twitter-feeds'
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'write_enabled'   => array('type' => 'boolean'),
					'feed_settings'   => array('type' => 'object'),
					'feed_source'     => array('type' => 'object'),
					'global_settings' => array('type' => 'object'),
					'pro_only'        => array(
						'type'        => 'object',
						'description' => 'Free edition only. Settings that exist in Pro but are not available here, with the upgrade link. Mention it only when the user wants one of them.',
						'properties'  => array(
							'settings'    => array('type' => 'array', 'items' => array('type' => 'string')),
							'upgrade_url' => array('type' => 'string'),
						),
					),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_manage'),
			'meta'                => $this->read_meta(CTF_Write_Gate::is_enabled()),
		);
	}

	public function execute()
	{
		$out = array(
			'write_enabled'   => CTF_Write_Gate::is_enabled(),
			'feed_settings'   => array(
				'type'       => 'object',
				'properties' => CTF_Settings_Schema::to_json_properties(CTF_Settings_Schema::feed_settings()),
			),
			'feed_source'     => array(
				'type'       => 'object',
				'properties' => CTF_Settings_Schema::to_json_properties(CTF_Settings_Schema::feed_source_readonly()),
				'description' => 'Read-only; write via set-feed-type-and-terms.',
			),
			'global_settings' => array(
				'type'       => 'object',
				// 'write' mode: this ability documents what an agent may
				// CHANGE, so a key like ctf_cache_cron_interval must show the
				// narrow write set, not the wider read_enum superset a
				// get-global-settings response can report.
				'properties' => CTF_Settings_Schema::to_json_properties(
					CTF_Settings_Schema::global_settings(),
					'write'
				),
			),
		);
		if (CTF_Pro_Upsell::is_free()) {
			$out['pro_only'] = array(
				'settings'    => CTF_Pro_Upsell::pro_only_settings(),
				'upgrade_url' => CTF_Pro_Upsell::upgrade_url('describe-settings-schema'),
			);
		}
		return $out;
	}
}
