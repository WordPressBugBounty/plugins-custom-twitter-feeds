<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\Services\CTF_Global_Settings_Service;
use TwitterFeed\Abilities\Services\CTF_Settings_Schema;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/update-global-settings
 *
 * Gated by CTF_Write_Gate (off by default), then the same capability as the
 * Settings page. Unknown keys are rejected by core (additionalProperties:false);
 * read-only keys are reported in `ignored`.
 *
 * @since 2.9
 */
class CTF_Update_Global_Settings extends CTF_Ability
{
	public function slug()
	{
		return 'update-global-settings';
	}

	public function args()
	{
		$writable = CTF_Settings_Schema::writable(CTF_Settings_Schema::global_settings());

		return array(
			'label'               => __('Update Twitter Feed global settings', 'custom-twitter-feeds'),
			'description'         => __('Updates one or more plugin-wide Custom Twitter Feeds settings (GDPR mode, rebranding, persistent cache, loading feeds via AJAX). The caching schedule and custom templates are read-only. Requires AI write access to be enabled in Twitter Feed → Settings → General. Returns the keys that were updated and the keys that were ignored.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => CTF_Settings_Schema::to_json_properties($writable, 'write'),
				'minProperties'        => 1,
				'additionalProperties' => false,
			),
			'output_schema'       => $this->write_output_schema(),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_write'),
			'meta'                => $this->write_meta(false, true),
		);
	}

	public function execute($input = null)
	{
		return (new CTF_Global_Settings_Service())->update($this->normalize_input($input));
	}
}
