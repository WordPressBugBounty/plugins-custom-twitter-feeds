<?php

namespace TwitterFeed\Abilities\Services;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Typed allowlist of the settings exposed through the Abilities API — the
 * FREE edition's subset of the Pro schema (custom-twitter-feeds-pro#750).
 *
 * This is the single source of truth for what an AI agent may read or write
 * in the free plugin. Anything not listed here is never exposed — connected
 * account, access tokens, app credentials, custom CSS/JS and usage tracking
 * are deliberately absent. Free has NO license tiers, so there is no `tier` /
 * `x-license-feature` metadata at all (contrast the Pro schema).
 *
 * Every enum, minimum, maximum and default below is a LITERAL, pinned by
 * tests/Unit/Abilities/CTF_Settings_Schema_Drift_Test.php against the free
 * plugin's own control definitions (CTF_Customize_Tab, CTF_Styling_Tab,
 * CTF_Settings_Tab, CTF_Feed_Saver::settings_defaults(),
 * CTF_Global_Settings::default_settings_options()) — see
 * projects/custom-twitter-feeds/abilities-free-plan.md §3 and
 * docs/agdr/AgDR-9005-free-edition-abilities-edition-filtered-schema.md §2.
 * Nothing here walks the control tree at runtime. Where a free control
 * declares no bound, none is declared here (no invented maximums).
 *
 * @since 2.10.1
 */
class CTF_Settings_Schema
{
	const MAX_STRING_LENGTH = 500;

	/** Hex colour: #rgb, #rgba, #rrggbb, #rrggbbaa — or empty (plugin default). */
	const COLOR_PATTERN = '^(#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8}))?$';

