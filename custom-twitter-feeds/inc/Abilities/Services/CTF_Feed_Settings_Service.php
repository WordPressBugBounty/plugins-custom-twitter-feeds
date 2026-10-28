<?php

namespace TwitterFeed\Abilities\Services;

use TwitterFeed\Abilities\CTF_Pro_Upsell;
use TwitterFeed\Builder\CTF_Db;
use TwitterFeed\Builder\CTF_Feed_Saver;
use TwitterFeed\Builder\CTF_Feed_Saver_Manager;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Reads/writes Feed Builder feeds for the Abilities API (free edition).
 *
 * Wraps CTF_Db / CTF_Feed_Saver and REDACTS everything an agent must not see:
 * CTF_Feed_Saver::settings_defaults() carries consumer_key, consumer_secret,
 * access_token and access_token_secret AS PER-FEED DEFAULTS — this service
 * builds every response from the CTF_Settings_Schema allowlist, never by
 * filtering the hydrated array, so those keys can never appear in output.
 *
 * Free has no license tiers: there is no tier-available callable anywhere in
 * this class (contrast the Pro service). `ignored` reasons are only
 * `read_only` or `unknown_setting`.
 *
 * @since 2.10.1
 */
class CTF_Feed_Settings_Service
{
	const MAX_PER_PAGE = 50;

	/**
	 * Most feeds the free plugin allows. The Feed Builder enforces it
	 * client-side (builder.js createNewFeed(): `feedsList.length >= 1` opens the
	 * "Create more than one feed with Twitter Feeds Pro" dialog) and the All
	 * Feeds screen marks any extra feed as disabled; there is no server-side
	 * constant, so create_feed() applies the same limit here. Pro has no limit.
	 */
	const FREE_FEED_LIMIT = 1;

	/**
	 * Per-feed keys whose change alters which tweets are fetched from
	 * Twitter/X. Free's word/retweet/reply filters are inert (CtfFeed.php
	 * hard-codes includereplies=false, selfreplies=false, includeretweets=true;
	 * filterTweetSet() never applies includewords/excludewords), so unlike
	 * Pro's RETRIEVAL_KEYS, only `num` changes what free actually fetches.
	 *
	 * @var string[]
	 */
	const RETRIEVAL_KEYS = array('num');

	/**
	 * Feed templates the builder ships (CTF_Feed_Builder::get_feed_templates(),
	 * inc/Admin/Traits/CTF_Feed_Templates_Settings.php). Used for the READ side
	 * of the per-feed `feedtemplate` key, so a feed downgraded from Pro (or
	 * created before this ability existed) reads back honestly.
	 *
	 * @var string[]
	 */
	const FEED_TEMPLATES = array(
		'default',
		'masonry_cards',
		'simple_carousel',
		'simple_cards',
		'showcase_carousel',
		'latest_tweet',
		'widget',
	);

	/**
	 * Templates create-feed will accept: only the ones whose expanded
	 * settings set `layout => 'list'` (free's only renderable layout) — see
	 * projects/custom-twitter-feeds/abilities-free-plan.md §3a row
	 * `feedtemplate` and AgDR-9005 §2 ("a key whose write enum collapses...").
	 * `masonry_cards`, `simple_carousel` and `showcase_carousel` set layout
	 * to masonry/carousel and are dropped from the write side.
	 *
	 * @var string[]
	 */
	const CREATE_FEED_TEMPLATES = array('default', 'simple_cards', 'latest_tweet', 'widget');

	/**
	 * Source types an agent may configure in the free plugin. Free renders
	 * only `usertimeline` feeds (CTF_Feed_Builder's other types are upsell
	 * popups, commented out, or unreachable in free's builder) — see
	 * projects/custom-twitter-feeds/abilities-free-plan.md §2 row 9.
	 *
	 * @var array<string, null>
	 */
	const SOURCE_TYPES = array(
		'usertimeline' => null,
	);

