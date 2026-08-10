<?php
/**
 * Customizer Builder
 * Image Chooser Field Control
 *
 * @since 2.0
 */
namespace TwitterFeed\Builder\Controls;

if(!defined('ABSPATH'))	exit;

class SB_Imagechooser_Control extends SB_Controls_Base{

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
		return 'imagechooser';
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
		<div class="sb-control-imagechooser-ctn ctf-fb-fs">
			<div class="ctf-fb-fs">
				<input type="text" class="sb-control-imagechooser-input ctf-fb-fs" :class="checkNotEmpty(<?php echo $controlEditingTypeModel ?>[control.id]) ? 'sb-control-imagechooser-padding' : ''" v-model="<?php echo $controlEditingTypeModel ?>[control.id]" :placeholder="control.placeholder ? control.placeholder : <?php echo $controlEditingTypeModel ?>[control.id]" :aria-label="control.heading || control.label || 'Image URL'" disabled>
				<div class="sb-control-imagechooser-clear ctf-fb-tltp-parent" v-if="checkNotEmpty(<?php echo $controlEditingTypeModel ?>[control.id])">
					<button type="button" class="sb-control-imagechooser-clear-icon" :aria-label="genericText.clear" @click.prevent.default="changeSettingValue(control.id, '')"></button>
					<div class="ctf-fb-tltp-elem" aria-hidden="true"><span>{{genericText.clear.replace(/ /g,"&nbsp;")}}</span></div>
				</div>
			</div>
			<button type="button" class="sb-control-imagechooser-btn" @click.prevent.default="imageChooser( control.id )">
				<div v-html="svgIcons['imageChooser']" aria-hidden="true"></div>
				{{genericText.addImage.replace(/ /g,"&nbsp;")}}
			</button>
		</div>
		<?php
	}

}