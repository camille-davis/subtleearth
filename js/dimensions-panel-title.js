(function () {
	function updatePanelTitles() {
		document.querySelectorAll('.block-editor-block-inspector [class*="panel"], [aria-label*="Dimensions"]').forEach((el) => {
			const text = el.textContent || el.getAttribute('aria-label') || '';
			if (text.includes('Dimensions') && !text.includes('Spacing')) {
				if (el.textContent) {
					el.textContent = el.textContent.replace('Dimensions', 'Spacing');
				}
				const ariaLabel = el.getAttribute('aria-label');
				if (ariaLabel) {
					el.setAttribute('aria-label', ariaLabel.replace('Dimensions', 'Spacing'));
				}
			}
		});
	}

	if (wp?.i18n?.setLocaleData) {
		wp.i18n.setLocaleData({ 'Dimensions': ['Spacing'] }, 'default');
	}

	const observer = new MutationObserver(updatePanelTitles);
	const inspector = document.querySelector('.block-editor-block-inspector');

	function init() {
		updatePanelTitles();
		if (inspector) {
			observer.observe(inspector, { childList: true, subtree: true, characterData: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => setTimeout(init, 500));
	} else {
		setTimeout(init, 500);
	}
})();

