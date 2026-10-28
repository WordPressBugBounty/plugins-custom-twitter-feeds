<?php

namespace TwitterFeed\Abilities;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Browser-side start of an ability-initiated account connect.
 *
 * Custom Twitter Feeds' own Connect button links DIRECTLY to the OAuth relay
 * (`ctf_oauth_url()`, inc/ctf-functions.php) with a bare GET carrying `state`,
 * the admin's email (`wordpress_user`), a `con_nonce` minted with
 * `wp_create_nonce('ctf_con')`, and `vn`. That nonce is bound to the current
 * session token, so one minted during an Application-Password REST request
 * (no cookie session) never verifies when the relay calls back into
 * `CTF_Feed_Builder::connect_site_token()`. Handing that link straight to an
 * agent would also put the admin's email in the MCP client/LLM context.
 *
 * `get-connect-url` therefore returns a wp-admin bootstrap URL instead (mirrors
 * Instagram Pro's SBI_Connect_Bootstrap). Landing here — capability-checked,
 * write-gate re-checked (TOCTOU-safe: the gate can be turned off between the
 * ability call and the page load), single-use state token consumed — mints
 * the REAL `ctf_oauth_url()` link in the admin's own browser session and
 * redirects to it. The admin's email is minted here, in the browser, and never
 * returned to the ability caller.
 *
 * @since 2.10.1
 */
class CTF_Connect_Bootstrap
{
	/**
	 * Host of the Smash Balloon OAuth relay. Hardcoded — never taken from the
	 * query string — per AgDR-9005 §5 ("the redirect host is hardcoded").
	 */
	const REDIRECT_HOST = 'connect.smashballoon.com';

	public function __construct()
	{
		add_action('admin_init', array($this, 'maybe_redirect'), 1);
	}

	public function maybe_redirect()
	{
		if (
			empty($_GET['page']) || $_GET['page'] !== 'ctf-settings'
			|| empty($_GET['ctf_abilities_connect']) || !is_string($_GET['ctf_abilities_connect'])
		) {
			return;
		}
		if (!ctf_current_user_can('manage_twitter_feed_options')) {
			return;
		}
		// Read the token first so a gate refusal can burn it: a link refused
		// while the gate is off must stay dead if the gate is re-enabled later.
		$state = isset($_GET['ctf_abilities_state']) && is_string($_GET['ctf_abilities_state'])
			? sanitize_key($_GET['ctf_abilities_state']) : '';
		// Re-check the write gate on load, not only when the ability ran: an
		// admin may have turned AI write access off between minting this link
		// and clicking it (TOCTOU). Capability was checked above and tokens are
		// user-bound, so consume() can only burn the caller's own link.
		if (!CTF_Write_Gate::is_enabled()) {
			CTF_State_Token::consume($state, 'connect');
			wp_die(
				esc_html__('AI write access has been disabled since this link was created.', 'custom-twitter-feeds'),
				esc_html__('Twitter Feed', 'custom-twitter-feeds'),
				array('response' => 403, 'back_link' => true)
			);
		}
		if (!CTF_State_Token::consume($state, 'connect')) {
			wp_die(
				esc_html__(
					'This connect link is invalid or has expired. Ask your AI assistant for a new one.',
					'custom-twitter-feeds'
				),
				esc_html__('Twitter Feed', 'custom-twitter-feeds'),
				array('response' => 403, 'back_link' => true)
			);
		}

		add_filter('allowed_redirect_hosts', array($this, 'allow_connect_host'));

		// The REAL connect URL, built the same way the plugin's own Connect
		// button builds it — with `con_nonce` minted HERE, in the browser
		// session, and the admin's email resolved HERE. Neither value is ever
		// handed back to the ability caller.
		$connect_url = ctf_oauth_url(admin_url('admin.php?page=ctf-settings'));

		wp_safe_redirect($connect_url);
		exit;
	}

	/**
	 * @param array $hosts
	 * @return array
	 */
	public function allow_connect_host($hosts)
	{
		$hosts[] = self::REDIRECT_HOST;
		return $hosts;
	}
}
