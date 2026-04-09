(function () {
	const { addFilter } = wp.hooks;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment, createElement: el } = wp.element;

	const BLOCK_NAME = 'core/image';
	const imageAttributes = [
		{ attrKey: 'fullwidth', className: 'is-fullwidth-image', label: 'Fullwidth' },
		{ attrKey: 'fullheight', className: 'is-fullheight-image', label: 'Fullheight' },
	];

	imageAttributes.forEach(({ attrKey, className: cssClass }) => {
		addFilter(
			'blocks.registerBlockType',
			`subtle/image-block-${attrKey}`,
			(settings, name) => {
				if (name !== BLOCK_NAME) {
					return settings;
				}

				return Object.assign({}, settings, {
					attributes: Object.assign({}, settings.attributes, {
						[attrKey]: {
							type: 'boolean',
							default: false,
						},
					}),
				});
			}
		);

		addFilter(
			'editor.BlockListBlock',
			`subtle/image-block-${attrKey}-class`,
			(BlockListBlock) => {
				return ({ name, attributes, className, ...props }) => {
					if (name !== BLOCK_NAME || !attributes?.[attrKey]) {
						return el(BlockListBlock, { name, attributes, className, ...props });
					}

					return el(BlockListBlock, {
						...props,
						name,
						attributes,
						className: className ? `${className} ${cssClass}` : cssClass,
					});
				};
			}
		);
	});

	const withResponsiveSizeControl = createHigherOrderComponent((BlockEdit) => {
		return ({ name, attributes, setAttributes, ...props }) => {
			if (name !== BLOCK_NAME) {
				return el(BlockEdit, { name, attributes, setAttributes, ...props });
			}

			return el(
				Fragment,
				null,
				el(BlockEdit, { name, attributes, setAttributes, ...props }),
				el(
					InspectorControls,
					{ group: 'settings' },
					el(
						PanelBody,
						{ title: 'Responsive size', initialOpen: true, order: 10 },
						imageAttributes.map(({ attrKey, label }) =>
							el(ToggleControl, {
								key: attrKey,
								label,
								checked: attributes[attrKey],
								onChange: (value) => setAttributes({ [attrKey]: value }),
							})
						)
					)
				)
			);
		};
	}, 'withResponsiveSizeControl');

	addFilter('editor.BlockEdit', 'subtle/image-block-options', withResponsiveSizeControl);
})();

