<template>
	<div id="dropbox_prefs" class="section">
		<h2>
			<DropboxIcon class="icon" />
			{{ t('integration_dropbox', 'Dropbox integration') }}
		</h2>
		<NcNoteCard type="info">
			<p>
				{{ t('integration_dropbox', 'If you want your Nextcloud users to authenticate to Dropbox using your Dropbox OAuth app, create one in Dropbox.') }}
			</p>
			<a href="https://www.dropbox.com/developers/apps" class="external" target="_blank">
				{{ t('integration_dropbox', 'Dropbox developer settings') }}
			</a>
			<p>
				{{ t('integration_dropbox', 'Make sure your give those permissions to your app:') }}
			</p>
			<p>
				<strong>account_info.read - files.metadata.read - files.content.read</strong>
			</p>
			<p>
				{{ t('integration_dropbox', 'No need to add any redirect URI.') }}
				{{ t('integration_dropbox', 'Then set the app key and app secret below.') }}
			</p>
		</NcNoteCard>
		<div id="dropbox-content">
			<div class="line">
				<label for="dropbox-client-id">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_dropbox', 'App key') }}
				</label>
				<input id="dropbox-client-id"
					v-model="state.client_id"
					type="password"
					:readonly="readonly"
					:placeholder="t('integration_dropbox', 'Your Dropbox application key')"
					@input="onInput"
					@focus="readonly = false">
			</div>
			<div class="line">
				<label for="dropbox-client-secret">
					<KeyIcon :size="20" class="icon" />
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
	</div>
</template>

<script>
import KeyIcon from 'vue-material-design-icons/Key.vue'

import DropboxIcon from './icons/DropboxIcon.vue'

import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		DropboxIcon,
		KeyIcon,
		NcNoteCard,
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
				.then(response => {
					showSuccess(t('integration_dropbox', 'Dropbox admin options saved'))
				})
				.catch(error => {
					showError(t('integration_dropbox', 'Failed to save Dropbox admin options'))
					console.error(error)
				})
				.then(() => {
				})
		},
	},
}
</script>

<style scoped lang="scss">
#dropbox_prefs {
	#dropbox-content{
		margin-left: 40px;
	}

	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 250px;
		}
	}
}
</style>
