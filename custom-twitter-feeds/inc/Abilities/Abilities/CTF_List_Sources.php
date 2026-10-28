<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\Services\CTF_Source_Service;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/list-sources
 *
 * Custom Twitter Feeds connects ONE global account (unlike Instagram Feed's
 * per-account source list), so this returns a single redacted account object
 * rather than a list. Free has no license-tiered app-credential switch, so
 * the connection signal is the relay site token or a pair of own access
 * tokens — see projects/custom-twitter-feeds/abilities-free-plan.md §2 row 7.
 * Never returns the site token, access tokens or app credentials.
 *
 * @since 2.10.1
 */
class CTF_List_Sources extends CTF_Ability
{
	public function slug()
	{
		return 'list-sources';
	}

	public function args()
	{
		return array(
			'label'               => __('Get the connected Twitter/X account', 'custom-twitter-feeds'),
			'description'         => __('Returns whether a Twitter/X account is connected to this site, its handle, whether app credentials are on file, and whether the free Smash Balloon relay is being used instead of the site\'s own app credentials. Never returns tokens or app credentials.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
			// No input_schema: a bare GET passes null input, which an object schema rejects.
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'is_connected'        => array('type' => 'boolean'),
					'using_smash_relay'   => array(
						'type'        => 'boolean',
						'description' => 'True when using the free Smash Balloon relay instead of the '
							. 'site\'s own Twitter/X app credentials.',
					),
					'has_app_credentials' => array('type' => 'boolean'),
					'account_handle'      => array('type' => 'string'),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_manage'),
			'meta'                => $this->read_meta(true),
		);
	}

	public function execute()
	{
		return (new CTF_Source_Service())->get_account();
	}
}
