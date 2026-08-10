<?php
/**
 * Customizer Builder
 * Toggle Control
 *
 * @since 2.0
 */
namespace TwitterFeed\Builder\Controls;

if(!defined('ABSPATH'))	exit;

class SB_Toggle_Control extends SB_Controls_Base{

	/**
	 * Get control type.
	 *
	 * Getting the Control Type
	 *
	 * @since 2.0
	 * @access public
	 *
	 * @return string
	*/
	public function get_type(){
		return 'toggle';
	}

	/**
	 * Output Control
	 *
	 *
	 * @since 2.0
	 * @access public
	 *
	 * @return HTML
	*/
	public function get_control_output($controlEditingTypeModel){
		?>
		<div class="sb-control-toggle-ctn ctf-fb-fs">
			<div class="sb-control-toggle-elm ctf-fb-fs sb-tr-2" data-active="true">
				<span class="sb-control-toggle-deco sb-tr-1" aria-hidden="true"></span>
				<span class="sb-control-toggle-icon" aria-hidden="true" v-if="control.toggle.icon" v-html="svgIcons[control.toggle.icon]"></span>
				<span class="sb-control-label">{{control.toggle.label}}</span>
			</div>
		</div>
		<?php
	}

}