	/**
	 * Per-feed settings free actually renders and lets the admin control.
	 * Free renders ONLY `usertimeline` feeds in `list` layout — masonry,
	 * carousel, autoscroll, lightbox and the five word/retweet/reply filter
	 * keys have no free front-end code and are absent (not `feature_unavailable`).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function feed_settings()
	{
		return array(
			'feed_name' => array(
				'type'        => 'string',
				'description' => 'Internal feed name shown in the Feed Builder list.',
			),
			'num' => array(
				'type'        => 'integer',
				// CTF_Customize_Tab 'num' control: min => 1, no max declared.
				'minimum'     => 1,
				'description' => 'Number of tweets to display.',
			),
			'showheader' => array(
				'type'        => 'boolean',
				'description' => 'Show the feed header.',
			),
			'headerstyle' => array(
				'type'        => 'string',
				'enum'        => array('standard', 'text'),
				'description' => 'Header style.',
			),
			'showbio' => array(
				'type'        => 'boolean',
				'description' => 'Show the account bio in the header (headerstyle=standard only).',
			),
			'customheadertext' => array(
				'type'        => 'string',
				'description' => 'Text-style header content.',
			),
			'customheadersize' => array(
				'type'        => 'string',
				'enum'        => array('small', 'medium', 'large'),
				'description' => 'Text-style header size.',
			),
			'showbutton' => array(
				'type'        => 'boolean',
				'description' => 'Show the Load More button.',
			),
			'buttontext' => array(
				'type'        => 'string',
				'description' => 'Load More button label.',
			),
			'colorpalette' => array(
				'type'        => 'string',
				'enum'        => array('inherit', 'light', 'dark', 'custom'),
				'description' => 'Colour palette.',
			),
			'custombgcolor' => array(
				'type'        => 'string',
				'format'      => 'color',
				'description' => 'Custom-palette background colour.',
			),
			'customaccentcolor' => array(
				'type'        => 'string',
				'format'      => 'color',
				'description' => 'Custom-palette accent colour.',
			),
			'customtextcolor1' => array(
				'type'        => 'string',
				'format'      => 'color',
				'description' => 'Custom-palette primary text colour.',
			),
			'customtextcolor2' => array(
				'type'        => 'string',
				'format'      => 'color',
				'description' => 'Custom-palette secondary text colour.',
			),
			'customlinkcolor' => array(
				'type'        => 'string',
				'format'      => 'color',
				'description' => 'Custom-palette link colour.',
			),
			'tweetpoststyle' => array(
				'type'        => 'string',
				'enum'        => array('regular', 'boxed'),
				'description' => 'Individual tweet style.',
			),
			'tweetbgcolor' => array(
				'type'        => 'string',
				'format'      => 'color',
				'description' => 'Boxed tweet background colour.',
			),
			'tweetcorners' => array(
				'type'        => 'integer',
				// CTF_Customize_Tab 'tweetcorners' control declares neither a min
				// nor a max; `minimum` 0 kept because a negative CSS border-radius
				// has no meaning (AgDR-9005: no invented maximum).
				'minimum'     => 0,
				'description' => 'Boxed tweet corner radius (px).',
			),
			'tweetboxshadow' => array(
				'type'        => 'boolean',
				'description' => 'Boxed tweet shadow.',
			),
			'textlength' => array(
				'type'        => 'integer',
				// CTF_Styling_Tab 'textlength' control declares no min/max.
				'minimum'     => 0,
				'description' => 'Max tweet text characters. Empty/0 resolves to the plugin default (280).',
			),
			'height' => array(
				'type'        => 'integer',
				// CTF_Customize_Tab 'height' control has a `px` suffix and no
				// declared max; `minimum` 0 is also the "auto" sentinel.
				'minimum'     => 0,
				'description' => 'Feed height in pixels (0 = auto). Free has no height unit control — '
					. 'the value always renders in px.',
			),
			'feedtemplate' => array(
				'type'        => 'string',
				'writable'    => false,
				'description' => 'Feed template applied at creation time. Read-only after creation; '
					. 'set it via create-feed. A stored value outside the free create-feed enum '
					. '(e.g. from a Pro downgrade) is reported as-is.',
			),
		);
	}

	/**
	 * Read-only, per-feed properties describing the CONFIGURED source — the
	 * mirror of what set-feed-type-and-terms most recently wrote. Never
	 * writable directly: the only write path for a feed's source is the
	 * dedicated set-feed-type-and-terms ability.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function feed_source_readonly()
	{
		return array(
			'type' => array(
				'type'        => 'string',
				// Read enum covers every value free can ever STORE, not just what
				// it can WRITE (AgDR-9004/9005 downgrade rule): a feed created
				// under Pro and downgraded to free, or a feed combining more than
				// one source ('mixed'), must read back honestly rather than being
				// coerced to 'usertimeline'. Only 'usertimeline' renders in free.
				'enum'        => array_merge(
					array_keys(CTF_Feed_Settings_Service::SOURCE_TYPES),
					array('hometimeline', 'mentionstimeline', 'hashtag', 'search', 'lists', 'mixed')
				),
				'readOnly'    => true,
				'description' => 'Source type currently configured for the feed. Only "usertimeline" '
					. 'renders in the free plugin; other values (e.g. from a Pro downgrade) are '
					. 'reported as-is but do not render.',
			),
			'usertimeline_text' => array(
				'type'        => 'string',
				'readOnly'    => true,
				'description' => 'Configured @handle(s) when type is usertimeline.',
			),
		);
	}

	/**
	 * Global (plugin-wide) settings stored in the `ctf_options` option.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function global_settings()
	{
		return array(
			'ctf_caching_type' => array(
				'type'        => 'string',
				'enum'        => array('background'),
				'description' => 'How feeds are refreshed. The Settings UI only offers "background"; read-only.',
				'writable'    => false,
			),
			// The three cron keys are READ-ONLY (QA, SBR-511): the free Settings UI
			// hides the whole caching control, and "update feeds more often" is
			// sold as Pro. Their enums stay so reads still validate.
			'ctf_cache_cron_interval' => array(
				'type'        => 'string',
				// inTheBackgroundOptions has '30mins' too, but the Settings UI
				// disables that option (feeds.php ':disabled="key === \'30mins\'"'),
				// so it is not offered as a WRITE value. Pro-only '2hour' is
				// never offered either. `enum` therefore stays the narrow
				// write-only set (validate_in() and the update ability's input
				// schema both key off it).
				'enum'        => array('1hour', '12hours', '24hours'),
				// `read_enum` is the READ superset (AgDR-9004/9005 downgrade
				// rule): '30mins' is a real, still-scheduled legacy value
				// (SB_Twitter_Cron_Updater::start_cron_job() has a live
				// 'ctf30mins' branch), and '2hour' can be left behind by a
				// Pro→Free downgrade. A read must report either verbatim, not
				// clamp it to the default — only the write path is restricted.
				'read_enum'   => array('30mins', '1hour', '2hour', '12hours', '24hours'),
				'description' => 'Background cache refresh interval. Read-only: the Settings UI hides this control in the free plugin (updating feeds more often is a Pro feature).',
				'writable'    => false,
			),
			'ctf_cache_cron_time' => array(
				'type'        => 'string',
				'enum'        => array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'),
				'description' => 'Hour for the daily (24hours) refresh. Read-only in the free plugin.',
				'writable'    => false,
			),
			'ctf_cache_cron_am_pm' => array(
				'type'        => 'string',
				'enum'        => array('am', 'pm'),
				'description' => 'AM/PM for the daily refresh hour. Read-only in the free plugin.',
				'writable'    => false,
			),
			'gdpr' => array(
				'type'        => 'string',
				'enum'        => array('auto', 'yes', 'no'),
				'description' => 'GDPR mode, as set in Settings → Feeds. "auto" turns it on only when '
					. 'a supported consent plugin is active, "yes" always on, "no" always off. '
					. 'While on, feeds skip third-party requests (X/Twitter media, scripts) until the '
					. 'visitor consents. It affects legal compliance, so confirm the change with the '
					. 'site owner first.',
			),
			'rebranding' => array(
				'type'        => 'boolean',
				'description' => 'Show "X" branding instead of "Twitter" across the plugin.',
			),
			'persistentcache' => array(
				'type'        => 'boolean',
				'description' => 'Permanently cache Search and Hashtag feed tweets in the database. '
					. 'Affects legacy hashtag/search feeds and own-credential sites only.',
			),
			'ajax_theme' => array(
				'type'        => 'boolean',
				'description' => 'Load feeds via AJAX (for themes that break the initial load).',
			),
			'customtemplates' => array(
				'type'        => 'boolean',
				'description' => 'Allow overriding feed HTML with templates in the active theme. Read-only: change it in the Settings page.',
				'writable'    => false,
			),
		);
	}

	/**
	 * Build a JSON Schema `properties` map from a table (drops private keys).
	 *
	 * @param array  $table
	 * @param string $mode 'read' (default) uses the wider `read_enum` when a
	 *                     key declares one, so a legally-stored superset value
	 *                     (a legacy value, or one left behind by a Pro
	 *                     downgrade) doesn't fail core's output-schema
	 *                     validation. 'write' always uses the narrow `enum` —
	 *                     the input schema for an update/create ability must
	 *                     stay restricted to what the free plugin actually
	 *                     offers.
	 * @return array
	 */
	public static function to_json_properties(array $table, $mode = 'read')
	{
		$out = array();
		foreach ($table as $key => $def) {
			$prop = array('type' => $def['type']);
			$enum_key = ($mode !== 'write' && isset($def['read_enum'])) ? 'read_enum' : 'enum';
			foreach (array('minimum', 'maximum', 'description') as $k) {
				if (isset($def[$k])) {
					$prop[$k] = $def[$k];
				}
			}
			if (isset($def[$enum_key])) {
				$prop['enum'] = $def[$enum_key];
			}
			if ($def['type'] === 'string' && !isset($def['enum'])) {
				$prop['maxLength'] = self::MAX_STRING_LENGTH;
			}
			if (isset($def['format']) && $def['format'] === 'color') {
				$prop['pattern']     = self::COLOR_PATTERN;
				$prop['description'] = (isset($prop['description']) ? $prop['description'] . ' ' : '')
					. 'Hex colour (#rgb, #rrggbb or #rrggbbaa) or empty for the default.';
			}
			if (isset($def['readOnly']) && $def['readOnly'] === true) {
				$prop['readOnly'] = true;
			} elseif (isset($def['writable']) && $def['writable'] === false) {
				$prop['readOnly'] = true;
			}
			$out[$key] = $prop;
		}
		return $out;
	}

