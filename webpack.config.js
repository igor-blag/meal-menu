const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(__dirname, 'blocks/meal-calendar/src/index.js'),
	},
	output: {
		path: path.resolve(__dirname, 'blocks/meal-calendar/build'),
		filename: '[name].js',
	},
};
