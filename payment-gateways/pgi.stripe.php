<?php

	require_once(EXTENSIONS . '/pgi_loader/lib/class.paymentgateway.php');
	require_once(EXTENSIONS . '/stripe/lib/class.stripegeneral.php');
	require_once(EXTENSIONS . '/stripe/api/lib/Stripe.php');

	Class StripePaymentGateway extends PaymentGateway {

		public function about() {
			return array(
				'name' => 'Stripe Payment Gateway',
				'version' => '0.1',
				'release-date' => '2013-01-10'
			);
		}

		/**
		 * Call the default appendPreferences function that the extension would
		 * use if the Payment Gateway Loader extension wasn't installed. Pass a
		 * dummy context to the appendPreferences, so the function will return
		 * the fieldset even though PGL is installed.
		 *
		 * With the resulting Fieldset, we add the relevant pickable classes
		 */
		public function getPreferencesPane() {
			// Call the extensions appendPreferences function
			$context = array(
				'wrapper' => new XMLElement('dummy'),
				'pgi-loader' => true
			);

			Extension_Stripe::actionAddCustomPreferenceFieldsets($context);

			$fieldset = current($context['wrapper']->getChildren());

			if(!is_a($fieldset, 'XMLElement')) return $fieldset;

			$fieldset->setAttribute('class', 'settings pgi-pickable');
			$fieldset->setAttribute('id', 'stripe');

			return $fieldset;
		}

		public static function processTransaction(array $values) {
            self::_setApiKey();
			return Stripe_Charge::create($values);
		}

		public static function refundTransaction(array $values) {
            self::_setApiKey();
			$ch =  Stripe_Charge::retrieve($values['id']);
            return $ch->refund();
		}

        private static function _setApiKey() {
            return Stripe::setApiKey(Stripe_General::getApiKey());
        }

	}