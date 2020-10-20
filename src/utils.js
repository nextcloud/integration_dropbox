let mytimer = 0
export function delay(callback, ms) {
	return function() {
		const context = this
		const args = arguments
		clearTimeout(mytimer)
		mytimer = setTimeout(function() {
			callback.apply(context, args)
		}, ms || 0)
	}
}

export function humanFileSize(bytes, approx = false, si = false, dp = 1) {
	const thresh = si ? 1000 : 1024

	if (Math.abs(bytes) < thresh) {
		return bytes + ' B'
	}

	const units = si
		? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
		: ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
	let u = -1
	const r = 10 ** dp

	do {
		bytes /= thresh
		++u
	} while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1)

	if (approx) {
		return Math.floor(bytes) + ' ' + units[u]
	} else {
		return bytes.toFixed(dp) + ' ' + units[u]
	}
}

export function detectBrowser() {
	// Opera 8.0+
	// eslint-disable-next-line
	if ((!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0) {
		return 'opera'
	}

	// Firefox 1.0+
	if (typeof InstallTrigger !== 'undefined') {
		return 'firefox'
	}

	// Chrome 1 - 79
	// eslint-disable-next-line
	if (!!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime)) {
		return 'chrome'
	}

	// Safari 3.0+ "[object HTMLElementConstructor]"
	// eslint-disable-next-line
	if (/constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification))) {
		return 'safari'
	}

	// Internet Explorer 6-11
	// eslint-disable-next-line
	if (/*@cc_on!@*/false || !!document.documentMode) {
		return 'ie'
	}

	// Edge 20+
	// eslint-disable-next-line
	if (!isIE && !!window.StyleMedia) {
		return 'edge'
	}

	// Edge (based on chromium) detection
	// eslint-disable-next-line
	if (isChrome && (navigator.userAgent.indexOf("Edg") != -1)) {
		return 'edge-chromium'
	}

	// Blink engine detection
	// eslint-disable-next-line
	if ((isChrome || isOpera) && !!window.CSS) {
		return 'blink'
	}
}