	/**
	 * Cast a raw stored value to the declared type for output.
	 *
	 * Stored data is not guaranteed to satisfy the schema (legacy installs,
	 * import/export, a bad save, or a Pro downgrade). Core validates our
	 * OUTPUT against the schema and fails the whole ability on one bad value,
	 * so anything that does not satisfy the declared enum/range falls back to
	 * $default (the plugin's own default for that key) rather than being
	 * passed through. Reads NEVER clamp or coerce a legally stored value that
	 * DOES satisfy the schema (e.g. a superset enum member) — only genuinely
	 * invalid values fall back.
	 *
	 * @param array $def     Schema entry for the key.
	 * @param mixed $value   Raw stored value (null when missing).
	 * @param mixed $default Plugin default for the key.
	 * @return mixed
	 */
	public static function cast_out(array $def, $value, $default = null)
	{
		switch ($def['type']) {
			case 'boolean':
				if ($value === null) {
					$value = $default;
				}
				return $value === true || $value === 'true' || $value === 1 || $value === '1' || $value === 'on';

			case 'integer':
				if ($value === null || $value === '' || !is_numeric($value)) {
					$value = is_numeric($default) ? $default : 0;
				}
				$int = (int) $value;
				if (isset($def['minimum']) && $int < $def['minimum']) {
					$int = (int) $def['minimum'];
				}
				if (isset($def['maximum']) && $int > $def['maximum']) {
					$int = (int) $def['maximum'];
				}
				return $int;

			default:
				$str = is_scalar($value) ? (string) $value : '';

				$is_color = isset($def['format']) && $def['format'] === 'color';
				if ($is_color && !preg_match('/' . self::COLOR_PATTERN . '/', $str)) {
					$fallback_str = is_scalar($default) ? (string) $default : '';
					$str          = preg_match('/' . self::COLOR_PATTERN . '/', $fallback_str) ? $fallback_str : '';
				}

				// A read validity check is against the SUPERSET (`read_enum`
				// when the key declares one, else the plain `enum`) — a
				// legally stored value outside the narrower write set (a
				// legacy value, or one left by a Pro downgrade) must be
				// reported as-is, not clamped to the default. Only a value
				// outside BOTH sets is genuinely invalid and falls back.
				$valid_enum = isset($def['read_enum'])
					? $def['read_enum']
					: (isset($def['enum']) ? $def['enum'] : null);
				if ($valid_enum !== null && !in_array($str, $valid_enum, true)) {
					$fallback = is_scalar($default) ? (string) $default : '';
					$str      = in_array($fallback, $valid_enum, true) ? $fallback : (string) $valid_enum[0];
				}

				if (!isset($def['enum'])) {
					$max = isset($def['maxLength']) ? (int) $def['maxLength'] : self::MAX_STRING_LENGTH;
					if (mb_strlen($str) > $max) {
						$str = mb_substr($str, 0, $max);
					}
				}

				return $str;
		}
	}

