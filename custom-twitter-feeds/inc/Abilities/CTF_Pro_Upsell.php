<?php

namespace TwitterFeed\Abilities;

use TwitterFeed\Builder\CTF_Feed_Builder;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Pro upsell surfaces for the FREE edition's Abilities API (AgDR-9006).
 *
 * One place that knows three things, so every surface stays consistent and the
 * copy never drifts from the rest of the plugin:
 *   1. the upgrade URL + UTM convention  -> upgrade_url()
 *   2. which Pro features exist, and which feed settings they unlock
 *                                         -> features() / pro_only_settings()
 *   3. the one-line "this is Pro" hint attached to Pro-caused refusals
 *                                         -> hint() / hint_for_feature() / hint_for_setting()
 *
 * Feature names/descriptions are NOT restated here: they are read from the
 * builder's own upsell popup (CTF_Feed_Builder::get_extensions_popup()), so a
 * copy change there flows through automatically. Each promoted popup entry
 * carries an explicit, translatable `featureName` (never parsed out of the
 * heading, which is translated text); an entry without one is left out.
 * Only the map of "feature -> Pro-only setting keys" lives here, and the tests
 * pin it against the FULL free schema (every listed key must be absent from it).
 *
 * UTM convention (one rule, so every port matches):
 *   - utm_source = `abilities` for API responses, `settings` for the admin card;
 *   - utm_medium = the feature id, i.e. the popup/upsell key in kebab-case
 *     (`feedLayout` -> `feed-layout`, see feature_medium()), for any link tied to one
 *     feature;
 *   - utm_medium = `ai-agents-card` for the banner, `get-pro-features` for that
 *     ability's top-level upgrade_url, and `describe-settings-schema` for the
 *     `pro_only.upgrade_url` of that ability.
 *
 * Free edition only. Every public method is inert in a Pro build.
 *
 * PORTING: swap BASE_URL / UTM_CAMPAIGN, the data source in features(), and
 * PRO_SETTINGS_BY_FEATURE. Nothing else is plugin-specific.
 *
 * @since 2.10.1
 */
class CTF_Pro_Upsell
{
	/** The plugin's existing lite-upgrade landing page (same one the Settings and builder CTAs use). */
	const BASE_URL = 'https://smashballoon.com/custom-twitter-feeds/twitter-lite-upgrade/';

	/** Matches the `utm_campaign` every other free-edition upgrade link already carries. */
	const UTM_CAMPAIGN = 'twitter-free';

	/**
	 * Pro-only feed settings, grouped by the builder-popup feature that unlocks them.
	 *
	 * Keys are the CTF_Feed_Builder::get_extensions_popup() array keys; only the
	 * listed ones are promoted. Values are setting keys that exist in Pro and are
	 * deliberately ABSENT from the free schema (AgDR-9005). Features with no
	 * per-feed setting in the Pro schema (media) map to an empty list: never
	 * invent a key for them. Deliberately not listed: the Hashtag / Search / List /
	 * Mentions feed types (the plugin's own `twitterApiLimit` notice says they are
	 * unavailable because of Twitter API changes, so they are not promoted as
	 * something Pro unlocks), `socialwall` and `feedTemplates`.
	 *
	 * @var array<string, string[]>
	 */
	const PRO_SETTINGS_BY_FEATURE = array(
		'feedLayout'          => array(
			'layout', 'masonrycols', 'masonrytabletcols', 'masonrymobilecols', 'carouselrows',
			'carouselcols', 'carouseltabletcols', 'carouselmobilecols', 'carouselloop',
			'carouselarrows', 'carouselpag', 'carouselautoplay', 'carouseltime',
		),
		'advancedFilters'     => array('includeretweets', 'includereplies', 'selfreplies', 'includewords', 'excludewords'),
		'mediaExtension'      => array(),
		'autoscrollExtension' => array('autoscroll'),
		'lightboxExtension'   => array('disablelightbox'),
	);

	/**
	 * Popup keys of the features the AI Agents card banner names. Picked by key
	 * (not array position) so reordering the builder popup cannot change the banner.
	 *
	 * @var string[]
	 */
	const BANNER_FEATURES = array('feedLayout', 'advancedFilters', 'autoscrollExtension');

	/**
	 * Only the free edition promotes Pro. Guarded here so a future shared build
	 * can never surface any of this inside Pro.
	 *
	 * @return bool
	 */
	public static function is_free()
	{
		return !function_exists('ctf_is_pro_version') || !ctf_is_pro_version();
	}

	/**
	 * Upgrade link with the plugin's standard UTM set.
	 *
	 * @param string $medium What was shown (e.g. `get-pro-features`, `describe-settings-schema`, `feed-layout`, `ai-agents-card`).
	 * @param string $source Surface: `abilities` for API responses, `settings` for the admin card.
	 * @return string
	 */
	public static function upgrade_url($medium, $source = 'abilities')
	{
		return self::BASE_URL
			. '?utm_campaign=' . self::UTM_CAMPAIGN
			. '&utm_source=' . rawurlencode((string) $source)
			. '&utm_medium=' . rawurlencode((string) $medium)
			. '&utm_content=UpgradeToPro';
	}

