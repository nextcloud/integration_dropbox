<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div id="dropbox_prefs" class="section">
		<h2>
			<DropboxIcon class="icon" />
			{{ t('integration_dropbox', 'Dropbox integration') }}
		</h2>
		<div id="dropbox-content">
			<NcNoteCard type="info">
				{{ t('integration_dropbox', 'If you want your Nextcloud users to authenticate to Dropbox using your Dropbox OAuth app, create one in Dropbox.') }}
				<br>
				<a href="https://www.dropbox.com/developers/apps" class="external" target="_blank">
					{{ t('integration_dropbox', 'Dropbox developer settings') }}
				</a>
				<br>
				{{ t('integration_dropbox', 'Make sure your give those permissions to your app:') }}
				<br>
				<strong>account_info.read - files.metadata.read - files.content.read</strong>
				<br>
				{{ t('integration_dropbox', 'No need to add any redirect URI.') }}
				{{ t('integration_dropbox', 'Then set the app key and app secret below.') }}
			</NcNoteCard>
			<NcTextField
				v-model="state.client_id"
				type="password"
				:label="t('integration_dropbox', 'App key')"
				:placeholder="t('integration_dropbox', 'Your Dropbox application key')"
				:readonly="readonly"
				:show-trailing-button="!!state.client_id"
				@trailing-button-click="state.client_id = ''; onInput()"
				@focus="readonly = false"
				@update:model-value="onInput">
				<template #icon>
					<KeyOutlineIcon :size="20" />
				</template>
			</NcTextField>
			<NcTextField
				v-model="state.client_secret"
				type="password"
				:label="t('integration_dropbox', 'App secret')"
				:placeholder="t('integration_dropbox', 'Your Dropbox application secret')"
				:readonly="readonly"
				:show-trailing-button="!!state.client_secret"
				@trailing-button-click="state.client_secret = ''; onInput()"
				@focus="readonly = false"
				@update:model-value="onInput">
				<template #icon>
					<KeyOutlineIcon :size="20" />
				</template>
			</NcTextField>
		</div>
	</div>
</template>

<script>
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'

import DropboxIcon from './icons/DropboxIcon.vue'

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

export default {
	name: 'AdminSettings',

	components: {
		DropboxIcon,
		KeyOutlineIcon,
		NcNoteCard,
		NcTextField,
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
		async saveOptions() {
			await confirmPassword()
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
	h2 {
		display: flex;
		align-items: center;
		justify-content: start;
		gap: 8px;
	}
	#dropbox-content{
		margin-left: 40px;
		display: flex;
		flex-direction: column;
		gap: 4px;
		max-width: 800px;
	}
}
</style>
