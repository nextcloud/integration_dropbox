<template>
	<div v-if="state.client_id" id="dropbox_prefs" class="section">
		<h2>
			<a class="icon icon-dropbox" />
			{{ t('integration_dropbox', 'Dropbox data migration') }}
		</h2>
		<div v-if="showOAuth" class="dropbox-content">
			<div v-if="!connected">
				<p class="settings-hint">
					<span v-if="usingCustomApp">
						<span class="icon icon-details" />
						{{ t('integration_dropbox', 'If you have trouble authenticating, ask your Nextcloud administrator to check Dropbox admin settings.') }}
					</span>
				</p>
				<br>
				<a class="button"
					target="_blank"
					:href="oauthUrl"
					:class="{ loading: codeLoading }"
					:disabled="codeLoading === true">
					<span class="icon icon-external" />
					{{ t('integration_dropbox', 'Connect to Dropbox to get an access code') }}
				</a>
				<br><br>
				<div class="dropbox-grid-form">
					<label for="dropbox-code">
						<a class="icon icon-category-auth" />
						{{ t('integration_dropbox', 'Dropbox access code') }}
					</label>
					<input id="dropbox-code"
						v-model="accessCode"
						type="text"
						:class="{ loading: codeLoading }"
						:disabled="codeLoading === true"
						:placeholder="t('integration_dropbox', 'Access code')"
						@input="onAccessCodeInput">
				</div>
			</div>
			<div v-else class="dropbox-grid-form">
				<label>
					<a class="icon icon-checkmark-color" />
					{{ t('integration_dropbox', 'Connected as {user}', { user: state.user_name }) }}
				</label>
				<button id="dropbox-rm-cred" @click="onLogoutClick">
					<span class="icon icon-close" />
					{{ t('integration_dropbox', 'Disconnect from Dropbox') }}
				</button>
			</div>
		</div>
		<p v-else class="settings-hint">
			{{ t('integration_dropbox', 'You must access this page with HTTPS to be able to authenticate to Dropbox.') }}
		</p>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl, imagePath } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay, detectBrowser } from '../utils'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'PersonalSettings',

	components: {
	},

	props: [],

	data() {
		return {
			state: loadState('integration_dropbox', 'user-config'),
			accessCode: '',
			codeLoading: false,
			chromiumImagePath: imagePath('integration_dropbox', 'chromium.png'),
			firefoxImagePath: imagePath('integration_dropbox', 'firefox.png'),
			isChromium: detectBrowser() === 'chrome',
			isFirefox: detectBrowser() === 'firefox',
		}
	},

	computed: {
		showOAuth() {
			// 2 cases, no client secret means the default app is used => https required
			// if there is a client secret, redirect URL is probably correctly defined by NC admin in Dropbox OAuth app
			return this.state.client_id
				&& (this.state.client_secret || window.location.protocol === 'https:')
		},
		usingCustomApp() {
			return this.state.client_id && this.state.client_secret
		},
		connected() {
			return this.state.user_name && this.state.user_name !== ''
		},
		oauthUrl() {
			return 'https://www.dropbox.com/oauth2/authorize?client_id=' + encodeURIComponent(this.state.client_id)
				+ '&response_type=code'
				+ '&token_access_type=offline'
		},
	},

	watch: {
	},

	mounted() {
		const paramString = window.location.search.substr(1)
		// eslint-disable-next-line
		const urlParams = new URLSearchParams(paramString)
		const rdToken = urlParams.get('dropboxToken')
		if (rdToken === 'success') {
			showSuccess(t('integration_dropbox', 'Successfully connected to Dropbox!'))
		} else if (rdToken === 'error') {
			showError(t('integration_dropbox', 'Dropbox OAuth error:') + ' ' + urlParams.get('message'))
		}
	},

	methods: {
		onLogoutClick() {
			this.state.user_name = ''
			this.saveOptions()
		},
		onAccessCodeInput() {
			delay(() => {
				this.saveAccessCode()
			}, 2000)()
		},
		saveOptions() {
			const req = {
				values: {
					user_name: this.state.user_name,
				},
			}
			const url = generateUrl('/apps/integration_dropbox/config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_dropbox', 'Dropbox options saved'))
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to save Dropbox options')
						+ ': ' + error.response.request.responseText
					)
				})
				.then(() => {
				})
		},
		saveAccessCode() {
			if (this.accessCode === '') {
				return
			}
			this.codeLoading = true
			const req = {
				code: this.accessCode,
			}
			const url = generateUrl('/apps/integration_dropbox/access-code')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_dropbox', 'Successfully connected to Dropbox!'))
					this.state.user_name = response.data.user_name
					this.accessCode = ''
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to connect to Dropbox')
						+ ': ' + error.response?.request?.responseText
					)
				})
				.then(() => {
					this.codeLoading = false
				})
		},
	},
}
</script>

<style scoped lang="scss">
#dropbox_prefs .icon {
	display: inline-block;
	width: 32px;
}

.icon-dropbox {
	background-image: url(./../../img/app-dark.svg);
	background-size: 23px 23px;
	height: 23px;
	margin-bottom: -4px;
}

body.theme--dark .icon-dropbox {
	background-image: url(./../../img/app.svg);
}

.dropbox-content {
	margin-left: 40px;
}

.dropbox-grid-form {
	max-width: 600px;
	display: grid;
	grid-template: 1fr / 1fr 1fr;
	button .icon {
		margin-bottom: -1px;
	}
	input {
		width: 100%;
	}
}

.dropbox-grid-form label {
	line-height: 38px;
}

</style>
