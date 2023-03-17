// WordPress Coding Standards
// https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/#property-ordering

const content = [ 'content' ];

const display = [
	'display',
	'flex',
	'flex-grow',
	'flex-shrink',
	'flex-basis',
	'flex-direction',
	'flex-wrap',
	'align-items',
	'justify-content',
	'order',
	'gap',
	'grid-template-columns',
	'grid-template-rows',
	'grid-template-areas',
	'grid-auto-columns',
	'grid-auto-rows',
	'grid-auto-flow',
	'grid-gap',
	'grid-column-gap',
	'grid-row-gap',
	'grid-column',
	'grid-area',
	'grid-column-start',
	'grid-row-start',
	'grid-column-end',
	'grid-row-end',
];

const position = [ 'position', 'top', 'right', 'bottom', 'left', 'z-index' ];

const boxModel = [
	'box-sizing',
	'width',
	'min-width',
	'max-width',
	'height',
	'min-height',
	'max-height',
	'border',
	'border-style',
	'border-width',
	'border-color',
	'border-top',
	'border-top-style',
	'border-top-width',
	'border-top-color',
	'border-bottom',
	'border-bottom-style',
	'border-bottom-width',
	'border-bottom-color',
	'border-right',
	'border-right-style',
	'border-right-width',
	'border-right-color',
	'border-left',
	'border-left-style',
	'border-left-width',
	'border-left-color',
	'outline',
	'outline-color',
	'outline-style',
	'outline-width',
	'border-radius',
	'border-top-right-radius',
	'border-top-left-radius',
	'border-bottom-right-radius',
	'border-bottom-left-radius',
	'padding',
	'padding-top',
	'padding-bottom',
	'padding-left',
	'padding-right',
	'margin',
	'margin-top',
	'margin-bottom',
	'margin-left',
	'margin-right',
];

const colors = [
	'background',
	'background-image',
	'background-size',
	'background-position',
	'background-repeat',
	'background-origin',
	'background-clip',
	'background-attachment',
	'background-color',
	'color',
];

const typography = [
	'font-family',
	'font-style',
	'font-variant',
	'font-stretch',
	'font-size',
	'font-weight',
	'line-height',
	'text-align',
	'text-decoration',
	'text-decoration-color',
	'text-decoration-style',
	'text-decoration-line',
	'text-transform',
	'letter-spacing',
];

const nativeStyle = [
	'list-style',
	'list-style-type',
	'list-style-position',
	'list-style-image',
	'appearance',
];

const mask = [
	'mask',
	'mask-image',
	'mask-mode',
	'mask-position',
	'mask-size',
	'mask-repeat',
	'mask-origin',
	'mask-clip',
	'mask-composite',
];

const transition = [
	'transition',
	'transition-delay',
	'transition-duration',
	'transition-property',
	'transition-timing-function',
];
const animation = [
	'animation',
	'animation-name',
	'animation-duration',
	'animation-timing-function',
	'animation-delay',
	'animation-iteration-count',
	'animation-direction',
	'animation-fill-mode',
	'animation-play-state',
];

const other = [ 'box-shadow', 'opacity', 'transform', 'cursor', 'float' ];

module.exports = {
	extends: [
		'@wordpress/stylelint-config',
		'stylelint-config-recommended-less',
	],
	plugins: [ 'stylelint-order', 'stylelint-less' ],
	customSyntax: 'postcss-less',
	rules: {
		'function-url-quotes': null,
		'no-invalid-position-at-import-rule': null,
		'order/order': [ 'custom-properties', 'declarations' ],
		'order/properties-order': [
			...content,
			...display,
			...position,
			...boxModel,
			...colors,
			...typography,
			...nativeStyle,
			...mask,
			...transition,
			...animation,
			...other,
		],
	},
};
