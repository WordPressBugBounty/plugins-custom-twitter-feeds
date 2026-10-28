<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\CTF_Pro_Upsell;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/get-pro-features
 *
 * Read-only, FREE edition only (CTF_Abilities::abilities() does not register it
 * in Pro). Lets an agent answer "what else can this plugin do?" or explain why
 * a request can't be done in free. Content is derived from the plugin's own
 * upsell data via CTF_Pro_Upsell (AgDR-9006), never restated here.
 *
 * @since 2.10.1
 */
class CTF_Get_Pro_Features extends CTF_Ability
{
	public function slug()
	{
		return 'get-pro-features';
	}

	public function args()
	{
		return array(
			'label'               => __('Get Twitter Feed Pro features', 'custom-twitter-feeds'),
			'description'         => __(
				'Lists what the Pro edition adds to this free plugin, with the settings each feature unlocks and an upgrade link. Mention it to the user only when they ask for something the free edition cannot do, or ask what else the plugin can do.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
				'custom-twitter-feeds'
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'edition'     => array('type' => 'string', 'enum' => array('free')),
					'upgrade_url' => array('type' => 'string'),
					'features'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'        => array('type' => 'string'),
								'description' => array('type' => 'string'),
								'settings'    => array(
									'type'        => 'array',
									'items'       => array('type' => 'string'),
									'description' => 'Pro-only setting keys (or key=value) this feature unlocks. May be empty.',
								),
							),
						),
					),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_manage'),
			'meta'                => $this->read_meta(true),
		);
	}

	public function execute()
	{
		return array(
			'edition'     => 'free',
			'upgrade_url' => CTF_Pro_Upsell::upgrade_url('get-pro-features'),
			'features'    => CTF_Pro_Upsell::features(),
		);
	}
}
