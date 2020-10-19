<template>
	<div id="dropbox_prefs" class="section">
		<h2>
			<a class="icon icon-dropbox" />
			{{ t('integration_dropbox', 'Dropbox integration') }}
		</h2>
		<p class="settings-hint">
			{{ t('integration_dropbox', 'There are 2 ways to allow your Nextcloud users to use OAuth to authenticate to Dropbox:') }}
			<br><br>
			<ul>
				<li>
					<b>1. </b>{{ t('integration_dropbox', 'Leave all fields empty to use default Nextcloud Dropbox OAuth app.') }}
					<br><br>
				</li>
				<li>
					<b>2. </b>{{ t('integration_dropbox', 'Create your own Dropbox "web application" in Dropbox preferences and put the application ID and secret below.') }}
					<a href="https://www.dropbox.com/prefs/apps" target="_blank" class="external">{{ t('integration_dropbox', 'Dropbox app settings') }}</a>
					<br><br>
					<span class="icon icon-details" />
					{{ t('integration_dropbox', 'Make sure you set the "Redirection uri" to one of the following URLs:') }}
					<b> {{ redirect_uri }} </b>
					<br>
					<b> {{ redirect_uri_protocol }} </b>
					<br><br>
				</li>
			</ul>
		</p>
		<div class="grid-form">
			<label for="dropbox-client-id">
				<a class="icon icon-category-auth" />
				{{ t('integration_dropbox', 'Application ID') }}
			</label>
			<input id="dropbox-client-id"
				v-model="state.client_id"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_dropbox', 'Client ID of your Dropbox application')"
				@input="onInput"
				@focus="readonly = false">
			<label for="dropbox-client-secret">
				<a class="icon icon-category-auth" />
				{{ t('integration_dropbox', 'Application secret') }}
			</label>
			<input id="dropbox-client-secret"
				v-model="state.client_secret"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_dropbox', 'Client secret of your Dropbox application')"
				@input="onInput"
				@focus="readonly = false">
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils'
import { showSuccess, showError } from '@nextcloud/dialogs'

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
			redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/integration_dropbox/oauth-redirect'),
			redirect_uri_protocol: 'web+nextclouddropbox://oauth-protocol-redirect',
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onInput() {
			const that = this
			delay(() => {
				that.saveOptions()
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
						+ ': ' + error.response.request.responseText
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
