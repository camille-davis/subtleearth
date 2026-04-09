(function () {
	'use strict';

	/**
	 * Update editor title visibility based on hide_title setting
	 */
	function updateEditorTitleVisibility(hideTitle) {
		const editorCanvas = document.querySelector('iframe[name="editor-canvas"]') ||
		                      document.querySelector('iframe.editor-canvas');

		if (!editorCanvas) {
			return;
		}

		const updateTitle = () => {
			try {
				const editorDoc = editorCanvas.contentDocument || editorCanvas.contentWindow.document;
				if (!editorDoc) {
					return;
				}

				const titleWrapper = editorDoc.querySelector('.editor-visual-editor__post-title-wrapper');
				if (titleWrapper) {
					const shouldHide = hideTitle === true || hideTitle === '1' || hideTitle === 1;
					titleWrapper.style.display = shouldHide ? 'none' : '';
				}
			} catch (e) {
				// Cross-origin or other access issues - ignore silently
			}
		};

		updateTitle();

		if (editorCanvas.contentDocument && editorCanvas.contentDocument.readyState === 'complete') {
			updateTitle();
		} else {
			editorCanvas.addEventListener('load', updateTitle, { once: true });
		}

		if (editorCanvas.contentDocument) {
			const observer = new MutationObserver(updateTitle);
			observer.observe(editorCanvas.contentDocument.body || editorCanvas.contentDocument, {
				childList: true,
				subtree: true,
			});

			setTimeout(() => observer.disconnect(), 30000);
		}
	}

	/**
	 * Initialize based on saved meta value
	 */
	function initFromMeta() {
		const hideTitle = subtlePageAppearance?.hideTitle || false;
		updateEditorTitleVisibility(hideTitle);
	}

	/**
	 * Listen for meta box checkbox changes
	 */
	function watchMetaBoxChanges() {
		const checkbox = document.querySelector('input[name="subtle_hide_page_title"]');
		if (!checkbox) {
			return;
		}

		checkbox.addEventListener('change', function() {
			updateEditorTitleVisibility(this.checked ? '1' : '0');
		});
	}

	/**
	 * Initialize when DOM is ready
	 */
	function init() {
		initFromMeta();
		watchMetaBoxChanges();

		// Retry after delays to catch late-loading editor
		setTimeout(initFromMeta, 500);
		setTimeout(initFromMeta, 1500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

