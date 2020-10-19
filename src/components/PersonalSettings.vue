<template>
	<div v-if="state.client_id" id="dropbox_prefs" class="section">
		<h2>
			<a class="icon icon-dropbox" />
			{{ t('integration_dropbox', 'Dropbox data migration') }}
		</h2>
		<div v-if="showOAuth" class="dropbox-content">
			<div v-if="!connected">
				<p class="settings-hint">
					<span class="icon icon-details" />
					<span v-if="usingCustomApp">
						{{ t('integration_dropbox', 'If you have trouble authenticating, ask your Nextcloud administrator to check Dropbox admin settings.') }}
					</span>
					<span v-else>
						{{ t('integration_dropbox', 'Make sure to accept the protocol registration on top of this page to allow authentication to Dropbox.') }}
						<span v-if="isChromium">
							<br>
							{{ t('integration_dropbox', 'With Chrome/Chromium, you should see a popup on browser top-left to authorize this page to open "web+nextclouddropbox" links.') }}
							<br>
							{{ t('integration_dropbox', 'If you don\'t see the popup, you can still click on this icon in the address bar.') }}
							<br>
							<img :src="chromiumImagePath">
							<br>
							{{ t('integration_dropbox', 'Then authorize this page to open "web+nextclouddropbox" links.') }}
							<br>
							{{ t('integration_dropbox', 'If you still don\'t manage to get the protocol registered, check your settings on this page:') }}
							<b>chrome://settings/handlers</b>
						</span>
						<span v-else-if="isFirefox">
							<br>
							{{ t('integration_dropbox', 'With Firefox, you should see a bar on top of this page to authorize this page to open "web+nextclouddropbox" links.') }}
							<br><br>
							<img :src="firefoxImagePath">
						</span>
					</span>
				</p>
				<button id="dropbox-oauth" @click="onOAuthClick">
					<span class="icon icon-external" />
					{{ t('integration_dropbox', 'Connect to Dropbox') }}
				</button>
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
			readonly: true,
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

		// register protocol handler
		if (window.isSecureContext && window.navigator.registerProtocolHandler) {
			const ncUrl = window.location.protocol
				+ '//' + window.location.hostname
				+ window.location.pathname.replace('settings/user/connected-accounts', '').replace('/index.php/', '')
			window.navigator.registerProtocolHandler(
				'web+nextclouddropbox',
				generateUrl('/apps/integration_dropbox/oauth-protocol-redirect') + '?url=%s',
				t('integration_dropbox', 'Nextcloud Dropbox integration on {ncUrl}', { ncUrl })
			)
		}
	},

	methods: {
		onLogoutClick() {
			this.state.user_name = ''
			this.saveOptions()
		},
		onInput() {
			const that = this
			delay(() => {
				that.saveOptions()
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
		onOAuthClick() {
			let redirectUri
			if (this.state.client_secret) {
				const redirectEndpoint = generateUrl('/apps/integration_dropbox/oauth-redirect')
				redirectUri = window.location.protocol + '//' + window.location.protocol + redirectEndpoint
			} else {
				redirectUri = 'web+nextclouddropbox://oauth-protocol-redirect'
			}
			const oauthState = Math.random().toString(36).substring(3)
			const requestUrl = 'https://www.dropbox.com/api/v1/authorize?client_id=' + encodeURIComponent(this.state.client_id)
				+ '&redirect_uri=' + encodeURIComponent(redirectUri)
				+ '&state=' + encodeURIComponent(oauthState)
				+ '&response_type=code'
				+ '&duration=permanent'
				+ '&scope=' + encodeURIComponent('identity history mysubdropboxs privatemessages read wikiread')

			const req = {
				values: {
					oauth_state: oauthState,
				},
			}
			const url = generateUrl('/apps/integration_dropbox/config')
			axios.put(url, req)
				.then((response) => {
					window.location.replace(requestUrl)
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to save Dropbox OAuth state')
						+ ': ' + error.response.request.responseText
					)
				})
				.then(() => {
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
}

.dropbox-grid-form label {
	line-height: 38px;
}

</style>
