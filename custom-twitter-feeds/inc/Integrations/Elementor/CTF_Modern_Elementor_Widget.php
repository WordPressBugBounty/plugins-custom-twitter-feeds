<?php

namespace TwitterFeed\Integrations\Elementor;

use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\Blocks\SB_Elementor_Feed_Widget;
use TwitterFeed\Builder\CTF_Db;

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('\Elementor\Widget_Base')) {
	return;
}

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps -- Follows codebase CTF_ prefix convention.
class CTF_Modern_Elementor_Widget extends SB_Elementor_Feed_Widget
{
	/**
	 * Get the widget name.
	 *
	 * @return string
	 */
	protected function get_widget_name() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'sb-twitter-feed';
	}

	/**
	 * Get the widget title.
	 *
	 * @return string
	 */
	protected function get_widget_title() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return __( 'Twitter Feed', 'custom-twitter-feeds' );
	}

	/**
	 * Get the widget icon CSS class.
	 *
	 * @return string
	 */
	protected function get_widget_icon() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'sb-elem-icon sb-elem-twitter';
	}

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 */
	protected function get_shortcode_tag() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'custom-twitter-feeds';
	}

	/**
	 * Get the available feeds options for the widget.
	 *
	 * @return array
	 */
	protected function get_feeds_options() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return CTF_Db::elementor_feeds_query();
	}

	/**
	 * Get the text domain.
	 *
	 * @return string
	 */
	protected function get_text_domain() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'custom-twitter-feeds';
	}

	/**
	 * Get the script dependencies.
	 *
	 * @return array
	 */
	protected function get_script_deps() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return array( 'ctf_scripts', 'sb-elementor-editor' );
	}

	/**
	 * Get the style dependencies.
	 *
	 * @return array
	 */
	protected function get_style_deps() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return array( 'ctf_styles', 'sb-elementor-editor' );
	}

	/**
	 * Get the output filter name.
	 *
	 * @return string
	 */
	protected function get_output_filter() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'ctf_output';
	}
}
