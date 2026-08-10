<?php
/**
 * Customizer Builder
 * CheckBox Control
 *
 * @since 2.0
 */
namespace TwitterFeed\Builder\Controls;

if(!defined('ABSPATH'))	exit;

class SB_Checkbox_Control extends SB_Controls_Base{

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
		return 'checkbox';
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
		<button type="button" class="sb-control-checkbox-ctn ctf-fb-fs" role="checkbox" :aria-checked="((control.custom != undefined && control.custom == 'feedtype') ? <?php echo $controlEditingTypeModel ?>['type'].includes(control.value) : checkActiveControl(control.id, control.options.enabled)) ? 'true' : 'false'" :aria-label="control.label" :aria-disabled="control.disabledInput != undefined ? 'true' : null" :tabindex="control.disabledInput != undefined ? -1 : null" @click.prevent.default="(control.custom != undefined && control.custom == 'feedtype') ?  changeCheckboxSectionValue('type', control.value, 'feedFlyPreview', false, $event) : changeSwitcherSettingValue(control.id, control.options.enabled, control.options.disabled, control.ajaxAction != undefined ? control.ajaxAction : false)" @keydown.enter.prevent="(control.custom != undefined && control.custom == 'feedtype') ?  changeCheckboxSectionValue('type', control.value, 'feedFlyPreview', false, $event) : changeSwitcherSettingValue(control.id, control.options.enabled, control.options.disabled, control.ajaxAction != undefined ? control.ajaxAction : false)" @keydown.space.prevent="(control.custom != undefined && control.custom == 'feedtype') ?  changeCheckboxSectionValue('type', control.value, 'feedFlyPreview', false, $event) : changeSwitcherSettingValue(control.id, control.options.enabled, control.options.disabled, control.ajaxAction != undefined ? control.ajaxAction : false)">
			<span class="sb-control-checkbox" aria-hidden="true" :data-active="(control.custom != undefined && control.custom == 'feedtype') ? <?php echo $controlEditingTypeModel ?>['type'].includes(control.value) : checkActiveControl(control.id, control.options.enabled)"></span>
			<span class="sb-control-label" :data-title="control.labelStrong ? 'true' : false">{{control.label}}</span>
		</button>
		<?php
	}

}