	/**
	 * Writable subset of a table (keys not flagged `writable => false`).
	 *
	 * @param array $table
	 * @return array
	 */
	public static function writable(array $table)
	{
		return array_filter(
			$table,
			function ($def) {
				return !isset($def['writable']) || $def['writable'] !== false;
			}
		);
	}

	/**
	 * Validate + normalise ONE incoming value for storage.
	 *
	 * Core has already validated the request against the JSON Schema, so this
	 * is the second, PHP-side line: it never trusts the transport. Returns the
	 * value to store, or a WP_Error (code ctf_invalid_value, status 400).
	 *
	 * @param array  $def
	 * @param mixed  $value
	 * @param string $key
	 * @return mixed|\WP_Error
	 */
	public static function validate_in(array $def, $value, $key)
	{
		$bad = function ($reason) use ($key) {
			return new \WP_Error(
				'ctf_invalid_value',
				sprintf(
					/* translators: 1: setting key, 2: reason */
					__('Invalid value for %1$s: %2$s', 'custom-twitter-feeds'),
					$key,
					$reason
				),
				array('status' => 400)
			);
		};

		switch ($def['type']) {
			case 'boolean':
				if (is_bool($value)) {
					return $value;
				}
				if (in_array($value, array('true', '1', 1, 'on'), true)) {
					return true;
				}
				if (in_array($value, array('false', '0', 0, 'off', ''), true)) {
					return false;
				}
				return $bad(__('expected a boolean', 'custom-twitter-feeds'));

			case 'integer':
				if (!is_numeric($value) || (float) $value !== (float) (int) $value) {
					return $bad(__('expected an integer', 'custom-twitter-feeds'));
				}
				$int = (int) $value;
				if (isset($def['minimum']) && $int < $def['minimum']) {
					return $bad(sprintf(__('minimum is %d', 'custom-twitter-feeds'), $def['minimum']));
				}
				if (isset($def['maximum']) && $int > $def['maximum']) {
					return $bad(sprintf(__('maximum is %d', 'custom-twitter-feeds'), $def['maximum']));
				}
				return $int;

			default:
				if (!is_scalar($value)) {
					return $bad(__('expected a string', 'custom-twitter-feeds'));
				}
				$str = sanitize_text_field((string) $value);
				if (isset($def['enum']) && !in_array($str, $def['enum'], true)) {
					return $bad(sprintf(__('allowed values: %s', 'custom-twitter-feeds'), implode(', ', $def['enum'])));
				}
				$is_color = isset($def['format']) && $def['format'] === 'color';
				if ($is_color && !preg_match('/' . self::COLOR_PATTERN . '/', $str)) {
					return $bad(__('expected a hex colour such as #408bd1', 'custom-twitter-feeds'));
				}
				if (mb_strlen($str) > self::MAX_STRING_LENGTH) {
					return $bad(
						sprintf(__('maximum length is %d characters', 'custom-twitter-feeds'), self::MAX_STRING_LENGTH)
					);
				}
				return $str;
		}
	}
}
