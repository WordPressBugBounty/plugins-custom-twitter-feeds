<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\CTF_Ability_Response;
use TwitterFeed\Abilities\Services\CTF_Feed_Settings_Service;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/set-feed-type-and-terms
 *
 * The canonical coupled-key ability (AgDR-9004 requirement 2): `type` and
 * `terms` only mean something together, so they are read-only on the generic
 * update-feed-settings ability and can only change through this validating
 * one. Free's write enum collapses to `['usertimeline']` —
 * `CTF_Feed_Settings_Service::SOURCE_TYPES` — since free has no hashtag,
 * search, lists, home or mentions timeline rendering. The id is kept
 * (unchanged from Pro) for upgrade/agent tool-list parity. Destructive by
 * annotation: changing the source changes what a feed shows.
 *
 * @since 2.10.1
 */
class CTF_Set_Feed_Type_And_Terms extends CTF_Ability
{
	public function slug()
	{
		return 'set-feed-type-and-terms';
	}

	public function args()
	{
		return array(
			'label'               => __('Set what a Twitter Feed shows', 'custom-twitter-feeds'),
			'description'         => __('Sets the source for a feed: usertimeline (one or more @handles, comma-separated — empty uses the connected account\'s own timeline). This is the only source type the free plugin renders. Use get-connect-url first if no account is connected yet. Requires AI write access.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'feed_id' => array('type' => 'integer', 'minimum' => 1),
					'type'    => array(
						'type' => 'string',
						'enum' => array_keys(CTF_Feed_Settings_Service::SOURCE_TYPES),
					),
					'terms'   => array(
						'type'        => 'string',
						'maxLength'   => 500,
						'description' => 'Comma-separated @handle(s). May be empty to use the connected '
							. 'account\'s own timeline.',
					),
				),
				'required'             => array('feed_id', 'type'),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'feed_id'  => array('type' => 'integer'),
					'type'     => array('type' => 'string'),
					'terms'    => array('type' => 'string'),
					// AgDR-0504 requirement 3 — see CTF_Ability::write_output_schema().
					'warnings' => array(
						'type'        => 'array',
						'items'       => array('type' => 'string'),
						'description' => 'Human-readable notices: a value was accepted but had no effect.',
					),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_write'),
			'meta'                => $this->write_meta(true, true),
		);
	}

	public function execute($input = null)
	{
		$input   = $this->normalize_input($input);
		$feed_id = isset($input['feed_id']) ? (int) $input['feed_id'] : 0;
		if ($feed_id <= 0) {
			return CTF_Ability_Response::invalid_feed_id();
		}
		$type   = isset($input['type']) ? (string) $input['type'] : '';
		$terms  = isset($input['terms']) ? (string) $input['terms'] : '';
		$result = (new CTF_Feed_Settings_Service())->set_feed_type_and_terms($feed_id, $type, $terms);

		return $result === null ? CTF_Ability_Response::feed_not_found($feed_id) : $result;
	}
}
