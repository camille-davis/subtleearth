(function () {
	const { addFilter } = wp.hooks;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl } = wp.components;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment, createElement: el } = wp.element;

	const BLOCK_NAME = 'core/columns';
	const COLUMN_MIN = 1;
	const COLUMN_MAX = 8;

	const columnOptions = Array.from({ length: COLUMN_MAX - COLUMN_MIN + 1 }, (_, i) => ({
		label: String(i + COLUMN_MIN),
		value: String(i + COLUMN_MIN),
	}));

	const selectOptions = [{ label: 'Select...', value: '' }, ...columnOptions];

	const breakpoints = ['desktop', 'medium', 'tablet', 'mobile'];
	const breakpointHelp = {
		desktop: 'Applies to all screen widths.',
		medium: 'Screen widths under 1200px.',
		tablet: 'Screen widths under 768px.',
		mobile: 'Screen widths under 576px.',
	};

	addFilter(
		'blocks.registerBlockType',
		'subtle/columns-block-columns-per-row',
		(settings, name) => {
			if (name !== BLOCK_NAME) {
				return settings;
			}

			const attributes = {};
			breakpoints.forEach(breakpoint => {
				attributes[`${breakpoint}ColumnsPerRow`] = {
					type: 'string',
					default: '',
				};
			});

			return Object.assign({}, settings, {
				attributes: Object.assign({}, settings.attributes, attributes),
			});
		}
	);

	const withColumnsPerRowControl = createHigherOrderComponent((BlockEdit) => {
		return ({ name, attributes, setAttributes, ...props }) => {
			if (name !== BLOCK_NAME) {
				return el(BlockEdit, { name, attributes, setAttributes, ...props });
			}

			const controls = breakpoints.map(breakpoint => {
				const label = breakpoint.charAt(0).toUpperCase() + breakpoint.slice(1);
				const attrKey = `${breakpoint}ColumnsPerRow`;
				return el(SelectControl, {
					key: breakpoint,
					label: label,
					value: attributes[attrKey] || '',
					options: selectOptions,
					onChange: (value) => setAttributes({ [attrKey]: value }),
					help: breakpointHelp[breakpoint],
				});
			});

			return el(
				Fragment,
				{},
				el(BlockEdit, { name, attributes, setAttributes, ...props }),
				el(
					InspectorControls,
					{ group: 'settings' },
					el(
						PanelBody,
						{ title: 'Columns per Row', initialOpen: true, order: 10 },
						...controls
					)
				)
			);
		};
	}, 'withColumnsPerRowControl');

	addFilter('editor.BlockEdit', 'subtle/columns-block-columns-per-row', withColumnsPerRowControl);

	// Hide column count warning
	(function() {
		const blockEditor = document.getElementById('editor');
		if (!blockEditor) {
			return;
		}

		const SIDEBAR_SELECTOR = '.interface-interface-skeleton__sidebar';
		const WARNING_SELECTOR = '.is-warning';
		const WARNING_TEXT = 'column count exceeds';

		const hideWarning = () => {
			const sidebar = document.querySelector(SIDEBAR_SELECTOR);
			if (!sidebar) {
				return;
			}

			sidebar.querySelectorAll(WARNING_SELECTOR).forEach(warning => {
				if ((warning.textContent || '').includes(WARNING_TEXT)) {
					warning.style.display = 'none';
				}
			});
		};

		const sidebarObserver = new MutationObserver(() => {
			const sidebar = document.querySelector(SIDEBAR_SELECTOR);
			if (!sidebar) {
				return;
			}

			hideWarning();

			const warningObserver = new MutationObserver(hideWarning);
			warningObserver.observe(sidebar, { childList: true, subtree: true });

			sidebarObserver.disconnect();
		});

		hideWarning();
		sidebarObserver.observe(blockEditor, { childList: true, subtree: true });
	})();

	addFilter(
		'editor.BlockListBlock',
		'subtle/columns-block-columns-per-row-attributes',
		(BlockListBlock) => {
			return ({ name, attributes, wrapperProps, ...props }) => {
				if (name !== BLOCK_NAME) {
					return el(BlockListBlock, { name, attributes, wrapperProps, ...props });
				}

				const values = {};
				let prevValue = '';
				breakpoints.forEach(breakpoint => {
					const attrKey = `${breakpoint}ColumnsPerRow`;
					values[breakpoint] = attributes[attrKey] || prevValue;
					prevValue = values[breakpoint];
				});

				if (!Object.values(values).some(v => v)) {
					return el(BlockListBlock, { name, attributes, wrapperProps, ...props });
				}

				const dataAttrs = {};
				breakpoints.forEach(breakpoint => {
					if (values[breakpoint]) {
						dataAttrs[`data-columns-per-row-${breakpoint}`] = values[breakpoint];
					}
				});

				return el(BlockListBlock, {
					...props,
					name,
					attributes,
					wrapperProps: { ...wrapperProps, ...dataAttrs },
				});
			};
		}
	);
})();

