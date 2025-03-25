<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div id="dropbox_prefs" class="section">
		<h2>
			<DropboxIcon />
			{{ t('integration_dropbox', 'Dropbox data migration') }}
		</h2>
		<NcNoteCard v-if="!isAdminConfigured" type="info">
			{{ t('integration_dropbox', 'Your administrator has not yet configured this integration.') }}
		</NcNoteCard>
		<div v-else class="dropbox-content">
			<h3>{{ t('integration_dropbox', 'Authentication') }}</h3>
			<div v-if="!connected">
				<br>
				<p class="settings-hint">
					<span v-if="codeFailed" class="line">
						<InformationOutlineIcon />
						{{ t('integration_dropbox', 'If you have trouble authenticating, ask your Nextcloud administrator to check Dropbox admin settings.') }}
					</span>
				</p>
				<br>
				<a target="_blank"
					:href="oauthUrl">
					<NcButton :class="{ loading: codeLoading }"
						:disabled="codeLoading === true">
						<template #icon>
							<OpenInNewIcon />
						</template>
						{{ t('integration_dropbox', 'Connect to Dropbox to get an access code') }}
					</NcButton>
				</a>
				<br><br>
				<div class="line">
					<label for="dropbox-code">
						<KeyIcon />
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
			<div v-else>
				<div class="line">
					<label>
						<CheckIcon :size="20" />
						{{ t('integration_dropbox', 'Connected as {user} ({email})', { user: state.user_name, email: state.email }) }}
					</label>
					<NcButton @click="onLogoutClick">
						<template #icon>
							<CloseIcon />
						</template>
						{{ t('integration_dropbox', 'Disconnect from Dropbox') }}
					</NcButton>
				</div>
				<br>
				<div v-if="storageSize > 0"
					id="import-storage">
					<h3>{{ t('integration_dropbox', 'Dropbox storage') }}</h3>
					<div v-if="!importingDropbox" class="line">
						<label for="dropbox-output">
							<FolderIcon :size="20" />
							{{ t('integration_dropbox', 'Import directory') }}
						</label>
						<input id="dropbox-output"
							:readonly="true"
							:value="state.output_dir">
						<NcButton @click="onOutputChange">
							<template #icon>
								<PencilIcon />
							</template>
						</NcButton>
						<br><br>
					</div>
					<div class="line">
						<label>
							<FolderIcon :size="20" />
							{{ t('integration_dropbox', 'Dropbox storage size: {formSize}', { formSize: myHumanFileSize(storageSize, true) }) }}
						</label>
						<NcButton v-if="enoughSpaceForDropbox && !importingDropbox"
							@click="onImportDropbox">
							<template #icon>
								<FolderIcon :size="20" />
							</template>
							{{ t('integration_dropbox', 'Import Dropbox files') }}
						</NcButton>
						<span v-else-if="!enoughSpaceForDropbox">
							{{ t('integration_dropbox', 'Your Dropbox storage is bigger than your remaining space left ({formSpace})', { formSpace: myHumanFileSize(state.free_space) }) }}
						</span>
						<div v-else>
							<br>
							{{ n('integration_dropbox', '{amount} file imported', '{amount} files imported', nbImportedFiles, { amount: nbImportedFiles }) }}
							<br>
							<span v-if="importJobRunning">{{ t('integration_dropbox', 'Import job is currently running') }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="loading-small" /></span>
							<span v-else>{{ lastDropboxImportDate }}</span>
							<br>
							<span v-if="lastImportError">{{ t('integration_dropbox', 'An error occured during the import: {error}', {error: lastImportError}) }}</span>
							<NcButton @click="onCancelDropboxImport">
								<template #icon>
									<CloseIcon />
								</template>
								{{ t('integration_dropbox', 'Cancel Dropbox files import') }}
							</NcButton>
						</div>
					</div>
				</div>
				<div v-else-if="storageSize === 0">
					{{ t('integration_dropbox', 'Your Dropbox storage is empty') }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'

import DropboxIcon from './icons/DropboxIcon.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay, humanFileSize } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'

export default {
	name: 'PersonalSettings',

	components: {
		DropboxIcon,
		NcButton,
		NcNoteCard,
		OpenInNewIcon,
		PencilIcon,
		CloseIcon,
		FolderIcon,
		KeyIcon,
		CheckIcon,
		InformationOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_dropbox', 'user-config'),
			accessCode: '',
			codeLoading: false,
			codeFailed: false,
			// dropbox import stuff
			storageSize: -1,
			importingDropbox: false,
			lastDropboxImportTimestamp: 0,
			importJobRunning: 0,
			lastImportError: '',
			nbImportedFiles: 0,
			dropboxImportLoop: null,
		}
	},

	computed: {
		isAdminConfigured() {
			return this.state.client_id !== '' && this.state.has_client_secret === true
		},
		connected() {
			return (this.state.user_name && this.state.user_name !== '') || this.state.account_id || this.state.email
		},
		oauthUrl() {
			return 'https://www.dropbox.com/oauth2/authorize?'
				+ 'client_id=' + encodeURIComponent(this.state.client_id)
				+ '&response_type=code'
				+ '&token_access_type=offline'
		},
		enoughSpaceForDropbox() {
			return this.storageSize === 0 || this.state.user_quota === 'none' || this.storageSize < this.state.free_space
		},
		lastDropboxImportDate() {
			return this.lastDropboxImportTimestamp !== 0
				? t('integration_dropbox', 'Last Dropbox import job at {date}', { date: moment.unix(this.lastDropboxImportTimestamp).format('LLL') })
				: t('integration_dropbox', 'Dropbox import process will begin soon')
		},
	},

	mounted() {
		// get informations if we are connected
		if (this.connected) {
			this.getStorageInfo()
			this.getDropboxImportValues(true)
		}
	},

	methods: {
		onLogoutClick() {
			this.state.user_name = ''
			this.state.email = null
			this.state.account_id = null
			this.saveOptions({ user_name: this.state.user_name })
		},
		onAccessCodeInput() {
			delay(() => {
				this.saveAccessCode()
			}, 2000)()
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_dropbox/config')
			axios.put(url, req)
				.then(response => {
					showSuccess(t('integration_dropbox', 'Dropbox options saved'))
				})
				.catch(error => {
					showError(t('integration_dropbox', 'Failed to save Dropbox options'))
					console.error(error)
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
				.then(response => {
					showSuccess(t('integration_dropbox', 'Successfully connected to Dropbox!'))
					this.state.user_name = response.data.user_name
					this.state.email = response.data.email
					this.state.account_id = response.data.account_id
					this.accessCode = ''
					this.codeFailed = false
					this.getStorageInfo()
				})
				.catch(error => {
					this.codeFailed = true
					showError(t('integration_dropbox', 'Failed to connect to Dropbox'))
					console.error(error)
				})
				.then(() => {
					this.codeLoading = false
				})
		},
		getStorageInfo() {
			const url = generateUrl('/apps/integration_dropbox/storage-size')
			axios.get(url)
				.then(response => {
					if (response.data?.usageInStorage) {
						this.storageSize = response.data.usageInStorage
					}
				})
				.catch(error => {
					showError(t('integration_dropbox', 'Failed to get Dropbox storage information'))
					console.error(error)
				})
		},
		getDropboxImportValues(launchLoop = false) {
			const url = generateUrl('/apps/integration_dropbox/import-files-info')
			axios.get(url)
				.then(response => {
					if (response.data && Object.keys(response.data).length > 0) {
						this.lastImportError = response.data.last_import_error
						this.importJobRunning = response.data.dropbox_import_running
						this.lastDropboxImportTimestamp = response.data.last_dropbox_import_timestamp
						this.nbImportedFiles = response.data.nb_imported_files
						this.importingDropbox = response.data.importing_dropbox
						if (!this.importingDropbox) {
							clearInterval(this.dropboxImportLoop)
						} else if (launchLoop) {
							// launch loop if we are currently importing AND it's the first time we call getDropboxImportValues
							this.dropboxImportLoop = setInterval(() => this.getDropboxImportValues(), 10000)
						}
					}
				})
				.catch(error => {
					console.debug(error)
				})
		},
		onImportDropbox() {
			const req = {
				params: {
				},
			}
			const url = generateUrl('/apps/integration_dropbox/import-files')
			axios.get(url, req)
				.then(response => {
					const targetPath = response.data.targetPath
					showSuccess(t('integration_dropbox', 'Starting importing files in {targetPath} directory', { targetPath }))
					this.getDropboxImportValues(true)
				})
				.catch(error => {
					showError(t('integration_dropbox', 'Failed to start importing Dropbox storage'))
					console.error(error)
				})
		},
		onCancelDropboxImport() {
			this.importingDropbox = false
			clearInterval(this.dropboxImportLoop)
			const req = {
				values: {
					importing_dropbox: '0',
					last_dropbox_import_timestamp: '0',
					nb_imported_files: '0',
				},
			}
			const url = generateUrl('/apps/integration_dropbox/config')
			axios.put(url, req)
				.then(response => {
				})
				.catch(error => {
					console.debug(error)
				})
		},
		onOutputChange() {
			OC.dialogs.filepicker(
				t('integration_dropbox', 'Choose where to write imported files'),
				(targetPath) => {
					if (targetPath === '') {
						targetPath = '/'
					}
					this.state.output_dir = targetPath
					this.saveOptions({ output_dir: this.state.output_dir })
				},
				false,
				'httpd/unix-directory',
				true,
			)
		},
		myHumanFileSize(bytes, approx = false, si = false, dp = 1) {
			return humanFileSize(bytes, approx, si, dp)
		},
	},
}
</script>

<style scoped lang="scss">
#dropbox_prefs {

	h2 {
		display: flex;
		align-items: center;
		span {
			margin-right: 8px;
		}
	}

	.dropbox-content {
		margin-left: 40px;

		h3 {
			font-weight: bold;
		}

		.line {
			display: flex;
			align-items: center;

			> input {
				width: 250px;
			}
			> span.material-design-icon {
				margin-right: 8px;
			}

			label {
				display: flex;
				align-items: center;
				width: 300px;

				> span {
					margin-right: 8px;
				}
			}
		}

		.folder-icon {
			margin-right: 0;
		}

		label > .folder-icon {
			color: var(--color-primary);
			margin-right: 8px;
		}

		#import-storage {
			> button {
				width: 300px;
			}

			> label {
				width: 300px;
				display: inline-block;

				span {
					margin-bottom: -2px;
				}
			}

			.output-selection label span {
				margin-bottom: -2px;
			}
		}
	}

	.output-selection {
		display: flex;
		align-items: center;

		label,
		input {
			width: 300px;
		}

		button {
			width: 44px !important;
		}
	}
}
</style>
