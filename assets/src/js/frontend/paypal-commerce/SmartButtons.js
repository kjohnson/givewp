/* globals paypal, Give, FormData, givePayPalCommerce */
import DonationForm from './DonationForm';
import PaymentMethod from './PaymentMethod';

/**
 * PayPal Smart Buttons.
 */
class SmartButtons extends PaymentMethod {
	/**
	 * Get smart button container.
	 *
	 * @since 2.9.0
	 *
	 * @return {object} Smart button container selector.
	 */
	getButtonContainer() {
		const ccFields = this.form.querySelector( '[id^="give_cc_fields-"]' );
		const smartButtonWrap = document.createElement( 'div' );
		const buttonDiv = document.createElement( 'div' );

		buttonDiv.setAttribute( 'id', `smart-buttons-${ this.form.getAttribute( 'data-id' ) }` );
		smartButtonWrap.setAttribute( 'id', '#give-paypal-commerce-smart-buttons-wrap' );

		smartButtonWrap.appendChild( buttonDiv );

		return ccFields.insertBefore( smartButtonWrap, ccFields.querySelector( '[id^=give-card-number-wrap-]' ) );
	}

	/**
	 * Render smart buttons.
	 *
	 * @since 2.9.0
	 */
	renderPaymentMethodOption() {
		this.smartButtonContainer = this.getButtonContainer();

		if ( ! this.smartButtonContainer ) {
			return;
		}

		const onInitHandler = this.onInitHandler.bind( this );
		const onClickHandler = this.onClickHandler.bind( this );
		const createOrderHandler = this.createOrderHandler.bind( this );
		const onApproveHandler = this.onApproveHandler.bind( this );

		paypal.Buttons( {
			onInit: onInitHandler,
			onClick: onClickHandler,
			createOrder: createOrderHandler,
			onApprove: onApproveHandler,
			style: {
				layout: 'vertical',
				size: 'responsive',
				shape: 'rect',
				label: 'paypal',
				color: 'gold',
				tagline: false,
			},
		} ).render( this.smartButtonContainer );
	}

	/**
	 * On init event handler for smart buttons.
	 *
	 * @since 2.9.0
	 *
	 * @param {object} data PayPal button data.
	 * @param {object} actions PayPal button actions.
	 */
	onInitHandler( data, actions ) { // eslint-disable-line
		// Keeping this for future reference.
	}

	/**
	 * On click event handler for smart buttons.
	 *
	 * @since 2.9.0
	 *
	 * @param {object} data PayPal button data.
	 * @param {object} actions PayPal button actions.
	 *
	 * @return {Promise<unknown>} Return wther or not open PayPal checkout window.
	 */
	async onClickHandler(data, actions) { // eslint-disable-line
		const formData = new FormData( this.form );

		formData.delete( 'card_name' );
		this.resetCreditCardFields();

		if ( ! Give.form.fn.isDonationFormHtml5Valid( this.form, true ) ) {
			return actions.reject();
		}

		Give.form.fn.removeErrors( this.jQueryForm );
		const result = await Give.form.fn.isDonorFilledValidData( this.form, formData );

		if ( 'success' === result ) {
			if ( DonationForm.isRecurringDonation( ) ) {
				this.submitDonationForm();

				return actions.reject();
			}

			return actions.resolve();
		}

		Give.form.fn.addErrors( this.jQueryForm, result );
		return actions.reject();
	}

	/**
	 * Create order event handler for smart buttons.
	 *
	 * @since 2.9.0
	 *
	 * @param {object} data PayPal button data.
	 * @param {object} actions PayPal button actions.
	 *
	 * @return {Promise<unknown>} Return PayPal order id.
	 */
	async createOrderHandler(data, actions) { // eslint-disable-line
		Give.form.fn.removeErrors( this.jQueryForm );

		// eslint-disable-next-line
		const response = await fetch(`${this.ajaxurl}?action=give_paypal_commerce_create_order`, {
			method: 'POST',
			body: DonationForm.getFormDataWithoutGiveActionField( this.form ),
		} );
		const responseJson = await response.json();
		let errorDetail = {};

		if ( ! responseJson.success ) {
			if ( null === responseJson.data.error ) {
				DonationForm.addErrors( this.jQueryForm, Give.form.fn.getErrorHTML( [ { message: givePayPalCommerce.defaultDonationCreationError } ] ) );
				return null;
			}

			errorDetail = responseJson.data.error.details[ 0 ];
			DonationForm.addErrors( this.jQueryForm, Give.form.fn.getErrorHTML( [ { message: errorDetail.description } ] ) );

			return null;
		}

		return responseJson.data.id;
	}

	/**
	 * On approve event handler for smart buttons.
	 *
	 * @since 2.9.0
	 *
	 * @param {object} data PayPal button data.
	 * @param {object} actions PayPal button actions.
	 *
	 * @return {*} Return whether or not PayPal payment captured.
	 */
	async onApproveHandler( data, actions ) {
		Give.form.fn.showProcessingState();
		Give.form.fn.disable( this.jQueryForm, true );
		Give.form.fn.removeErrors( this.jQueryForm );

		// eslint-disable-next-line
		const response = await fetch( `${ this.ajaxurl }?action=give_paypal_commerce_approve_order&order=` + data.orderID, {
			method: 'post',
			body: DonationForm.getFormDataWithoutGiveActionField( this.form ),
		} );
		const responseJson = await response.json();

		// Three cases to handle:
		//   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
		//   (2) Other non-recoverable errors -> Show a failure message
		//   (3) Successful transaction -> Show a success / thank you message

		let errorDetail = {};
		if ( ! responseJson.success ) {
			Give.form.fn.disable( this.jQueryForm, false );
			Give.form.fn.hideProcessingState();

			if ( null === responseJson.data.error ) {
				DonationForm.addErrors( this.jQueryForm, Give.form.fn.getErrorHTML( [ { message: givePayPalCommerce.defaultDonationCreationError } ] ) );
				return;
			}

			errorDetail = responseJson.data.error.details[ 0 ];
			if ( errorDetail && errorDetail.issue === 'INSTRUMENT_DECLINED' ) {
				Give.form.fn.hideProcessingState();
				Give.form.fn.disable( this.jQueryForm, false );

				// Recoverable state, see: "Handle Funding Failures"
				// https://developer.paypal.com/docs/checkout/integration-features/funding-failure/
				return actions.restart();
			}

			DonationForm.addErrors( this.jQueryForm, Give.form.fn.getErrorHTML( [ { message: errorDetail.description } ] ) );
			return;
		}

		const orderData = responseJson.data.order;
		await DonationForm.attachOrderIdToForm( this.form, orderData.id );

		this.submitDonationForm();
	}

	/**
	 * Submit donation form.
	 *
	 * @since 2.9.0
	 */
	submitDonationForm() {
		// Do not submit  empty or filled Name credit card field with form.
		// If we do that we will get `empty_card_name` error or other.
		// We are removing this field before form submission because this donation processed with smart button.
		this.jQueryForm.off( 'submit' );
		this.removeCreditCardFields();
		this.form.submit();
	}

	/**
	 * Reset Card fields.
	 *
	 * @since 2.9.0
	 */
	resetCreditCardFields() {
		this.jQueryForm.find( 'input[name="card_name"]' ).val( '' );
	}

	/**
	 * Remove Card fields.
	 *
	 * @since 2.9.0
	 */
	removeCreditCardFields() {
		this.jQueryForm.find( 'input[name="card_name"]' ).remove();
	}
}

export default SmartButtons;
