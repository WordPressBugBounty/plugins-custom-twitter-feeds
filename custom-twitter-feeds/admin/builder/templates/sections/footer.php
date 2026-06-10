<?php
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/connect-account-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/extensions-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/feedtypes-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/feedtypes-customizer-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/confirm-dialog-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/embed-popup.php';
    include_once CTF_BUILDER_DIR . 'templates/sections/popup/onboarding-popup.php';
    include_once CTF_BUILDER_DIR . 'templates/sections/popup/onboarding-customizer-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/install-plugin-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/feedtemplates-popup.php';
?>
<div class="sb-notification-ctn" :data-active="notificationElement.shown" :data-type="notificationElement.type">
	<div class="sb-notification-icon" v-html="svgIcons[notificationElement.type+'Notification']"></div>
	<span class="sb-notification-text" v-html="notificationElement.text"></span>
</div>

<div class="sb-full-screen-loader" :data-show="fullScreenLoader ? 'shown' :  'hidden'">
	<div class="sb-full-screen-loader-logo">
		<div class="sb-full-screen-loader-spinner"></div>
		<div class="sb-full-screen-loader-img" v-html="svgIcons['smash']"></div>
	</div>
	<div class="sb-full-screen-loader-txt">
		Loading...
	</div>
</div>



<sb-confirm-dialog-component
:dialog-box.sync="dialogBox"
:source-to-delete="sourceToDelete"
:svg-icons="svgIcons"
:parent-type="'builder'"
:generic-text="genericText"
></sb-confirm-dialog-component>

<!--
<sb-add-source-component
:sources-list="sourcesList"
:select-source-screen="selectSourceScreen"
:views-active="viewsActive"
:generic-text="genericText"
:selected-feed="selectedFeed"
:svg-icons="svgIcons"
:links="links"
ref="addSourceRef"
>
</sb-add-source-component>
-->
<install-plugin-popup
:views-active="viewsActive"
:generic-text="genericText"
:svg-icons="svgIcons"
:plugins="plugins[viewsActive.installPluginModal]"
>
</install-plugin-popup>
