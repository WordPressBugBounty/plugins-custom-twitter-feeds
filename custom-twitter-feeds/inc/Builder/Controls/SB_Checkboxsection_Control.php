<?php
/**
 * Customizer Builder
 * CheckBox Section Control
 *
 * @since 2.0
 */
namespace TwitterFeed\Builder\Controls;

if(!defined('ABSPATH'))	exit;

class SB_Checkboxsection_Control extends SB_Controls_Base{

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
		return 'checkboxsection';
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
		<div class="sb-control-checkboxsection-header" v-if="control.header">
			<div class="sb-control-checkboxsection-name">
				<span aria-hidden="true" v-html="svgIcons['preview']"></span>
				<strong class="">{{genericText.name}}</strong>
			</div>
			<strong>{{genericText.edit}}</strong>
		</div>
		<div class="sb-control-checkbox-ctn ctf-fb-fs" role="group" :aria-label="control.label">
			<span class="sb-control-checkbox-hover" aria-hidden="true"></span>
			<button type="button" class="sb-control-checkbox" role="checkbox" :aria-checked="checkActiveControl(control.id, control.options.enabled) ? 'true' : 'false'" :aria-label="control.label" :aria-disabled="control.disabledInput != undefined ? 'true' : null" :tabindex="control.disabledInput != undefined ? -1 : null" @click.stop.prevent.default="changeCheckboxSectionValue(control.id, control.value, control.ajaxAction != undefined ? control.ajaxAction : false, control.checkBoxAction != undefined ? control : false, $event)" @keydown.enter.stop.prevent="changeCheckboxSectionValue(control.id, control.value, control.ajaxAction != undefined ? control.ajaxAction : false, control.checkBoxAction != undefined ? control : false, $event)" @keydown.space.stop.prevent="changeCheckboxSectionValue(control.id, control.value, control.ajaxAction != undefined ? control.ajaxAction : false, control.checkBoxAction != undefined ? control : false, $event)" :data-active="checkActiveControl(control.id, control.options.enabled)"></button>
			<button type="button" class="sb-control-checkboxsection-open ctf-fb-fs" :aria-controls="control.section && control.section.id ? control.section.id : null" :aria-expanded="<?php echo $controlEditingTypeModel ?>[control.id] == control.options.enabled ? 'true' : 'false'" :aria-label="control.label" :aria-disabled="control.disabledInput != undefined ? 'true' : null" :tabindex="control.disabledInput != undefined ? -1 : null" @click.prevent.default="control.section.controls.length > 0 ? switchNestedSection(control.section.id, control.section) : false" @keydown.enter.prevent="control.section.controls.length > 0 ? switchNestedSection(control.section.id, control.section) : false" @keydown.space.prevent="control.section.controls.length > 0 ? switchNestedSection(control.section.id, control.section) : false" :data-active="<?php echo $controlEditingTypeModel ?>[control.id] == control.options.enabled">
				<strong class="sb-control-label">{{control.label}}</strong>
				<span v-if="control.section.controls.length > 0" class="sb-control-checkboxsection-btn" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

}