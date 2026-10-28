<?php

namespace TwitterFeed\Abilities\Services;

use TwitterFeed\Abilities\CTF_State_Token;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Connected-account operations for the Abilities API.
 *
 * Custom Twitter Feeds connects ONE global account (stored in `ctf_options`),
 * unlike Instagram Feed's per-account source list. Always redacted: never
 * returns the relay site token, access tokens or app credentials. Connecting
 * an account remains a HUMAN step — this service only builds the connect
 * link; the OAuth handshake happens in the admin's own browser, bootstrapped
 * by CTF_Connect_Bootstrap.
 *
 * Free has no `ctf_should_switch_to_smash_twitter()` app-credential switch
 * (Pro-only). The connection signal here is the relay site token
 * (`ctf_options[CTF_SITE_ACCESS_TOKEN_KEY]`, i.e. `site_access_token_free`)
 * OR a pair of own app access tokens — see
 * projects/custom-twitter-feeds/abilities-free-plan.md §2 row 7.
 *
 * @since 2.10.1
 */
class CTF_Source_Service
{
	/**
	 * The single connected account, fully redacted.
	 *
	 * @return array{is_connected: bool, using_smash_relay: bool, has_app_credentials: bool, account_handle: string}
	 */
	public function get_account()
	{
		$options = get_option('ctf_options', array());
		$options = is_array($options) ? $options : array();

		$has_site_token      = !empty($options[CTF_SITE_ACCESS_TOKEN_KEY]);
		$has_app_credentials = !empty($options['consumer_key']) && !empty($options['consumer_secret']);
		$has_own_tokens       = !empty($options['access_token']) && !empty($options['access_token_secret']);
		$using_smash_relay   = $has_site_token || (defined('CTF_DOING_SMASH_TWITTER') && CTF_DOING_SMASH_TWITTER);

		return array(
			'is_connected'         => $has_site_token || $has_own_tokens,
			'using_smash_relay'    => (bool) $using_smash_relay,
			'has_app_credentials'  => $has_app_credentials,
			'account_handle'       => isset($options['account_handle']) ? (string) $options['account_handle'] : '',
		);
	}

	/**
	 * Build the account-connect URL for the human to open.
	 *
	 * Points at OUR wp-admin bootstrap page (CTF_Connect_Bootstrap), which —
	 * in the admin's browser session — mints the plugin's REAL connect link
	 * (`ctf_oauth_url()`, with a session-bound `con_nonce` and the admin's own
	 * email) and redirects to it. Neither the nonce nor the email ever
	 * appears in this service's output.
	 *
	 * @return array{connect_url: string, expires_in: int, instructions: string}|\WP_Error
	 */
	public function get_connect_url()
	{
		$token = CTF_State_Token::create('connect');
		if (is_wp_error($token)) {
			return $token;
		}

		return array(
			'connect_url'  => add_query_arg(
				array(
					'page'                  => 'ctf-settings',
					'ctf_abilities_connect' => 1,
					'ctf_abilities_state'   => $token,
				),
				admin_url('admin.php')
			),
			'expires_in'   => CTF_State_Token::TTL,
			'instructions' => __('Open this link in the browser where you are logged into wp-admin. It forwards you to Twitter/X to authorize the app, then returns you to Twitter Feed → Settings with the account connected. The link is single-use and expires in 15 minutes. The AI assistant never sees your Twitter/X login or your WordPress admin email.', 'custom-twitter-feeds'), // phpcs:ignore Generic.Files.LineLength.TooLong -- single literal for make-pot
		);
	}
}
