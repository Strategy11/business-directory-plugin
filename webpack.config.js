/**
 * External dependencies
 */
const path = require( 'path' );

// Webpack configuration
const config = {
	mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
	devtool: process.env.NODE_ENV !== 'production' ? 'source-map' : undefined,
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [ `${ __dirname }/js`, 'node_modules' ],
	},
	entry: {
		'onboarding-wizard': './assets/js/onboarding-wizard/index.js',
		'admin-csv-import': './assets/js/admin-csv-import.js',
	},
	output: {
		filename: '[name].min.js',
		path: path.resolve( __dirname, 'assets/js' ),
	},
	module: {
		rules: [
			{
				test: /.js$/,
				exclude: /node_modules/,
				include: /js/,
				use: [
					{
						loader: 'babel-loader',
					},
				],
			},
			{
				test: /\.svg$/,
				use: [ '@svgr/webpack' ],
			},
			{
				test: /\.css$/i,
				use: [ { loader: 'style-loader' }, 'css-loader' ],
			},
		],
	},
	externals: {
		jquery: 'jQuery',
		$: 'jQuery',
	},
};

module.exports = config;
