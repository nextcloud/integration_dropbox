<template>
	<div v-if="state.client_id" id="dropbox_prefs" class="section">
		<h2>
			<a class="icon icon-dropbox" />
			{{ t('integration_dropbox', 'Dropbox data migration') }}
		</h2>
		<div class="dropbox-content">
			<h3>{{ t('integration_dropbox', 'Authentication') }}</h3>
			<div v-if="!connected">
				<p class="settings-hint">
					<span v-if="codeFailed">
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
			<div v-else>
				<div class="dropbox-grid-form">
					<label>
						<a class="icon icon-checkmark-color" />
						{{ t('integration_dropbox', 'Connected as {user}', { user: state.user_name }) }}
					</label>
					<button id="dropbox-rm-cred" @click="onLogoutClick">
						<span class="icon icon-close" />
						{{ t('integration_dropbox', 'Disconnect from Dropbox') }}
					</button>
				</div>
				<br>
				<div v-if="storageSize > 0" id="import-storage">
					<h3>{{ t('integration_dropbox', 'Dropbox storage') }}</h3>
					<div v-if="!importingDropbox" class="output-selection">
						<label for="dropbox-output">
							<span class="icon icon-folder" />
							{{ t('integration_dropbox', 'Import directory') }}
						</label>
						<input id="dropbox-output"
							:readonly="true"
							:value="state.output_dir">
						<button
							@click="onOutputChange">
							<span class="icon-rename" />
						</button>
						<br><br>
					</div>
					<label>
						<span class="icon icon-folder" />
						{{ t('integration_dropbox', 'Dropbox storage size: {formSize}', { formSize: myHumanFileSize(storageSize, true) }) }}
					</label>
					<button v-if="enoughSpaceForDropbox && !importingDropbox"
						id="dropbox-import-files"
						@click="onImportDropbox">
						<span class="icon icon-files-dark" />
						{{ t('integration_dropbox', 'Import Dropbox files') }}
					</button>
					<span v-else-if="!enoughSpaceForDropbox">
						{{ t('integration_dropbox', 'Your Dropbox storage is bigger than your remaining space left ({formSpace})', { formSpace: myHumanFileSize(state.free_space) }) }}
					</span>
					<div v-else>
						<br>
						{{ n('integration_dropbox', '{amount} file imported', '{amount} files imported', nbImportedFiles, { amount: nbImportedFiles }) }}
						<br>
						{{ lastDropboxImportDate }}
						<br>
						<button @click="onCancelDropboxImport">
							<span class="icon icon-close" />
							{{ t('integration_dropbox', 'Cancel Dropbox files import') }}
						</button>
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
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay, humanFileSize } from '../utils'
import { showSuccess, showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/styles/toast.scss'
import moment from '@nextcloud/moment'

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
			codeFailed: false,
			// dropbox import stuff
			storageSize: -1,
			importingDropbox: false,
			lastDropboxImportTimestamp: 0,
			nbImportedFiles: 0,
			dropboxImportLoop: null,
		}
	},

	computed: {
		connected() {
			return this.state.user_name && this.state.user_name !== ''
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
				.then((response) => {
					showSuccess(t('integration_dropbox', 'Dropbox options saved'))
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to save Dropbox options')
						+ ': ' + error.response?.request?.responseText
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
					this.codeFailed = false
					this.getStorageInfo()
				})
				.catch((error) => {
					this.codeFailed = true
					showError(
						t('integration_dropbox', 'Failed to connect to Dropbox')
						+ ': ' + error.response?.request?.responseText
					)
				})
				.then(() => {
					this.codeLoading = false
				})
		},
		getStorageInfo() {
			const url = generateUrl('/apps/integration_dropbox/storage-size')
			axios.get(url)
				.then((response) => {
					if (response.data?.usageInStorage) {
						this.storageSize = response.data.usageInStorage
					}
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to get Dropbox storage information')
						+ ': ' + error.response?.request?.responseText
					)
				})
				.then(() => {
				})
		},
		getDropboxImportValues(launchLoop = false) {
			const url = generateUrl('/apps/integration_dropbox/import-files-info')
			axios.get(url)
				.then((response) => {
					if (response.data && Object.keys(response.data).length > 0) {
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
				.catch((error) => {
					console.debug(error)
				})
				.then(() => {
				})
		},
		onImportDropbox() {
			const req = {
				params: {
				},
			}
			const url = generateUrl('/apps/integration_dropbox/import-files')
			axios.get(url, req)
				.then((response) => {
					const targetPath = response.data.targetPath
					showSuccess(
						t('integration_dropbox', 'Starting importing files in {targetPath} directory', { targetPath })
					)
					this.getDropboxImportValues(true)
				})
				.catch((error) => {
					showError(
						t('integration_dropbox', 'Failed to start importing Dropbox storage')
						+ ': ' + error.response?.request?.responseText
					)
				})
				.then(() => {
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
				.then((response) => {
				})
				.catch((error) => {
					console.debug(error)
				})
				.then(() => {
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
				true
			)
		},
		myHumanFileSize(bytes, approx = false, si = false, dp = 1) {
			return humanFileSize(bytes, approx, si, dp)
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

	h3 {
		font-weight: bold;
	}

	#import-storage > button {
		width: 300px;
	}

	#import-storage > label {
		width: 300px;
		display: inline-block;

		span {
			margin-bottom: -2px;
		}
	}
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

.output-selection {
	display: flex;

	label,
	input {
		width: 300px;
	}
	button {
		width: 44px !important;
	}
}
</style>
