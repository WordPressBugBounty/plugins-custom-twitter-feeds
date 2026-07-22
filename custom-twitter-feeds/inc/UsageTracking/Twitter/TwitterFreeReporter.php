<?php
/**
 * Twitter reporter for Smash Usage Tracking — Free plugin variant.
 *
 * Collects environment, global settings, connected accounts, feeds summary,
 * features enabled, performance metrics, and error metrics specific to
 * Custom Twitter Feeds (Free).
 *
 * @package TwitterFeed\UsageTracking\Twitter
 * @since 2.6
 */

namespace TwitterFeed\UsageTracking\Twitter;

use TwitterFeed\UsageTracking\ReporterInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TwitterFreeReporter implements ReporterInterface {

	const SCHEMA_VERSION = '1.0';

	/**
	 * Plugin slug for payload root.
	 *
	 * @return string
	 */
	public function get_plugin_slug() {
		return 'twitter';
	}

	/**
	 * Schema version for the report payload.
	 *
	 * @return string
	 */
	public function get_schema_version() {
		return self::SCHEMA_VERSION;
	}

	/**
	 * Configuration snapshot (environment, settings, sources, feeds, features).
	 *
	 * @return array
	 */
	public function get_configuration_snapshot() {
		$global_settings = $this->get_global_settings();

		// Single DB scan — reused for latest sample, summary, and features map.
		$all_feed_data = $this->get_all_feed_data();

		return array(
			'environment'      => $this->get_environment(),
			'global_settings'  => $global_settings,
			'sources'          => $this->get_sources_summary(),
			'latest_15_feeds'  => $this->get_latest_feeds( $all_feed_data ),
			'feeds'            => $this->get_feeds_summary( $all_feed_data ),
			'features_enabled' => $this->get_features_enabled( $all_feed_data, $global_settings ),
			'version'          => defined( 'CTF_VERSION' ) ? CTF_VERSION : '',
			'license_tier'     => $this->get_license_tier(),
			'license_status'   => $this->get_license_status(),
			'license_expires'  => $this->get_license_expires(),
			'license_item_id'  => $this->get_license_item_id(),
		);
	}

	/**
	 * Dynamic metrics for the given period.
	 *
	 * @param string|int $period_start Start of period (ISO 8601 or timestamp).
	 * @param string|int $period_end   End of period (ISO 8601 or timestamp).
	 * @return array
	 */
	public function get_dynamic_metrics( $period_start, $period_end ) {
		$ts_start = is_numeric( $period_start ) ? (int) $period_start : strtotime( $period_start );
		$ts_end   = is_numeric( $period_end ) ? (int) $period_end : strtotime( $period_end );

		return array(
			'period_start'     => $period_start,
			'period_end'       => $period_end,
			'performance'      => $this->get_performance_metrics(),
			'errors'           => $this->get_error_metrics(),
			'events'           => $this->get_events_for_period( $ts_start, $ts_end ),
			'days_active'      => $this->get_days_active( $period_start, $period_end ),
			'session_duration' => $this->get_session_duration(),
		);
	}

	/**
	 * Environment data (WP, PHP, theme, locale, multisite, install age).
	 *
	 * @return array
	 */
	private function get_environment() {
		$install_ts = null;
		$statuses   = get_option( 'ctf_statuses', array() );
		if ( ! empty( $statuses['first_install'] ) && is_numeric( $statuses['first_install'] ) ) {
			$install_ts = (int) $statuses['first_install'];
		}
		if ( null === $install_ts ) {
			$install_ts = (int) get_option( 'ctf_installed_timestamp', 0 );
		}
		$install_age_days = $install_ts ? max( 0, (int) ((time() - $install_ts) / DAY_IN_SECONDS) ) : 0;

		$theme      = wp_get_theme();
		$theme_name = $theme->exists() ? $theme->get( 'Name' ) : '';

		return array(
			'wp_version'           => get_bloginfo( 'version' ),
			'php_version'          => PHP_VERSION,
			'active_theme'         => $theme_name,
			'locale'               => get_locale(),
			'multisite'            => is_multisite(),
			'site_count'           => is_multisite() ? (int) get_blog_count() : 1,
			'active_plugins_count' => count(
                array_unique(
                    array_merge(
                        (array) get_option( 'active_plugins', array() ),
                        array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) )
                    )
                )
            ),
			'install_age_days'     => $install_age_days,
		);
	}

	/**
	 * Global CTF settings (caching, gdpr, preserve, advanced tab).
	 *
	 * @return array
	 */
	private function get_global_settings() {
		$opts = get_option( 'ctf_options', array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}

		return array(
			'preserve_settings' => ! empty( $opts['preserve_settings'] ),
			'cache_time'        => isset( $opts['cache_time'] ) ? $opts['cache_time'] : '',
			'cache_time_unit'   => isset( $opts['cache_time_unit'] ) ? $opts['cache_time_unit'] : '',
			'caching_type'      => isset( $opts['ctf_caching_type'] ) ? $opts['ctf_caching_type'] : 'page',
			'cron_interval'     => isset( $opts['ctf_cache_cron_interval'] ) ? $opts['ctf_cache_cron_interval'] : '',
			'ajax_theme'        => ! empty( $opts['ajax_theme'] ),
			'autores'           => ! empty( $opts['autores'] ),
			'creditctf'         => ! empty( $opts['creditctf'] ),
			'gdpr'              => isset( $opts['gdpr'] ) ? $opts['gdpr'] : '',
			'persistent_cache'  => ! empty( $opts['persistentcache'] ),
			'rebranding'        => ! empty( $opts['rebranding'] ),
			'sslonly'           => ! empty( $opts['sslonly'] ),
			'disable_intents'   => ! empty( $opts['disableintents'] ),
			'head_enqueue'      => ! empty( $opts['headenqueue'] ),
			'custom_templates'  => ! empty( $opts['customtemplates'] ),
			'request_method'    => isset( $opts['request_method'] ) ? $opts['request_method'] : 'auto',
		);
	}

	/**
	 * Sources summary — connected accounts from ctf_options.
	 * CTF has no separate sources table; OAuth-connected accounts live in ctf_options.
	 *
	 * @return array
	 */
	private function get_sources_summary() {
		$opts     = get_option( 'ctf_options', array() );
		$accounts = isset( $opts['connected_accounts'] ) && is_array( $opts['connected_accounts'] )
			? $opts['connected_accounts']
			: array();

		$connected_count = count( $accounts );

		// Tally account types where available.
		$account_type = array(
			'personal' => 0,
			'business' => 0,
			'other'    => 0,
		);
		foreach ( $accounts as $account ) {
			if ( ! is_array( $account ) ) {
				continue;
			}
			$type = isset( $account['type'] ) ? strtolower( (string) $account['type'] ) : 'other';
			if ( isset( $account_type[ $type ] ) ) {
				++$account_type[ $type ];
			} else {
				++$account_type['other'];
			}
		}

		return array(
			'connected_accounts_count' => $connected_count,
			'account_type'             => $account_type,
		);
	}

	/**
	 * Whitelist of feed setting keys to track.
	 *
	 * @var string[]
	 */
	private static $feed_settings_whitelist = array(
		'type',
		'layout',
		'masonry',
		'carousel',
		'num',
		'loadmore',
		'showheader',
		'showbio',
		'disablelightbox',
		'include_media',
		'include_twittercards',
		'include_retweeter',
		'include_avatar',
		'include_author',
		'include_text',
		'include_date',
		'include_actions',
		'include_twitterlink',
		'include_linkbox',
		'include_replied_to',
		'autoscroll',
		'persistentcache',
		'carouselpag',
		'includeretweets',
		'includereplies',
		'includewords',
		'excludewords',
		'masonrycols',
		'carouselcols',
		'cachetime',
		'cache_time',
		'cache_time_unit',
		'usertimeline_text',
		'hashtag_text',
		'search_text',
		'moderation',
	);

	/**
	 * Load every feed's decoded settings plus feed_name, sorted newest-first.
	 *
	 * @return array[]
	 */
	private function get_all_feed_data(): array {
		global $wpdb;
		$table        = $wpdb->prefix . 'ctf_feeds';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

		if ( ! $table_exists ) {
			return array();
		}

		$rows = $wpdb->get_results(
			"SELECT feed_name, settings FROM {$table} ORDER BY last_modified DESC LIMIT 500", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name derived from $wpdb->prefix, not user input.
			ARRAY_A
		);

		$out = array();
		foreach ( $rows as $row ) {
			$decoded = ! empty( $row['settings'] ) ? json_decode( $row['settings'], true ) : array();
			$out[]   = array(
				'feed_name' => isset( $row['feed_name'] ) ? sanitize_text_field( (string) $row['feed_name'] ) : '',
				'settings'  => is_array( $decoded ) ? $decoded : array(),
			);
		}

		return $out;
	}

	/**
	 * Latest 15 feeds with whitelisted settings.
	 *
	 * @param array[] $all_feed_data From get_all_feed_data().
	 * @return array
	 */
	private function get_latest_feeds( array $all_feed_data ): array {
		$feeds = array();
		foreach ( array_slice( $all_feed_data, 0, 15 ) as $row ) {
			$feed_name = $row['feed_name'];
			if ( strlen( $feed_name ) > 255 ) {
				$feed_name = substr( $feed_name, 0, 255 );
			}
			$feeds[] = array(
				'feed_name' => $feed_name,
				'settings'  => $this->pick_whitelisted_settings( $row['settings'] ),
			);
		}
		return $feeds;
	}

	/**
	 * Aggregate feed type and layout distribution across ALL feeds.
	 *
	 * Twitter feed types: usertimeline, hashtag, search, lists, mentions
	 * Twitter layouts: list, grid, masonry, carousel
	 *
	 * @param array[] $all_feed_data From get_all_feed_data().
	 * @return array
	 */
	private function get_feeds_summary( array $all_feed_data ): array {
		$by_type   = array();
		$by_layout = array();

		foreach ( $all_feed_data as $row ) {
			$s = $row['settings'];

			// Determine feed type
			$type = isset( $s['type'] ) ? (string) $s['type'] : 'usertimeline';

			// Determine layout — CTF uses 'layout', 'masonry', 'carousel' flags
			if ( isset( $s['layout'] ) ) {
				$layout = (string) $s['layout'];
			} elseif ( ! empty( $s['masonry'] ) ) {
				$layout = 'masonry';
			} elseif ( ! empty( $s['carousel'] ) ) {
				$layout = 'carousel';
			} else {
				$layout = 'list';
			}

			$by_type[ $type ]     = ($by_type[ $type ] ?? 0) + 1;
			$by_layout[ $layout ] = ($by_layout[ $layout ] ?? 0) + 1;
		}

		return array(
			'total_count' => count( $all_feed_data ),
			'by_type'     => $by_type,
			'by_layout'   => $by_layout,
		);
	}

	/**
	 * Flat boolean feature map for the Laravel dashboard's feature adoption page.
	 *
	 * @param array[] $all_feed_data   From get_all_feed_data().
	 * @param array   $global_settings From get_global_settings().
	 * @return array<string,bool>
	 */
	private function get_features_enabled( array $all_feed_data, array $global_settings ): array {
		$feed_flags = array(
			'load_more'        => false,
			'show_header'      => false,
			'masonry_layout'   => false,
			'carousel'         => false,
			'lightbox'         => false,
			'hashtag_feeds'    => false,
			'search_feeds'     => false,
			'lists_feeds'      => false,
			'user_timeline'    => false,
			'mentions_feeds'   => false,
			'autoscroll'       => false,
			'moderation'       => false,
			'link_cards'       => false,
			'include_media'    => false,
			'include_retweets' => false,
			'show_bio'         => false,
		);

		foreach ( $all_feed_data as $row ) {
			$s = $row['settings'];

			if ( ! $feed_flags['load_more'] && ! empty( $s['loadmore'] ) ) {
$feed_flags['load_more'] = true;
            }
			if ( ! $feed_flags['show_header'] && ! empty( $s['showheader'] ) ) {
$feed_flags['show_header'] = true;
            }
			if ( ! $feed_flags['masonry_layout'] && ( ! empty( $s['masonry'] ) || (isset( $s['layout'] ) && 'masonry' === $s['layout'])) ) {
$feed_flags['masonry_layout'] = true;
            }
			if ( ! $feed_flags['carousel'] && ( ! empty( $s['carousel'] ) || (isset( $s['layout'] ) && 'carousel' === $s['layout'])) ) {
$feed_flags['carousel'] = true;
            }
			if ( ! $feed_flags['lightbox'] && empty( $s['disablelightbox'] ) ) {
$feed_flags['lightbox'] = true;
            }
			if ( ! $feed_flags['hashtag_feeds'] && isset( $s['type'] ) && 'hashtag' === $s['type'] ) {
$feed_flags['hashtag_feeds'] = true;
            }
			if ( ! $feed_flags['search_feeds'] && isset( $s['type'] ) && 'search' === $s['type'] ) {
$feed_flags['search_feeds'] = true;
            }
			if ( ! $feed_flags['lists_feeds'] && isset( $s['type'] ) && 'lists' === $s['type'] ) {
$feed_flags['lists_feeds'] = true;
            }
			if ( ! $feed_flags['user_timeline'] && isset( $s['type'] ) && 'usertimeline' === $s['type'] ) {
$feed_flags['user_timeline'] = true;
            }
			if ( ! $feed_flags['mentions_feeds'] && isset( $s['type'] ) && 'mentions' === $s['type'] ) {
$feed_flags['mentions_feeds'] = true;
            }
			if ( ! $feed_flags['autoscroll'] && ! empty( $s['autoscroll'] ) ) {
$feed_flags['autoscroll'] = true;
            }
			if ( ! $feed_flags['moderation'] && ! empty( $s['moderation'] ) ) {
$feed_flags['moderation'] = true;
            }
			if ( ! $feed_flags['link_cards'] && ! empty( $s['include_twittercards'] ) ) {
$feed_flags['link_cards'] = true;
            }
			if ( ! $feed_flags['include_media'] && ! empty( $s['include_media'] ) ) {
$feed_flags['include_media'] = true;
            }
			if ( ! $feed_flags['include_retweets'] && ! empty( $s['includeretweets'] ) ) {
$feed_flags['include_retweets'] = true;
            }
			if ( ! $feed_flags['show_bio'] && ! empty( $s['showbio'] ) ) {
$feed_flags['show_bio'] = true;
            }

			if ( ! in_array( false, $feed_flags, true ) ) {
				break;
			}
		}

		return array_merge(
            $feed_flags,
            array(
				'ajax_theme_fix'    => (bool) ($global_settings['ajax_theme'] ?? false),
				'persistent_cache'  => (bool) ($global_settings['persistent_cache'] ?? false),
				'gdpr_enabled'      => isset( $global_settings['gdpr'] ) && '' !== $global_settings['gdpr'],
				'rebranding'        => (bool) ($global_settings['rebranding'] ?? false),
				'credit_link'       => (bool) ($global_settings['creditctf'] ?? false),
				'preserve_settings' => (bool) ($global_settings['preserve_settings'] ?? false),
				'auto_resize'       => (bool) ($global_settings['autores'] ?? false),
            )
        );
	}

	/**
	 * Return only whitelisted feed settings.
	 *
	 * @param array $settings Raw feed settings.
	 * @return array
	 */
	private function pick_whitelisted_settings( array $settings ) {
		$out = array();
		foreach ( self::$feed_settings_whitelist as $key ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}
			$value = $settings[ $key ];
			if ( is_array( $value ) ) {
				$out[ $key ] = $value;
			} elseif ( is_scalar( $value ) ) {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	// ── License methods ───────────────────────────────────────────────────────
	// The Free plugin has no EDD license infrastructure. These return static
	// values so the payload is always consistent and the dashboard can correctly
	// segment free vs paid sites.

	/** @return string */
	private function get_license_tier() {
return 'free'; }

	/** @return null */
	private function get_license_status() {
return null; }

	/** @return null */
	private function get_license_expires() {
return null; }

	/** @return null */
	private function get_license_item_id() {
return null; }

	// ── Metrics methods ───────────────────────────────────────────────────────

	/**
	 * Performance metrics (feed caches count, cached posts count).
	 *
	 * @return array
	 */
	private function get_performance_metrics() {
		global $wpdb;

		$feed_caches_count = 0;
		$cache_table       = $wpdb->prefix . 'ctf_feed_caches';
		$cache_exists      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $cache_table ) ) === $cache_table;
		if ( $cache_exists ) {
			$feed_caches_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cache_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name derived from $wpdb->prefix, not user input.
		}

		$cached_posts_count = 0;
		$posts_table        = $wpdb->prefix . 'ctf_posts';
		$posts_exists       = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $posts_table ) ) === $posts_table;
		if ( $posts_exists ) {
			$cached_posts_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$posts_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name derived from $wpdb->prefix, not user input.
		}

		$cache_requests_count = (int) get_option( 'ctf_smash_cache_requests_count', 0 );

		return array(
			'feed_caches_count'    => $feed_caches_count,
			'cached_posts_count'   => $cached_posts_count,
			'cache_requests_count' => $cache_requests_count,
		);
	}

	/**
	 * Map a Twitter/X API HTTP status code or legacy Twitter API error code to a category.
	 *
	 * HTTP codes: 401 (auth), 403 (permission), 404 (not_found), 429 (rate_limit), 5xx (server)
	 * Legacy Twitter v1.1 codes:
	 *   auth:       32, 64, 135, 215, 326
	 *   rate_limit: 88, 185
	 *   permission: 93, 130, 131
	 *   not_found:  34, 144
	 *   server:     503
	 *
	 * @param int|string $code
	 * @return string
	 */
	private function categorise_error_code( $code ): string {
		$code = (int) $code;

		// HTTP auth errors
		if ( 401 === $code ) {
return 'auth';
        }
		// HTTP permission errors
		if ( 403 === $code ) {
return 'permission';
        }
		// HTTP not found
		if ( 404 === $code ) {
return 'not_found';
        }
		// HTTP rate limiting
		if ( 429 === $code ) {
return 'rate_limit';
        }
		// HTTP server errors
		if ( $code >= 500 && $code < 600 ) {
return 'server';
        }

		// Legacy Twitter v1.1 API error codes
		if ( in_array( $code, array( 32, 64, 135, 215, 326 ), true ) ) {
return 'auth';
        }
		if ( in_array( $code, array( 88, 185 ), true ) ) {
return 'rate_limit';
        }
		if ( in_array( $code, array( 93, 130, 131 ), true ) ) {
return 'permission';
        }
		if ( in_array( $code, array( 34, 144 ), true ) ) {
return 'not_found';
        }
		if ( 503 === $code ) {
return 'server';
        }

		return 'other';
	}

	/**
	 * Error metrics: categorised counts and latest 10 errors.
	 *
	 * CTF stores errors in two places:
	 * - Legacy: ctf_errors option (array of plain strings)
	 * - SmashTwitter: ctf_statuses['smash_twitter']['error_log'] (array of strings with timestamps)
	 *
	 * @return array
	 */
	private function get_error_metrics() {
		$latest = $this->build_latest_errors_array();

		$by_type        = array(
			'auth'       => 0,
			'rate_limit' => 0,
			'permission' => 0,
			'not_found'  => 0,
			'server'     => 0,
			'network'    => 0,
			'other'      => 0,
		);
		$critical_count = 0;
		foreach ( $latest as $err ) {
			$cat = isset( $err['category'] ) ? $err['category'] : 'other';
			if ( array_key_exists( $cat, $by_type ) ) {
++$by_type[ $cat ];
			} else {
++$by_type['other'];
            }
			if ( ! empty( $err['critical'] ) ) {
++$critical_count;
            }
		}

		$api_failures = count( $latest );

		return array(
			'api_failures'   => $api_failures,
			'by_type'        => $by_type,
			'critical_count' => $critical_count,
			'latest'         => array_slice( $latest, 0, 10 ),
		);
	}

	/**
	 * Build annotated error list from both legacy ctf_errors and SmashTwitter error_log.
	 *
	 * @return array
	 */
	private function build_latest_errors_array() {
		$latest = array();

		// SmashTwitter error_log (newer, timestamped strings)
		$statuses  = get_option( 'ctf_statuses', array() );
		$smash_log = isset( $statuses['smash_twitter']['error_log'] ) && is_array( $statuses['smash_twitter']['error_log'] )
			? $statuses['smash_twitter']['error_log']
			: array();

		foreach ( array_slice( $smash_log, -10 ) as $log_entry ) {
			$str       = is_string( $log_entry ) ? $log_entry : '';
			$prefix    = '';
			$logged_at = '';

			if ( preg_match( '/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+-\s+/', $str, $m ) ) {
				$logged_at = $m[1];
				$prefix    = $m[0];
			}

			$message  = trim( substr( $str, strlen( $prefix ) ) );
			$message  = $this->sanitize_error_message( $message, 300 );
			$code     = null;
			$category = 'other';

			// Extract HTTP codes (3 digits) or message-based codes
			if ( preg_match( '/(?:error|code)[:\s]+(\d{3,})/i', $message, $cm ) ) {
				$code     = (int) $cm[1];
				$category = $this->categorise_error_code( $code );
			} elseif ( stripos( $message, 'too_many_requests' ) !== false || stripos( $message, 'rate limit' ) !== false ) {
				$category = 'rate_limit';
			} elseif ( stripos( $message, 'could_not_authenticate' ) !== false || stripos( $message, 'unauthorized' ) !== false ) {
				$category = 'auth';
			} elseif ( stripos( $message, 'HTTP request error' ) !== false ) {
				$category = 'network';
			}

			$entry = array(
				'type'      => 'smash_log',
				'category'  => $category,
				'logged_at' => $logged_at,
				'message'   => $message,
				'critical'  => in_array( $category, array( 'auth', 'permission' ), true ),
			);
			if ( null !== $code ) {
$entry['error_code'] = $code;
            }
			$latest[] = $entry;
		}

		// Legacy ctf_errors option (array of plain error strings)
		$legacy_errors = get_option( 'ctf_errors', array() );
		if ( is_array( $legacy_errors ) ) {
			foreach ( array_slice( $legacy_errors, -5 ) as $error_str ) {
				$message  = is_string( $error_str ) ? $error_str : '';
				$message  = $this->sanitize_error_message( $message, 300 );
				$code     = null;
				$category = 'other';

				if ( preg_match( '/(?:error|code)[:\s]+(\d{3,})/i', $message, $cm ) ) {
					$code     = (int) $cm[1];
					$category = $this->categorise_error_code( $code );
				}

				$entry = array(
					'type'     => 'legacy_log',
					'category' => $category,
					'message'  => $message,
					'critical' => in_array( $category, array( 'auth', 'permission' ), true ),
				);
				if ( null !== $code ) {
$entry['error_code'] = $code;
                }
				$latest[] = $entry;
			}
		}

		return $latest;
	}

	/**
	 * Strip tokens and truncate error message.
	 *
	 * @param string $message
	 * @param int    $max_len
	 * @return string
	 */
	private function sanitize_error_message( $message, $max_len = 300 ) {
		// Redact known credential key=value patterns
		$message = preg_replace(
			'/\b(access_token|accesstoken|api_key|api_secret|client_id|client_secret|consumer_key|consumer_secret|secret_key|auth_token|refresh_token|private_key|token)\s*[=:]\s*["\']?[^\s&"\'\\\\,\]}\)]{4,}["\']?/i',
			'$1=[REDACTED]',
			$message
		);
		// Redact Bearer tokens
		$message = preg_replace( '/\bBearer\s+[A-Za-z0-9\-._~+\/]+=*/i', 'Bearer [REDACTED]', $message );
		if ( strlen( $message ) > $max_len ) {
			$message = substr( $message, 0, $max_len ) . '...';
		}
		return $message;
	}

	/**
	 * Days active in the given period.
	 *
	 * @param string $period_start
	 * @param string $period_end
	 * @return int|false
	 */
	private function get_days_active( $period_start, $period_end ) {
		$dates = get_option( \TwitterFeed\UsageTracking\Config::OPTION_ACTIVE_DATES, array() );
		if ( ! is_array( $dates ) || empty( $dates ) ) {
			return 0;
		}
		$count = 0;
		$start = strtotime( $period_start );
		$end   = strtotime( $period_end );
		foreach ( $dates as $d ) {
			$ts = strtotime( $d );
			if ( false !== $ts && $ts >= $start && $ts <= $end ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Average of last recorded session durations in seconds.
	 *
	 * @return int|false
	 */
	private function get_session_duration() {
		$durations = get_option( \TwitterFeed\UsageTracking\Config::OPTION_SESSION_DURATIONS, array() );
		if ( ! is_array( $durations ) || empty( $durations ) ) {
			return 0;
		}
		return (int) round( array_sum( $durations ) / count( $durations ) );
	}

	/**
	 * Event counts and last_date for each event.
	 *
	 * @param int $ts_start
	 * @param int $ts_end
	 * @return array
	 */
	private function get_events_for_period( $ts_start, $ts_end ) {
		$events = get_option( 'ctf_smash_usage_events', array() );
		if ( ! is_array( $events ) ) {
			return array();
		}

		$first          = reset( $events );
		$is_legacy_list = is_array( $first ) && ! isset( $first['count'] ) && (isset( $first['event'] ) || isset( $first['timestamp'] ) || isset( $first['time'] ));

		if ( $is_legacy_list ) {
			$out = array();
			foreach ( $events as $entry ) {
				if ( ! is_array( $entry ) ) {
continue;
                }
				$ts = isset( $entry['timestamp'] ) ? (int) $entry['timestamp'] : (isset( $entry['time'] ) ? (int) $entry['time'] : 0);
				if ( $ts < $ts_start || $ts > $ts_end ) {
continue;
                }
				$name = isset( $entry['event'] ) ? $entry['event'] : (isset( $entry['name'] ) ? $entry['name'] : '');
				if ( '' !== $name ) {
					if ( ! isset( $out[ $name ] ) ) {
$out[ $name ] = array(
	'count'     => 0,
	'last_date' => null,
);
                    }
					++$out[ $name ]['count'];
					$date = $ts ? gmdate( 'Y-m-d', $ts ) : null;
					if ( $date && (null === $out[ $name ]['last_date'] || $date > $out[ $name ]['last_date']) ) {
$out[ $name ]['last_date'] = $date;
                    }
				}
			}
			return $out;
		}

		$out = array();
		foreach ( $events as $name => $value ) {
			if ( ! is_string( $name ) || '' === $name ) {
continue;
            }
			if ( is_array( $value ) && isset( $value['count'] ) ) {
				$out[ $name ] = array(
					'count'     => (int) $value['count'],
					'last_date' => isset( $value['last_date'] ) && is_string( $value['last_date'] ) ? $value['last_date'] : null,
				);
				continue;
			}
			if ( is_numeric( $value ) ) {
				$out[ $name ] = array(
					'count'     => (int) $value,
					'last_date' => null,
				);
			}
		}

		return $out;
	}
}
