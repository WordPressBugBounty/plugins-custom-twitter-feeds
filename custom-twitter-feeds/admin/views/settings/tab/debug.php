<?php // phpcs:ignoreFile -- Vue template, no PHP. ?>
<div v-if="selected === 'app-5'" id="ctf-panel-data-sharing" role="tabpanel" aria-labelledby="ctf-settings-tab-data-sharing" tabindex="0">
    <div class="sb-tab-box sb-data-sharing-consent-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{debugTab.dataSharingTitle}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="ctf-data-sharing-consent" class="ctf-checkbox">
                    <input type="checkbox" name="ctf-data-sharing-consent" id="ctf-data-sharing-consent"
                           v-model="model.debug.sbc_data_sharing_consent">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">{{debugTab.dataSharingDesc}}</span>
                <div class="ctf-debug-consent-links">
                    <a :href="debugTab.permissionsUrl" target="_blank" rel="noopener">{{debugTab.permissionsLinkText}}</a>
                    <a :href="debugTab.termsUrl" target="_blank" rel="noopener">{{debugTab.termsLinkText}}</a>
                    <a :href="debugTab.privacyUrl" target="_blank" rel="noopener">{{debugTab.privacyLinkText}}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sb-tab-box sb-in-plugin-notifications-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{debugTab.notificationsTitle}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="ctf-in-plugin-notifications" class="ctf-checkbox">
                    <input type="checkbox" name="ctf-in-plugin-notifications" id="ctf-in-plugin-notifications"
                           v-model="model.debug.sbc_in_plugin_notifications"
                           :disabled="model.debug.sbc_data_sharing_consent">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">{{debugTab.notificationsDesc}}</span>
            </div>
        </div>
    </div>
</div>
