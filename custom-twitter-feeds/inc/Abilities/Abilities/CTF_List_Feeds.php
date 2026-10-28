<?php

namespace TwitterFeed\Abilities\Abilities;

use TwitterFeed\Abilities\Services\CTF_Feed_Settings_Service;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * twitter-feed/list-feeds
 *
 * Free renders only `usertimeline` feeds in `list` layout, so the summary
 * drops the `layout` field (every free feed renders as `list`; see
 * projects/custom-twitter-feeds/abilities-free-plan.md §2 row 2). `type` stays
 * a raw read-only string: a feed created under Pro and downgraded to free can
 * still hold `hashtag`, `search`, `lists`, `mixed`, etc.
 *
 * @since 2.10.1
 */
class CTF_List_Feeds extends CTF_Ability
{
	public function slug()
	{
		return 'list-feeds';
	}

	public function args()
	{
		$summary = array(
			'type'       => 'object',
			'properties' => array(
				'id'            => array('type' => 'integer'),
				'feed_name'     => array('type' => 'string'),
				'feed_title'    => array('type' => 'string'),
				'type'          => array(
					'type'        => 'string',
					'description' => 'Stored source type. Only "usertimeline" renders in the free plugin; '
						. 'other values (e.g. from a Pro downgrade) are reported as-is but do not render.',
				),
				'status'        => array('type' => 'string'),
				'last_modified' => array('type' => 'string'),
				'shortcode'     => array('type' => 'string', 'description' => 'Shortcode that renders this feed.'),
			),
		);

		return array(
			'label'               => __('List Twitter Feeds', 'custom-twitter-feeds'),
			'description'         => __(
				'Lists the feeds created in the Feed Builder with their id, name, type and shortcode. Paginated.',
				'custom-twitter-feeds'
			),
			'input_schema'        => array(
				// null is accepted because a bare GET passes null input (all params are optional).
				'type'                 => array('object', 'null'),
				'properties'           => array(
					'page'     => array('type' => 'integer', 'minimum' => 1, 'default' => 1),
					'per_page' => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => CTF_Feed_Settings_Service::MAX_PER_PAGE,
						'default' => 20,
					),
					'status'   => array(
						'type'        => 'string',
						'enum'        => array('publish', 'draft', 'trash', 'all'),
						'default'     => 'publish',
						'description' => 'Filter by feed status; "all" returns every feed.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'feeds'       => array('type' => 'array', 'items' => $summary),
					'total'       => array('type' => 'integer'),
					'page'        => array('type' => 'integer'),
					'per_page'    => array('type' => 'integer'),
					'total_pages' => array('type' => 'integer'),
				),
			),
			'execute_callback'    => array($this, 'execute'),
			'permission_callback' => array($this, 'can_manage'),
			'meta'                => $this->read_meta(true),
		);
	}

	public function execute($input = null)
	{
		$input = $this->normalize_input($input);
		return (new CTF_Feed_Settings_Service())->list_feeds(
			isset($input['page']) ? (int) $input['page'] : 1,
			isset($input['per_page']) ? (int) $input['per_page'] : 20,
			isset($input['status']) ? (string) $input['status'] : 'publish'
		);
	}
}