	/**
	 * Promoted features keyed by popup id: `[name, description]` straight from the
	 * builder's popup. Empty outside the free edition.
	 *
	 * @return array<string, array{name: string, description: string}>
	 */
	private static function popup_features()
	{
		if (!self::is_free() || !class_exists(CTF_Feed_Builder::class)) {
			return array();
		}
		$out = array();
		foreach (CTF_Feed_Builder::get_extensions_popup() as $id => $item) {
			$name = isset($item['featureName']) && isset(self::PRO_SETTINGS_BY_FEATURE[$id]) ? trim(wp_strip_all_tags((string) $item['featureName'])) : '';
			if ($name === '') {
				continue;
			}
			$out[$id] = array(
				'name'        => $name,
				'description' => isset($item['description']) ? trim(wp_strip_all_tags((string) $item['description'])) : '',
			);
		}
		return $out;
	}

	/**
	 * Pro features as `[name, description, settings[]]`.
	 *
	 * Name + description come from the builder's upsell popup; `settings` from
	 * PRO_SETTINGS_BY_FEATURE. Empty outside the free edition.
	 *
	 * @return array<int, array{name: string, description: string, settings: string[]}>
	 */
	public static function features()
	{
		$out = array();
		foreach (self::popup_features() as $id => $feature) {
			$out[] = array(
				'name'        => $feature['name'],
				'description' => $feature['description'],
				'settings'    => self::PRO_SETTINGS_BY_FEATURE[$id],
			);
		}
		return $out;
	}

	/**
	 * Every Pro-only setting key, flattened. Drives the `pro_only` block of
	 * describe-settings-schema.
	 *
	 * @return string[]
	 */
	public static function pro_only_settings()
	{
		if (!self::is_free()) {
			return array();
		}
		$all = array();
		foreach (self::PRO_SETTINGS_BY_FEATURE as $settings) {
			$all = array_merge($all, $settings);
		}
		return array_values(array_unique($all));
	}

	/**
	 * utm_medium for a feature: its popup key in kebab-case (`feedLayout` -> `feed-layout`).
	 *
	 * @param string $feature_id Popup/upsell key.
	 * @return string
	 */
	public static function feature_medium($feature_id)
	{
		return strtolower(preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '-', (string) $feature_id));
	}

	/**
	 * Names of the BANNER_FEATURES, in banner order, from the builder's popup copy.
	 * Keys missing from the popup are skipped.
	 *
	 * @return string[]
	 */
	public static function banner_feature_names()
	{
		$features = self::popup_features();
		$names    = array();
		foreach (self::BANNER_FEATURES as $id) {
			if (isset($features[$id])) {
				$names[] = $features[$id]['name'];
			}
		}
		return $names;
	}

	/**
	 * One-line hint for a refusal caused by a Pro-only feature.
	 *
	 * @param string $feature_label Human name of the Pro feature.
	 * @param string $medium        UTM medium: the feature id via feature_medium().
	 * @return string Empty outside the free edition.
	 */
	public static function hint($feature_label, $medium)
	{
		if (!self::is_free()) {
			return '';
		}
		return sprintf(
			/* translators: 1: Pro feature name, 2: upgrade URL */
			__('%1$s is a Pro feature. Upgrade: %2$s', 'custom-twitter-feeds'),
			$feature_label,
			self::upgrade_url($medium)
		);
	}

	/**
	 * Hint for a popup feature id (e.g. `feedLayout` for a masonry/carousel
	 * template the plugin itself refuses), or '' when the id is not promoted.
	 *
	 * @param string $feature_id Popup key.
	 * @return string
	 */
	public static function hint_for_feature($feature_id)
	{
		$features = self::popup_features();
		if (!isset($features[$feature_id])) {
			return '';
		}
		return self::hint($features[$feature_id]['name'], self::feature_medium($feature_id));
	}

	/**
	 * Hint for a refused setting key, or '' when the refusal is not Pro-caused
	 * (a typo'd key gets no upsell).
	 *
	 * @internal Not wired into any ability: core schema validation
	 *           (additionalProperties:false) rejects Pro-only keys with a 400
	 *           before plugin code runs, so a hint there is unreachable. Kept for
	 *           a plugin-side refusal of a setting key, should one appear.
	 *
	 * @param string $key Setting key the caller sent.
	 * @return string
	 */
	public static function hint_for_setting($key)
	{
		foreach (self::PRO_SETTINGS_BY_FEATURE as $id => $settings) {
			if (in_array($key, $settings, true)) {
				return self::hint_for_feature($id);
			}
		}
		return '';
	}
}
