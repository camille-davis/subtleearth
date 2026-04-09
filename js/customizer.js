(function ($) {
	wp.customize.bind('ready', function () {
		// Get all the controls in the typography section.
		const typographyControls = wp.customize
			.section('subtle_typography')
			.controls();
		typographyControls.forEach((control) => {
			// Create and insert 'Reset' button.
			const $resetButton = $(
				'<button type="button" class="button reset-button">' +
					subtleCustomizer.resetText + // eslint-disable-line no-undef
					'</button>'
			);
			control.container.find('input').after($resetButton);

			// On reset, repopulate input with default value.
			$resetButton.on('click', () => {
				const defaultValue = control.setting.default;
				control.container
					.find('input')
					.val(defaultValue)
					.trigger('change');
			});
		});
	});
})(jQuery);
