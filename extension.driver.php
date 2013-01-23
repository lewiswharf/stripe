<?php

require_once(EXTENSIONS . '/stripe/lib/class.stripegeneral.php');

class Extension_Stripe extends Extension {

    /*-------------------------------------------------------------------------
        Delegates:
    -------------------------------------------------------------------------*/

    public function getSubscribedDelegates() {
        return array(
            array(
                'page' => '/blueprints/events/',
                'delegate' => 'EventPreEdit',
                'callback' => 'actionEventPreEdit'
            ),
            array(
                'page' => '/blueprints/events/new/',
                'delegate' => 'AppendEventFilter',
                'callback' => 'actionAppendEventFilter'
            ),
            array(
                'page' => '/blueprints/events/edit/',
                'delegate' => 'AppendEventFilter',
                'callback' => 'actionAppendEventFilter'
            ),
            array(
                'page' => '/blueprints/events/',
                'delegate' => 'AppendEventFilterDocumentation',
                'callback' => 'actionAppendEventFilterDocumentation'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'EventPreSaveFilter',
                'callback' => 'actionEventPreSaveFilter'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'EventPostSaveFilter',
                'callback' => 'actionEventPostSaveFilter'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'actionAddCustomPreferenceFieldsets'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => 'actionSave'
            )
        );
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function actionEventPreEdit($context) {
        // Your code goes here...
    }

    public function actionAppendEventFilter($context) {
        $filters = Stripe_General::getAllFilters();

        foreach ($filters as $key => $val) {
            if (is_array($context['selected'])) {
                $selected = in_array($key, $context['selected']);
                $context['options'][] = array($key, $selected, $val);
            }
        }
    }

    public function actionAppendEventFilterDocumentation($context) {
        // Todo not firing
        var_dump($context);
    }

    public function actionEventPreSaveFilter($context) {

        if(!isset($_SESSION['symphony-stripe'])) {
            require_once(EXTENSIONS . '/stripe/lib/api/lib/Stripe.php');
            Stripe::setApiKey(Stripe_General::getApiKey());

            $filters = $context['event']->eParamFILTERS;
            $fields = $_POST['stripe'];

            // Convert handles if Symphony standard
            foreach ($fields as $key => $val) {
                $key = str_replace('-', '_', $key);
                $fields[$key] = $val;
            }

            foreach ($filters as $key => $val) {
                if (in_array($val, array_keys(Stripe_General::getAllFilters()))) {

                    try {
                        switch($val) {
                            case 'Stripe_Customer-create':
                                $stripe = Stripe_Customer::create($fields);
                                break;
                            case 'Stripe_Customer-retrieve-update':
                                $stripe = Stripe_Customer::retrieve($fields['id']);
                                $stripe = Stripe_General::setStripeFieldsToUpdate($stripe, $fields);
                                $stripe = $stripe->save();
                                break;
                            case 'Stripe_Customer-retrieve-delete':
                                $stripe = Stripe_Customer::retrieve($fields['id']);
                                $stripe = $stripe->delete();
                                break;
                            case 'Stripe_Customer-retrieve-updateSubscription':
                                $stripe = Stripe_Customer::retrieve($fields['id']);
                                unset($fields['id']);
                                $stripe = $stripe->updateSubscription($fields);
                                break;
                            case 'Stripe_Customer-retrieve-cancelSubscription':
                                $stripe = Stripe_Customer::retrieve($fields['id']);
                                $stripe = $stripe->cancelSubscription();
                                break;
                            case 'Stripe_Customer-retrieve-deleteDiscount':
                                $stripe = Stripe_Customer::retrieve($fields['id']);
                                $stripe = $stripe->deleteDiscount();
                                break;
                            case 'Stripe_Charge-create':
                                $stripe = Stripe_Charge::create($fields);
                                break;
                            case 'Stripe_Charge-retrieve-refund':
                                $stripe = Stripe_Charge::retrieve($fields['id']);
                                $stripe = $stripe->refund();
                                break;
                            case 'Stripe_InvoiceItem-create':
                                $stripe = Stripe_InvoiceItem::create($fields);
                                break;
                            case 'Stripe_Invoice-create':
                                $stripe = Stripe_Invoice::create($fields);
                                break;
                            case 'Stripe_InvoiceItem-retrieve-update':
                                $stripe = Stripe_InvoiceItem::retrieve($fields['id']);
                                unset($fields['id']);
                                $stripe = Stripe_General::setStripeFieldsToUpdate($stripe, $fields);
                                $stripe = $stripe->save();
                                break;
                            case 'Stripe_Invoice-retrieve-closed':
                                $stripe = Stripe_Invoice::retrieve($fields['id']);
                                $stripe->closed;
                                $stripe = $stripe->save();
                                break;
                            case 'Stripe_InvoiceItem-retrieve-delete':
                                $stripe = Stripe_InvoiceItem::retrieve($fields['id']);
                                $stripe = $stripe->delete();
                                break;
                            case 'Stripe_Invoice-retrieve-pay':
                                $stripe = Stripe_Invoice::retrieve($fields['id']);
                                $stripe = $stripe->pay();
                                break;
                        }
                    } catch (Stripe_InvalidRequestError $e) {
                        // Invalid parameters were supplied to Stripe's API
                        $context['messages'][] = array('stripe', false, $e->getMessage());
                        Stripe_General::emailPrimaryDeveloper($e->getMessage());
                    } catch (Stripe_AuthenticationError $e) {
                        // Authentication with Stripe's API failed
                        // (maybe you changed API keys recently)
                        $context['messages'][] = array('stripe', false, $e->getMessage());
                        Stripe_General::emailPrimaryDeveloper($e->getMessage());
                    } catch (Stripe_ApiConnectionError $e) {
                        // Network communication with Stripe failed
                        $context['messages'][] = array('stripe', false, $e->getMessage());
                        Stripe_General::emailPrimaryDeveloper($e->getMessage());
                    } catch (Stripe_Error $e) {
                        // Display a very generic error to the user, and maybe send
                        $context['messages'][] = array('stripe', false, $e->getMessage());
                        Stripe_General::emailPrimaryDeveloper($e->getMessage());
                    } catch (Exception $e) {
                        // Something else happened, completely unrelated to Stripe
                        $context['messages'][] = array('stripe', false, $e->getMessage());
                        Stripe_General::emailPrimaryDeveloper($e->getMessage());
                    }
                }
            }

            // Convert stripe object to array so that it can be looped
            $stripe = $stripe->__toArray();

            // Add stripe response to session in case event fails
            $_SESSION['symphony-stripe'] = serialize($stripe);
        } else {
            $stripe = unserialize($_SESSION['symphony-stripe']);

            // Ensure updated stripe[...] fields replace empty fields
            foreach($stripe as $key => $val) {
                if(empty($val) && isset($_POST['stripe'][$key])) {
                    $stripe[$key] = $_POST['stripe'][$key];
                }
            }
        }

        // Add values of response for Symphony event to process
        $context['fields'] = array_merge(Stripe_General::addStripeFieldsToSymphonyEventFields($stripe), $context['fields']);

        return $context;
    }

    public function actionEventPostSaveFilter($context) {
        // Clear session saved response
        unset($_SESSION['symphony-stripe']);
    }

    public function actionAddCustomPreferenceFieldsets($context) {
        // If the Payment Gateway Interface extension is installed, don't
        // double display the preference, unless this function is called from
        // the `pgi-loader` context.
        if (in_array('pgi_loader', Symphony::ExtensionManager()->listInstalledHandles()) xor isset($context['pgi-loader'])) return;

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Stripe')));

        $div = new XMLElement('div', null);
        $group = new XMLElement('div', null, array('class' => 'group'));

        // Build the Gateway Mode
        $label = new XMLElement('label', __('Stripe Mode'));
        $options = array(
            array('test', Stripe_General::isTestMode(), __('Test')),
            array('live', !Stripe_General::isTestMode(), __('Live'))
        );

        $label->appendChild(Widget::Select('settings[stripe][gateway-mode]', $options));
        $div->appendChild($label);
        $fieldset->appendChild($div);

        // Live Public API Key
        $label = new XMLElement('label', __('Live Secret API Key'));
        $label->appendChild(
            Widget::Input('settings[stripe][live-api-key]', Symphony::Configuration()->get("live-api-key", 'stripe'))
        );
        $group->appendChild($label);

        // Test Public API Key
        $label = new XMLElement('label', __('Test Secret API Key'));
        $label->appendChild(
            Widget::Input('settings[stripe][test-api-key]', Symphony::Configuration()->get("test-api-key", 'stripe'))
        );
        $group->appendChild($label);

        $fieldset->appendChild($group);
        $context['wrapper']->appendChild($fieldset);
    }

    public function actionSave($context) {
        $settings = $context['settings'];

        Symphony::Configuration()->set('test-api-key', $settings['stripe']['test-api-key'], 'stripe');
        Symphony::Configuration()->set('live-api-key', $settings['stripe']['live-api-key'], 'stripe');
        Symphony::Configuration()->set('gateway-mode', $settings['stripe']['gateway-mode'], 'stripe');

        return Symphony::Configuration()->write();
    }

    public function install() {
        // Create stripe_customer_id field database:
        Symphony::Database()->query("
			CREATE TABLE IF NOT EXISTS `tbl_fields_stripe_customer_id` (
             `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
              `field_id` INT(11) unsigned NOT NULL,
              `validator` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `disabled` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
              PRIMARY KEY (`id`),
              KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		");

        // Create stripe_customer_link field database:
        Symphony::Database()->query("
			CREATE TABLE IF NOT EXISTS `tbl_fields_stripe_customer_link` (
              `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
              `field_id` INT(11) unsigned NOT NULL,
         	  `related_field_id` VARCHAR(255) NOT NULL,
              `show_association` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
              `disabled` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
              PRIMARY KEY (`id`),
              KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		");
    }

    public function uninstall() {
        // Drop field tables:
        Symphony::Database()->query("DROP TABLE `tbl_fields_stripe_customer_id`");
        Symphony::Database()->query("DROP TABLE `tbl_fields_stripe_customer_link`");

        // Clean configuration
        Symphony::Configuration()->remove('test-api-key', 'stripe');
        Symphony::Configuration()->remove('live-api-key', 'stripe');
        Symphony::Configuration()->remove('gateway-mode', 'stripe');

        return Symphony::Configuration()->write();
    }

    public function fetchNavigation() {
        return array(
            array(
                'location' => 1000,
                'name' => __('Stripe'),
                'children' => array(
                    array(
                        'name' => __('Plans'),
                        'link' => '/plans/'
                    ),
                    array(
                        'name' => __('Coupons'),
                        'link' => '/coupons/'
                    )
                )
            )
        );
    }
}