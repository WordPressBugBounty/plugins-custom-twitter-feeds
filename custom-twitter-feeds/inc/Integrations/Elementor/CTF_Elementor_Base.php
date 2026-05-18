<?php

namespace TwitterFeed\Integrations\Elementor;

use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\Blocks\RecommendedElementorWidgets;
use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\Blocks\SB_Feed_Blocks_Registry;
use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\Blocks\SB_Block_Utils;
use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\Blocks\SB_Elementor_Editor_Assets;
use TwitterFeed\Builder\CTF_Db;

if (! defined('ABSPATH')) {
	exit;
}

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps -- Follows codebase CTF_ prefix convention.
class CTF_Elementor_Base
{
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Register and return the singleton instance.
	 *
	 * @return self
	 */
	public static function register()
	{
		if (null === self::$instance) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance()
	{
		return self::register();
	}

	/**
	 * Initialize the Elementor integration hooks.
	 *
	 * @return void
	 */
	private function init()
	{
		if (doing_action('init') || did_action('init')) {
			$this->init_elementor_integration();
		} else {
			add_action('init', array( $this, 'init_elementor_integration' ), 4);
		}
	}

	/**
	 * Set up Elementor widget registration, scripts, and categories.
	 *
	 * @return void
	 */
	public function init_elementor_integration() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		if (! did_action('elementor/loaded')) {
			return;
		}

		$recommended = new RecommendedElementorWidgets('twitter');
		$recommended->setup();

		$registry = SB_Feed_Blocks_Registry::instance();
		$registry->register_elementor_widget(array(
			'blockId'    => 'twitter',
			'widgetName' => 'sb-twitter-feed',
			'globalVar'  => 'ctfElementorData',
			'feedInitFn' => 'ctf_init',
		));

		add_action('elementor/widgets/register', array( $this, 'register_widgets' ));
		add_action('elementor/frontend/after_register_scripts', array( $this, 'register_frontend_scripts' ));
		add_action('elementor/elements/categories_registered', array( $this, 'add_smashballoon_categories' ));
		add_action('elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ));
	}

	/**
	 * Register the Elementor widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 *
	 * @return void
	 */
	public function register_widgets($widgets_manager) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		$widgets_manager->register(new CTF_Modern_Elementor_Widget());
	}

	/**
	 * Register and localize frontend scripts for Elementor.
	 *
	 * @return void
	 */
	public function register_frontend_scripts() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		ctf_scripts_and_styles(true);

		$feeds = CTF_Db::elementor_feeds_list();

		$data = array(
			'feeds'         => ! empty($feeds) ? $feeds : array(),
			'feed_url'      => admin_url('admin.php?page=ctf-feed-builder'),
			'is_pro_active' => ctf_is_pro_version(),
		);

		wp_localize_script('ctf_scripts', 'ctfElementorData', $data);

		SB_Feed_Blocks_Registry::instance()->enqueue_elementor_assets();
	}

	/**
	 * Add the Smash Balloon widget category to Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 *
	 * @return void
	 */
	public function add_smashballoon_categories($elements_manager) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		$elements_manager->add_category(
			SB_Block_Utils::CATEGORY_SLUG,
			array(
				'title' => esc_html__('Smash Balloon', 'custom-twitter-feeds'),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Enqueue shared Elementor editor styles.
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	{
		SB_Elementor_Editor_Assets::enqueue_shared_elementor_styles(CTF_VERSION);
	}
}
