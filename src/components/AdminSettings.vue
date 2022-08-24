<template>
	<div id="dropbox_prefs" class="section">
		<h2>
			<a class="icon icon-dropbox" />
			{{ t('integration_dropbox', 'Dropbox integration') }}
		</h2>
		<p class="settings-hint">
			{{ t('integration_dropbox', 'Leave all fields empty to use default Nextcloud Dropbox OAuth app.') }}
			<br><br>
			{{ t('integration_dropbox', 'If you want your Nextcloud users to authenticate to Dropbox using your own Dropbox OAuth app, create one in Dropbox.') }}
			<a href="https://www.dropbox.com/developers/apps" class="external" target="_blank">
				{{ t('integration_dropbox', 'Dropbox developer settings') }}
			</a>
			<br>
			{{ t('integration_dropbox', 'Make sure your give those permissions to your app:') }}
			<br>
			<b>account_info.read - files.metadata.read - files.content.read</b>
			<br>
			{{ t('integration_dropbox', 'No need to add any redirect URI.') }}
			<br>
			{{ t('integration_dropbox', 'Then set the app key and app secret below.') }}
		</p>
		<div class="grid-form">
			<label for="dropbox-client-id">
				<a class="icon icon-category-auth" />
				{{ t('integration_dropbox', 'App key') }}
			</label>
			<input id="dropbox-client-id"
				v-model="state.client_id"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_dropbox', 'Your Dropbox application key')"
				@input="onInput"
				@focus="readonly = false">
			<label for="dropbox-client-secret">
				<a class="icon icon-category-auth" />
				{{ t('integration_dropbox', 'App secret') }}
			</label>
			<input id="dropbox-client-secret"
				v-model="state.client_secret"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_dropbox', 'Your Dropbox application secret')"
				@input="onInput"
				@focus="readonly = false">
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/styles/toast.scss'

export default {
	name: 'AdminSettings',

	components: {
	},

	props: [],

	data() {
		return {
			state: loadState('integration_dropbox', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onInput() {
			delay(() => {
				this.saveOptions()
			}, 2000)()
		},
		saveOptions() {
			const req = {
				values: {
					client_id: this.state.client_id,
					client_secret: this.state.client_secret,
				},
			}
			const url = generateUrl('/apps/integration_dropbox/admin-config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_dropbox', 'Dropbox admin options saved'))
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to save Dropbox admin options')
						+ ': ' + error.response?.request?.responseText
					)
					console.debug(error)
				})
				.then(() => {
				})
		},
	},
}
</script>

<style scoped lang="scss">
.grid-form label {
	line-height: 38px;
}

.grid-form input {
	width: 100%;
}

.grid-form {
	max-width: 500px;
	display: grid;
	grid-template: 1fr / 1fr 1fr;
	margin-left: 30px;
}

#dropbox_prefs .icon {
	display: inline-block;
	width: 32px;
}

#dropbox_prefs .grid-form .icon {
	margin-bottom: -3px;
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

</style>
