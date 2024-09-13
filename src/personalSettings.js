/* jshint esversion: 6 */

/**
 * Nextcloud - dropbox
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

import Vue from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'
Vue.mixin({ methods: { t, n } })

const View = Vue.extend(PersonalSettings)
new View().$mount('#dropbox_prefs')
