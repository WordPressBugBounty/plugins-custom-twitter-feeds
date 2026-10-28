<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\Services\CTF_Source_Service;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/get-connect-url
 *
 * Free's own Connect button links directly to the OAuth relay with a
 * session-bound nonce and the admin's email (`ctf_oauth_url()`). A naive copy
 * of that link would fail to verify outside a browser session and would leak
 * the admin's email to the agent. This ability instead returns a wp-admin
 * bootstrap URL (CTF_Connect_Bootstrap) that builds the real link in the
 * admin's own browser — see
 * projects/custom-twitter-feeds/abilities-free-plan.md §4 and
 * docs/agdr/AgDR-9005-free-edition-abilities-edition-filtered-schema.md §5.
 *
 * @since 2.10.1
 */
class CTF_Get_Connect_Url extends CTF_Ability
{
	public function slug()
	{
		return 'get-connect-url';
	}

	public function args()
	{
		return array(
			'label'               => __('Get a Twitter/X account-connect link', 'custom-twitter-feeds'),
			'description'         => __('Returns a single-use link (valid 15 minutes) the site admin opens in their browser to connect a Twitter/X account via the Smash Balloon OAuth relay. The assistant never touches Twitter/X credentials or the admin\'s WordPress email. After connecting, use list-sources to confirm the account.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
			// Null-tolerant: POST abilities must define an input schema when any
			// input arrives; a bodyless POST passes null.
			'input_schema'        => array(
				'type'                 => array('object', 'null'),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'connect_url'  => array('type' => 'string'),
					'expires_in'   => array('type' => 'integer'),
					'instructions' => array('type' => 'string'),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_write'),
			'meta'                => $this->write_meta(false, false),
		);
	}

	public function execute()
	{
		return (new CTF_Source_Service())->get_connect_url();
	}
}