	/**
	 * @param int    $page 1-based.
	 * @param int    $per_page
	 * @param string $status publish|draft|trash|all (default publish).
	 * @return array{feeds: array, total: int, page: int, per_page: int, total_pages: int}
	 */
	public function list_feeds($page = 1, $per_page = 20, $status = 'publish')
	{
		global $wpdb;

		$page     = max(1, (int) $page);
		$per_page = min(self::MAX_PER_PAGE, max(1, (int) $per_page));
		$offset   = ($page - 1) * $per_page;
		$table    = $wpdb->prefix . 'ctf_feeds';

		$status = in_array($status, array('publish', 'draft', 'trash', 'all'), true) ? $status : 'publish';
		if ($status === 'all') {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is $wpdb->prefix + constant.
			$sql = $wpdb->prepare(
				"SELECT id, feed_name, feed_title, settings, status, last_modified
				FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is $wpdb->prefix + constant.
			$sql = $wpdb->prepare(
				"SELECT id, feed_name, feed_title, settings, status, last_modified
				FROM {$table} WHERE status = %s ORDER BY id ASC LIMIT %d OFFSET %d",
				$status,
				$per_page,
				$offset
			);
		}
		$rows = $wpdb->get_results($sql, ARRAY_A);

		$feeds = array();
		foreach ((array) $rows as $row) {
			$settings = json_decode($row['settings'], true);
			$settings = is_array($settings) ? $settings : array();
			$feeds[]  = array(
				'id'            => (int) $row['id'],
				'feed_name'     => (string) $row['feed_name'],
				'feed_title'    => (string) $row['feed_title'],
				'type'          => isset($settings['type']) ? (string) $settings['type'] : 'usertimeline',
				'status'        => (string) $row['status'],
				'last_modified' => (string) $row['last_modified'],
				'shortcode'     => '[custom-twitter-feed feed=' . (int) $row['id'] . ']',
			);
		}

		if ($status === 'all') {
			$total = (int) CTF_Db::feeds_count();
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is $wpdb->prefix + constant.
			$total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", $status));
		}

		return array(
			'feeds'       => $feeds,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil($total / max(1, $per_page)),
		);
	}

	/**
	 * @param int $feed_id
	 * @return array|null Null when the feed does not exist.
	 */
	public function get_feed($feed_id)
	{
		$feed_id = (int) $feed_id;
		if ($feed_id <= 0) {
			return null;
		}

		$saver    = new CTF_Feed_Saver($feed_id);
		$settings = $saver->get_feed_settings();
		if (!is_array($settings)) {
			return null;
		}
		$db = $saver->get_feed_db_data();

		$defaults = CTF_Feed_Saver::settings_defaults();
		$schema   = CTF_Settings_Schema::feed_settings();
		$out      = array();
		foreach ($schema as $key => $def) {
			$out[$key] = CTF_Settings_Schema::cast_out(
				$def,
				isset($settings[$key]) ? $settings[$key] : null,
				isset($defaults[$key]) ? $defaults[$key] : null
			);
		}
		// textlength's plugin-level default (280) is resolved HERE, before
		// cast_out(), because settings_defaults()['textlength'] is '5'... no —
		// settings_defaults() already carries '280' as the stored default, but
		// an EMPTY stored value (legacy rows saved before this key existed, or
		// an explicit "" write) must resolve to 280, not to cast_out()'s normal
		// "numeric-or-default" fallback (which would treat '' as non-numeric and
		// also land on 280 via $defaults — so this is a safety net for future
		// drift, not a live divergence today).
		if (isset($settings['textlength']) && $settings['textlength'] === '') {
			$out['textlength'] = 280;
		}
		$out['feed_name'] = isset($db['feed_name']) ? (string) $db['feed_name'] : $out['feed_name'];

		$source_schema = CTF_Settings_Schema::feed_source_readonly();
		$source        = array();
		foreach ($source_schema as $key => $def) {
			$source[$key] = CTF_Settings_Schema::cast_out(
				$def,
				isset($settings[$key]) ? $settings[$key] : null,
				isset($defaults[$key]) ? $defaults[$key] : null
			);
		}

		$warnings = array();
		if (isset($source['type']) && $source['type'] !== 'usertimeline') {
			$warnings[] = sprintf(
				/* translators: %s: stored source type */
				__(
					'This feed\'s stored source type is "%s", which does not render in the free plugin. Use set-feed-type-and-terms to switch it to usertimeline.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
					'custom-twitter-feeds'
				),
				$source['type']
			);
		}

		return array(
			'id'            => $feed_id,
			'feed_title'    => isset($db['feed_title']) ? (string) $db['feed_title'] : '',
			'status'        => isset($db['status']) ? (string) $db['status'] : '',
			'last_modified' => isset($db['last_modified']) ? (string) $db['last_modified'] : '',
			'shortcode'     => '[custom-twitter-feed feed=' . $feed_id . ']',
			'source'        => $source,
			'settings'      => $out,
			'warnings'      => $warnings,
		);
	}

	/**
	 * Apply a partial update to one feed's settings.
	 *
	 * CTF_Feed_Saver::update() REPLACES the stored settings JSON with whatever
	 * was passed to set_data(), so we merge into the RAW stored row (the
	 * `settings` column, decoded) — Pro-only stored keys (e.g. `layout:
	 * carousel`, `includewords`, from a Pro→free downgrade) survive untouched
	 * so a later upgrade restores them.
	 *
	 * @param int   $feed_id
	 * @param array $partial key => value, already schema-validated by core.
	 * @return array{feed_id: int, updated: string[], ignored: object, warnings: string[]}|\WP_Error|null
	 *         Null when the feed does not exist.
	 */
	public function update_feed($feed_id, array $partial)
	{
		$feed_id = (int) $feed_id;
		if ($feed_id <= 0) {
			return null;
		}
		$rows = CTF_Db::feeds_query(array('id' => $feed_id));
		if (empty($rows)) {
			return null;
		}
		$row    = $rows[0];
		$stored = json_decode($row['settings'], true);
		$stored = is_array($stored) ? $stored : array();

		$schema        = CTF_Settings_Schema::feed_settings();
		$writable      = CTF_Settings_Schema::writable($schema);
		$source_schema = CTF_Settings_Schema::feed_source_readonly();
		$updated       = array();
		$ignored       = array();
		$clean         = array();
		$new_name      = null;

		foreach ($partial as $key => $value) {
			if (!isset($schema[$key])) {
				$ignored[$key] = isset($source_schema[$key]) ? 'read_only' : 'unknown_setting';
				continue;
			}
			if (!isset($writable[$key])) {
				$ignored[$key] = 'read_only';
				continue;
			}
			$v = CTF_Settings_Schema::validate_in($schema[$key], $value, $key);
			if (is_wp_error($v)) {
				return $v;
			}
			if ($key === 'feed_name') {
				$new_name  = $v;
				$updated[] = $key;
				continue;
			}
			if ($key === 'textlength' && ($v === 0 || $v === '')) {
				// Resolve to the plugin default rather than storing a literal 0,
				// which CtfFeed's renderer would treat as "truncate to nothing"
				// rather than "use the plugin default".
				$v = 280;
			}
			// The builder stores scalars as strings and booleans as booleans (see
			// CTF_Feed_Saver_Manager::sanitize()/is_boolean()).
			$clean[$key] = is_bool($v) ? $v : (string) $v;
			$updated[]   = $key;
		}

		if (empty($clean) && $new_name === null) {
			return array(
				'feed_id'  => $feed_id,
				'updated'  => array(),
				'ignored'  => (object) $ignored,
				'warnings' => array(),
			);
		}

		$merged = array_merge($stored, $clean);

		$saver = new CTF_Feed_Saver($feed_id);
		$saver->set_feed_name($new_name !== null ? $new_name : (string) $row['feed_name']);
		$saver->set_data($merged);
		// CTF_Db update returns rows changed, or false on error. An idempotent
		// replay can legitimately change 0 rows.
		if ($saver->update_or_insert() === false) {
			return new \WP_Error(
				'ctf_feed_save_failed',
				__('The feed could not be saved.', 'custom-twitter-feeds'),
				array('status' => 500)
			);
		}

		$this->clear_feed_cache($feed_id, array_keys($clean));

		/**
		 * Fires after an AI agent updated a feed through the Abilities API.
		 *
		 * @param int      $feed_id
		 * @param string[] $updated Keys that were written.
		 */
		do_action('ctf_abilities_feed_settings_updated', $feed_id, $updated);

		return array(
			'feed_id'  => $feed_id,
			'updated'  => $updated,
			'ignored'  => (object) $ignored,
			'warnings' => array(),
		);
	}

	/**
	 * Create a feed the way the builder's new_insert path does: template
	 * defaults + validated quick settings. The new feed's source defaults to
	 * the connected account's own timeline (empty usertimeline_text); use
	 * set-feed-type-and-terms afterwards to point it at a specific handle.
	 *
	 * `feedtemplate` is restricted to CREATE_FEED_TEMPLATES (list-layout only):
	 * a masonry/carousel template would store a layout the free front-end
	 * cannot render, so an unsupported value is rejected with 400 rather than
	 * silently substituted — an agent should be told the template isn't
	 * available in free, not shown a feed that silently renders differently
	 * than requested.
	 *
	 * @param array $input {feed_name?, feedtemplate?, settings?}
	 * @return array|\WP_Error
	 */
	public function create_feed(array $input)
	{
		if (CTF_Pro_Upsell::is_free() && (int) CTF_Db::feeds_count() >= self::FREE_FEED_LIMIT) {
			$message = sprintf(
				/* translators: %d: number of feeds the free plugin allows */
				_n(
					'The free plugin supports %d feed and this site already has it, so no new feed was created. Edit the existing feed with update-feed-settings instead.',
					'The free plugin supports %d feeds and this site already has that many, so no new feed was created. Edit an existing feed with update-feed-settings instead.',
					self::FREE_FEED_LIMIT,
					'custom-twitter-feeds'
				),
				self::FREE_FEED_LIMIT
			);
			$hint = CTF_Pro_Upsell::hint(__('Creating more than one feed', 'custom-twitter-feeds'), 'multiple-feeds');
			if ($hint !== '') {
				$message .= ' ' . $hint;
			}
			return new \WP_Error(
				'ctf_feed_limit_reached',
				$message,
				array('status' => 403)
			);
		}

		$settings_data = array(
			'type'              => 'usertimeline',
			'usertimeline_text' => '',
			'dateformat'        => '1',
		);

		$ignored  = array();
		$warnings = array();

		$feedtemplate = !empty($input['feedtemplate']) ? (string) $input['feedtemplate'] : null;
		if ($feedtemplate !== null && !in_array($feedtemplate, self::CREATE_FEED_TEMPLATES, true)) {
			$message = sprintf(
				/* translators: %s: comma-separated allowed template names */
				__(
					'Invalid feedtemplate for the free plugin. Allowed values: %s',
					'custom-twitter-feeds'
				),
				implode(', ', self::CREATE_FEED_TEMPLATES)
			);
			// A masonry/carousel template exists (it passes the input schema, which
			// lists every builder template) but needs Pro: say so (AgDR-9006). A
			// value that is not a builder template at all is a typo, so no upsell.
			if (in_array($feedtemplate, self::FEED_TEMPLATES, true)) {
				$hint = CTF_Pro_Upsell::hint_for_feature('feedLayout');
				if ($hint !== '') {
					$message .= ' ' . $hint;
				}
			}
			return new \WP_Error(
				'ctf_invalid_value',
				$message,
				array('status' => 400)
			);
		}
		if ($feedtemplate !== null) {
			$settings_data['feedtemplate'] = $feedtemplate;
		}
		// Same template expansion the builder applies on new_insert. The trait
		// is mixed into CTF_Feed_Saver_Manager (inc/Admin/Traits/
		// CTF_Feed_Templates_Settings.php is a PHP trait, not a standalone
		// class in free — it has no static-callable class name of its own).
		$settings_data = CTF_Feed_Saver_Manager::get_feed_settings_by_feed_templates($settings_data);

		$schema  = CTF_Settings_Schema::feed_settings();
		$updated = array();
		if (!empty($input['settings']) && is_array($input['settings'])) {
			$writable      = CTF_Settings_Schema::writable($schema);
			$source_schema = CTF_Settings_Schema::feed_source_readonly();
			foreach ($input['settings'] as $key => $value) {
				if ($key === 'feed_name' || !isset($writable[$key])) {
					if (isset($schema[$key]) || $key === 'feed_name') {
						$ignored[$key] = 'read_only';
					} else {
						$ignored[$key] = isset($source_schema[$key]) ? 'read_only' : 'unknown_setting';
					}
					continue;
				}
				$v = CTF_Settings_Schema::validate_in($schema[$key], $value, $key);
				if (is_wp_error($v)) {
					return $v;
				}
				$settings_data[$key] = is_bool($v) ? $v : (string) $v;
				$updated[]           = $key;
			}
		}

		$feed_name = !empty($input['feed_name'])
			? sanitize_text_field((string) $input['feed_name'])
			: CTF_Db::feeds_query_name(__('Twitter Feed', 'custom-twitter-feeds'));

		$saver = new CTF_Feed_Saver(false);
		$saver->set_feed_name($feed_name);
		$saver->set_data($settings_data);
		if (!$saver->update_or_insert()) {
			return new \WP_Error(
				'ctf_feed_save_failed',
				__('The feed could not be created.', 'custom-twitter-feeds'),
				array('status' => 500)
			);
		}
		$feed_id = (int) $saver->get_feed_id();

		/** This action is documented in CTF_Feed_Settings_Service::update_feed(). */
		do_action('ctf_abilities_feed_settings_updated', $feed_id, $updated);

		return array(
			'feed_id'   => $feed_id,
			'feed_name' => $feed_name,
			'shortcode' => '[custom-twitter-feed feed=' . $feed_id . ']',
			'updated'   => $updated,
			'ignored'   => (object) $ignored,
			'warnings'  => $warnings,
		);
	}

	/**
	 * Set what a feed shows: the source TYPE (always `usertimeline` in free)
	 * and its TERMS (comma-separated @handles). Replaces set-feed-sources from
	 * the Instagram Feed pattern — Custom Twitter Feeds has no per-account
	 * source list, only one connected account and a type+terms pair per feed.
	 *
	 * @param int    $feed_id
	 * @param string $type Must be "usertimeline" in free.
	 * @param string $terms Handle(s), per $type. May be empty.
	 * @return array|\WP_Error|null Null when the feed does not exist.
	 */
	public function set_feed_type_and_terms($feed_id, $type, $terms)
	{
		$feed_id = (int) $feed_id;
		$rows    = CTF_Db::feeds_query(array('id' => $feed_id));
		if ($feed_id <= 0 || empty($rows)) {
			return null;
		}
		if (!array_key_exists($type, self::SOURCE_TYPES)) {
			return new \WP_Error(
				'ctf_invalid_value',
				sprintf(
					/* translators: %s: comma-separated allowed types */
					__('Invalid type. Allowed values: %s', 'custom-twitter-feeds'),
					implode(', ', array_keys(self::SOURCE_TYPES))
				),
				array('status' => 400)
			);
		}

		$terms = sanitize_text_field((string) $terms);
		if (mb_strlen($terms) > CTF_Settings_Schema::MAX_STRING_LENGTH) {
			return new \WP_Error(
				'ctf_invalid_value',
				sprintf(
					__('terms exceeds the maximum length of %d characters.', 'custom-twitter-feeds'),
					CTF_Settings_Schema::MAX_STRING_LENGTH
				),
				array('status' => 400)
			);
		}

		// Cleans/normalizes @handles (strips twitter.com/ URLs, adds "@") the
		// same way the builder does for a single-source usertimeline feed.
		$feed_data = CTF_Feed_Saver_Manager::create_single_feed_type_data($type, array($type => $terms));

		$stored = json_decode($rows[0]['settings'], true);
		$stored = is_array($stored) ? $stored : array();

		// A feed has exactly ONE source. UNCONDITIONALLY drop every OTHER
		// type's bare term key before writing the selected pair back in, so
		// "exactly one source survives" is a structural invariant of every
		// write — mirrors the same defensive clearing Pro's equivalent does.
		foreach (array('screenname', 'hashtag', 'search', 'lists', 'home', 'mentions') as $bare) {
			unset($stored[$bare]);
		}
		foreach (array('usertimeline_text', 'hashtag_text') as $text_key) {
			$stored[$text_key] = '';
		}

		$merged = array_merge($stored, $feed_data);

		$saver = new CTF_Feed_Saver($feed_id);
		$saver->set_feed_name((string) $rows[0]['feed_name']);
		$saver->set_data($merged);
		if ($saver->update_or_insert() === false) {
			return new \WP_Error(
				'ctf_feed_save_failed',
				__('The feed could not be saved.', 'custom-twitter-feeds'),
				array('status' => 500)
			);
		}

		// Source change always alters what is fetched from Twitter/X; 'type' is
		// not a RETRIEVAL_KEYS entry, so force the stored-tweets cache clear
		// directly rather than routing through clear_feed_cache()'s intersection
		// check (same reasoning as the Pro sibling ability).
		$this->clear_feed_cache($feed_id, array(), true);

		/** This action is documented in CTF_Feed_Settings_Service::update_feed(). */
		do_action('ctf_abilities_feed_settings_updated', $feed_id, array_keys($feed_data));

		return array(
			'feed_id'  => $feed_id,
			'type'     => $type,
			'terms'    => $terms,
			'warnings' => array(),
		);
	}

	/**
	 * Clear a feed's cache. The per-feed rendered/header cache rows (local,
	 * cheap) are always cleared so cosmetic changes show immediately. The
	 * stored-tweets cache is additionally cleared when a RETRIEVAL_KEYS key
	 * changed, or when $force_retrieval_clear says the change bypasses that
	 * key-list entirely (set-feed-type-and-terms). Free's native
	 * `builder_update()` clears `ctf_clear_cache_sql()` on EVERY update
	 * (parity choice, projects/custom-twitter-feeds/abilities-free-plan.md
	 * §4 "Cache/side effects on feed save" OQ-5) — this mirrors that by
	 * clearing unconditionally rather than gating on RETRIEVAL_KEYS, since
	 * free's own UI does the same and an agent should not diverge from it.
	 *
	 * @param int      $feed_id The feed to clear.
	 * @param string[] $changed_keys Settings keys that changed on this write.
	 * @param bool     $force_retrieval_clear Clear the stored-tweets cache regardless of $changed_keys.
	 */
	private function clear_feed_cache($feed_id, array $changed_keys, $force_retrieval_clear = false)
	{
		if (class_exists('TwitterFeed\Admin\CTF_Cache_Handler')) {
			(new \TwitterFeed\Admin\CTF_Cache_Handler())->clear_single_feed_cache($feed_id);
		}
		if (function_exists('ctf_clear_cache_sql')) {
			ctf_clear_cache_sql();
		}
	}
}
