<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\Services\CTF_Global_Settings_Service;
use TwitterFeed\Abilities\Services\CTF_Settings_Schema;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/get-global-settings
 *
 * @since 2.9
 */
class CTF_Get_Global_Settings extends CTF_Ability
{
	public function slug()
	{
		return 'get-global-settings';
	}

	public function args()
	{
		return array(
			'label'               => __('Get Twitter Feed global settings', 'custom-twitter-feeds'),
			'description'         => __(
				'Returns the plugin-wide Custom Twitter Feeds settings (caching schedule, GDPR, image/video handling, script loading). Never returns the connected account, tokens, app credentials or license data.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
				'custom-twitter-feeds'
			),
			// No input_schema: a bare GET passes null input, which an object schema rejects.
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array_merge(
					CTF_Settings_Schema::to_json_properties(CTF_Settings_Schema::global_settings()),
					array(
						'warnings' => array(
							'type'        => 'array',
							'items'       => array('type' => 'string'),
							'description' => 'Notes on stored values that fall outside what this setting '
								. 'can currently be written to (e.g. a legacy or Pro-downgrade value).',
						),
					)
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_manage'),
			'meta'                => $this->read_meta(true),
		);
	}

	public function execute()
	{
		return (new CTF_Global_Settings_Service())->get();
	}
}
