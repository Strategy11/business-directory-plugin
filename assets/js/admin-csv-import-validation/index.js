import domReady from '@wordpress/dom-ready';
import initializeCsvImportValidation from './initializeCsvImportValidation';

domReady(() => {
    initializeCsvImportValidation();
});
