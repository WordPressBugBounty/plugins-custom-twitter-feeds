<?php
/**
 * Customizer Builder
 * CheckBox List Control
 *
 * @since 2.0
 */
namespace TwitterFeed\Builder\Controls;

if(!defined('ABSPATH'))	exit;

class SB_Checkboxlist_Control extends SB_Controls_Base{

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
		return 'checkboxlist';
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
		<div role="group" :aria-label="control.heading || control.label">
			<button type="button" class="sb-control-checkbox-ctn ctf-fb-fs" v-for="option in control.options" role="checkbox" :aria-checked="<?php echo $controlEditingTypeModel ?>[control.id].includes(option.value) ? 'true' : 'false'" :aria-label="option.label" @click.prevent.default="changeCheckboxListValue(control.id, option.value)">
				<span class="sb-control-checkbox" aria-hidden="true" :data-active="<?php echo $controlEditingTypeModel ?>[control.id].includes(option.value)"></span>
				<span class="sb-control-label sb-small-p sb-dark-text" aria-hidden="true" v-html="option.label"></span>
			</button>
		</div>
		<?php
	}

}