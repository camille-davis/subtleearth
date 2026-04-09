(function () {
	const { addFilter } = wp.hooks;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl } = wp.components;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment, createElement: el } = wp.element;

	const BLOCK_NAME = 'core/cover';
	const ATTR_KEY = 'linkUrl';
	const ATTR_OPEN_NEW_TAB = 'linkOpenInNewTab';

	// Register the linkUrl attribute
	addFilter(
		'blocks.registerBlockType',
		'subtle/cover-block-link',
		(settings, name) => {
			if (name !== BLOCK_NAME) {
				return settings;
			}

			return Object.assign({}, settings, {
				attributes: Object.assign({}, settings.attributes, {
					[ATTR_KEY]: {
						type: 'string',
						default: '',
					},
					[ATTR_OPEN_NEW_TAB]: {
						type: 'boolean',
						default: false,
					},
				}),
			});
		}
	);

	// Add the link URL control to the block inspector
	const withLinkControl = createHigherOrderComponent((BlockEdit) => {
		return ({ name, attributes, setAttributes, ...props }) => {
			if (name !== BLOCK_NAME) {
				return el(BlockEdit, { name, attributes, setAttributes, ...props });
			}

			return el(
				Fragment,
				{},
				el(BlockEdit, { name, attributes, setAttributes, ...props }),
				el(
					InspectorControls,
					{ group: 'settings' },
					el(
						PanelBody,
						{ title: 'Link', initialOpen: false },
						el(TextControl, {
							label: 'Link URL',
							value: attributes[ATTR_KEY] || '',
							onChange: (value) => setAttributes({ [ATTR_KEY]: value }),
							help: 'Enter a URL to wrap the entire cover block in a link. Leave empty to disable.',
						}),
						el(ToggleControl, {
							label: 'Open in new tab',
							checked: attributes[ATTR_OPEN_NEW_TAB],
							onChange: (value) => setAttributes({ [ATTR_OPEN_NEW_TAB]: value }),
						})
					)
				)
			);
		};
	}, 'withLinkControl');

	addFilter('editor.BlockEdit', 'subtle/cover-block-link', withLinkControl);
})();
