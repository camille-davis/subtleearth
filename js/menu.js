jQuery(document).ready(function ($) {
	const $menuOpen = $('#open-menu');
	const $menuOuter = $('#menu-outer');

	if (!$menuOpen.length || !$menuOuter.length) {
		return;
	}

	const $body = $('body');
	const $window = $(window);
	const $menuClose = $('#close-menu');
	const focusableSelector = 'a[href]:not(:disabled):not(:hidden), ' +
		'button:not(:disabled):not(:hidden), ' +
		'input:not(:disabled):not(:hidden), ' +
		'select:not(:disabled):not(:hidden), ' +
		'textarea:not(:disabled):not(:hidden), ' +
		'summary:not(:disabled):not(:hidden), ' +
		'[tabindex]:not([tabindex="-1"]):not(:disabled):not(:hidden)';

	/**
	 * Helper functions for tab flow.
	 */

	// Stores original tabindex values to be restored.
	const tabindexStore = new Map();

	// Removes covered elements from tab flow when mobile menu is open.
	function removeFromTabFlow() {
		$(focusableSelector).each(function() {
			const $el = $(this);
			const element = this;

			// Skip element if we've already processed it.
			if (tabindexStore.has(element)) {
				return;
			}

			// Skip element if it's inside the menu.
			if ($el.closest('#menu-outer').length) {
				return;
			}

			// Skip element if it's inside uncovered admin bar.
			// (Admin bar is covered at 600px and under.)
			const isInAdminBar = $el.closest('#wpadminbar').length;
			if (isInAdminBar && $window.width() > 600) {
				return;
			}

			// Save original tabindex.
			const originalTabindex = $el.attr('tabindex');
			tabindexStore.set(element, originalTabindex !== undefined ? originalTabindex : null);

			// Remove element from tab flow.
			$el.attr('tabindex', '-1');
		});
	}

	// Restores saved tabindex values.
	function restoreTabFlow() {
		tabindexStore.forEach((originalTabindex, element) => {
			const $el = $(element);
			if (originalTabindex === null) {
				$el.removeAttr('tabindex');
			} else {
				$el.attr('tabindex', originalTabindex);
			}
		});
		tabindexStore.clear();
	}

	/**
	 * Open/close functionality.
	 */

	function isOpen() {
		return $menuOpen.attr('aria-expanded') === 'true';
	}

	function openMenu() {
		if (isOpen()) {
			return;
		}

		$menuOuter.addClass('transitioning');
		$menuOpen.attr('aria-expanded', 'true');
		$body.addClass('menu-open');
		removeFromTabFlow();

		setTimeout(() => {
			$menuOuter.removeClass('transitioning');
			$menuClose.focus();
		}, 300);
	}

	function closeMenu() {
		if (!isOpen()) {
			return;
		}

		$menuOuter.addClass('transitioning');
		$menuOpen.attr('aria-expanded', 'false');
		restoreTabFlow();

		setTimeout(() => {
			$menuOuter.removeClass('transitioning');
			$body.removeClass('menu-open');
			$menuOpen.focus();
		}, 300);
	}

	$menuOpen.on('click', openMenu);
	$menuClose.on('click', closeMenu);

	// Close after clicking a menu link.
	$('.menu-links a').on('click', closeMenu);

	// Close by clicking on modal background.
	$menuOuter.on('click', function (e) {
		if (!$(e.target).closest('.menu-inner').length) {
			console.log('clicked on modal background');
			closeMenu();
		}
	});

	// Close by pressing Escape.
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && isOpen()) {
			closeMenu();
		}
	});

	$window.on('resize', function () {

		// If menu is closed, do nothing.
		if (!isOpen()) {
			return;
		}

		// If we're at desktop width, close the menu.
		if ($window.width() > 1080) {
			closeMenu();
			return;
		}

		// Update tab flow to include or exclude admin bar.
		restoreTabFlow();
		removeFromTabFlow();
	});
});
