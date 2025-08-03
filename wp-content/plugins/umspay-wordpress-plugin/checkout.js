const umspay_data = window.wc.wcSettings.getSetting( 'umspay_data', {} );
const umspay_label = window.wp.htmlEntities.decodeEntities( umspay_data.title )
	|| window.wp.i18n.__( 'UmsPay', 'umspay' );
const umspay_content = ( umspay_data ) => {
	return window.wp.htmlEntities.decodeEntities( umspay_data.description || '' );
};
const UmsPay = {
	name: 'umspay',
	label: umspay_label,
	content: Object( window.wp.element.createElement )( umspay_content, null ),
	edit: Object( window.wp.element.createElement )( umspay_content, null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'umspay' ),
	ariaLabel: umspay_label,
	supports: {
		features: umspay_data.supports,
	},
};
window.wc.wcBlocksRegistry.registerPaymentMethod( UmsPay );