<?php
/**
 * Usage Tracking Reporter Interface
 *
 * Implemented by plugin-specific reporters for the Smash Usage Tracking module.
 *
 * @package TwitterFeed\UsageTracking
 * @since 2.6
 */

namespace TwitterFeed\UsageTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ReporterInterface {

	/**
	 * Plugin identifier for payload root (e.g. 'twitter').
	 *
	 * @return string
	 */
	public function get_plugin_slug();

	/**
	 * Schema version for the report payload.
	 *
	 * @return string
	 */
	public function get_schema_version();

	/**
	 * Configuration snapshot (environment, settings, sources, feeds).
	 *
	 * @return array
	 */
	public function get_configuration_snapshot();

	/**
	 * Dynamic metrics for a time period (performance, errors, events).
	 *
	 * @param string $period_start Start of period (e.g. ISO 8601 or timestamp).
	 * @param string $period_end   End of period (e.g. ISO 8601 or timestamp).
	 * @return array
	 */
	public function get_dynamic_metrics( $period_start, $period_end );
}
