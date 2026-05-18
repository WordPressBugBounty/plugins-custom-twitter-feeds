<?php

namespace TwitterFeed\Admin\Blocks;

use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\Blocks\SB_Feed_Block;
use TwitterFeed\Builder\CTF_Db;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps -- Follows codebase CTF_ prefix convention.
class CTF_Modern_Feed_Block extends SB_Feed_Block
{
	/**
	 * Get the block name.
	 *
	 * @return string
	 */
	protected function get_block_name() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'smashballoon/twitter-feed';
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
	 * Get the script handle for the block.
	 *
	 * @return string
	 */
	protected function get_script_handle() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'sb-feed-blocks';
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
	 * Get the plugin directory path.
	 *
	 * @return string
	 */
	protected function get_plugin_dir() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return trailingslashit(CTF_PLUGIN_DIR);
	}

	/**
	 * Get the action hook name for enqueuing scripts.
	 *
	 * @return string
	 */
	protected function get_enqueue_scripts_action() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'ctf_enqueue_scripts';
	}

	/**
	 * Get the JavaScript variable name for localized block data.
	 *
	 * @return string
	 */
	protected function get_localize_var_name() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'ctfTwitterFeedBlock';
	}

	/**
	 * Get the feed block identifier.
	 *
	 * @return string
	 */
	protected function get_feed_block_id() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'twitter';
	}

	/**
	 * Get the JavaScript initialization function name.
	 *
	 * @return string
	 */
	protected function get_init_function() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return 'ctf_init';
	}

	/**
	 * Get the block directory path.
	 *
	 * @return string
	 */
	protected function get_block_dir() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		return $this->get_plugin_dir() . 'vendor/smashballoon/framework/Packages/Blocks/dist/feed-blocks/twitter';
	}

	/**
	 * Get the localized data for the block editor.
	 *
	 * @return array
	 */
	protected function get_editor_localize_data() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		$feeds = CTF_Db::feeds_query();

		return array(
			'feeds'    => ! empty($feeds) ? $feeds : array(),
			'feed_url' => admin_url( 'admin.php?page=ctf-feed-builder' ),
			'nonce'    => wp_create_nonce('ctf-blocks'),
		);
	}

	/**
	 * Register hooks for the block.
	 *
	 * @return void
	 */
	public function register_hooks() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		add_action('ctf_enqueue_scripts', 'ctf_scripts_and_styles');
		parent::register_hooks();

		// Parent hooks register_block to init@10, but we're called during init@10
		// so that hook may be skipped. Call directly.
		if (doing_action('init') || did_action('init')) {
			$this->register_block();
		}

		remove_action('enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ));
		add_action('enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ), 25);
	}
}
