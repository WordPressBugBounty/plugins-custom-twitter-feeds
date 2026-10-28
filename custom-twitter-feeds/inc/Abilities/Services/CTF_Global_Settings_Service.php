<?php

namespace TwitterFeed\Abilities\Services;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Reads/writes plugin-wide settings for the Abilities API, restricted to the
 * CTF_Settings_Schema::global_settings() allowlist.
 *
 * `ctf_options` is a single option that ALSO holds account credentials
 * (consumer_key, access_token, site_access_token_free, ...); this service
 * only ever touches the allowlisted keys, never the whole array, so
 * credentials can never leak through a read or be clobbered by a write.
 *
 * @since 2.10.1
 */
class CTF_Global_Settings_Service
{
	/**
	 * Defaults for the allowlisted keys — read from
	 * TwitterFeed\Admin\CTF_Global_Settings::default_settings_options()
	 * (AgDR-9004 requirement 1: derive, don't restate; free has no
	 * Pro-style `global_abilities_defaults()` static accessor, so this calls
	 * the plugin's own public instance method instead of restating the
	 * literals here). default_settings_options() is a pure array builder, and
	 * CTF_Global_Settings::init() (called from its constructor) itself
	 * early-returns on `!is_admin()`, which is true for the REST requests an
	 * ability normally runs under — so instantiating it here does not
	 * register admin-only hooks a second time on the request path that
	 * matters.
	 *
	 * @return array<string, mixed>
	 */
	private function defaults()
	{
		return (new \TwitterFeed\Admin\CTF_Global_Settings())->default_settings_options();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get()
	{
		$all      = get_option('ctf_options', array());
		$all      = is_array($all) ? $all : array();
		$defaults = $this->defaults();
		$schema   = CTF_Settings_Schema::global_settings();
		$out      = array();
		$warnings = array();
		foreach ($schema as $key => $def) {
			$out[$key] = CTF_Settings_Schema::cast_out(
				$def,
				isset($all[$key]) ? $all[$key] : null,
				isset($defaults[$key]) ? $defaults[$key] : null
			);

			// AgDR-9004/9005 downgrade rule: a key with a wider `read_enum`
			// reports the stored value as-is even when it falls outside the
			// narrower write set (a legacy value, or one left by a Pro
			// downgrade). Flag it so an agent doesn't read e.g. "30mins" and
			// assume it can write that value straight back.
			// A read-only key (the cron schedule) has no write path to warn about.
			$is_writable = !isset($def['writable']) || $def['writable'] !== false;
			if ($is_writable && isset($def['read_enum']) && !in_array($out[$key], $def['enum'], true)) {
				$warnings[] = sprintf(
					/* translators: 1: setting key, 2: stored value */
					__(
						'%1$s is currently "%2$s", which is not one of the values this setting can be WRITTEN to. It is reported as-is; update it via the Settings page or update-global-settings with one of its offered values.', // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
						'custom-twitter-feeds'
					),
					$key,
					$out[$key]
				);
			}
		}
		$out['warnings'] = $warnings;
		return $out;
	}

	/**
	 * Apply a partial update to the plugin-wide settings.
	 *
	 * Mirrors what CTF_Global_Settings::ctf_save_settings() persists for the
	 * same keys (the allowlist keys ARE the ctf_options keys), then clears the
	 * persistent cache the way every settings save does. The cron-schedule keys
	 * are read-only here (the free plugin sells faster refreshes as Pro), so this
	 * never reschedules the background cron.
	 *
	 * @param array $partial key => value, already schema-validated by core.
	 * @return array{updated: string[], ignored: object, warnings: string[]}|\WP_Error
	 */
	public function update(array $partial)
	{
		$schema   = CTF_Settings_Schema::global_settings();
		$writable = CTF_Settings_Schema::writable($schema);
		$updated  = array();
		$ignored  = array();
		$clean    = array();

		foreach ($partial as $key => $value) {
			if (!isset($schema[$key])) {
				$ignored[$key] = 'unknown_setting';
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
			$clean[$key] = $v;
		}

		if (empty($clean)) {
			return array('updated' => array(), 'ignored' => (object) $ignored, 'warnings' => array());
		}

		$settings = get_option('ctf_options', array());
		$settings = is_array($settings) ? $settings : array();
		foreach ($clean as $key => $v) {
			$settings[$key] = $v;
			$updated[]      = $key;
		}
		update_option('ctf_options', $settings);

		if (class_exists('TwitterFeed\Admin\CTF_Cache_Handler')) {
			(new \TwitterFeed\Admin\CTF_Cache_Handler())->clear_persistent_cache();
		}

		/**
		 * Fires after an AI agent updated global settings through the Abilities API.
		 *
		 * @param string[] $updated Keys that were written.
		 */
		do_action('ctf_abilities_global_settings_updated', $updated);

		$warnings = array();
		if (in_array('gdpr', $updated, true) && $clean['gdpr'] === 'no') {
			$warnings[] = 'GDPR mode is now "no": feeds load X/Twitter media and scripts before '
				. 'visitors consent. Tell the site owner, since this can affect legal compliance.';
		}

		return array('updated' => $updated, 'ignored' => (object) $ignored, 'warnings' => $warnings);
	}
}
