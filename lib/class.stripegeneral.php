<?php

Abstract Class Stripe_General {

    /**
     * Check if Stripe is set to test mode
     * @return bool
     */
    public static function isTestMode() {
        return Symphony::Configuration()->get('gateway-mode', 'stripe') == 'test';
    }

    /**
     * Get API Key based on current mode
     * @return string
     */
    public static function getApiKey() {
        if(self::isTestMode())
            return Symphony::Configuration()->get('test-api-key', 'stripe');
        else
            return Symphony::Configuration()->get('live-api-key', 'stripe');
    }

    public static function getAllFilters() {
        // key = Stripe_Class-staticmethod-method
        return array(
            'Stripe_Customer-create' => 'Stripe: Create Customer',
            'Stripe_Customer-retrieve-update' => 'Stripe: Update Customer',
            'Stripe_Customer-retrieve-delete' => 'Stripe: Delete Customer',
            'Stripe_Customer-retrieve-updateSubscription' => 'Stripe: Update Customer Subscription',
            'Stripe_Customer-retrieve-cancelSubscription' => 'Stripe: Cancel Customer Subscription',
            'Stripe_Customer-retrieve-deleteDiscount' => 'Stripe: Delete Customer Discount',
            'Stripe_Charge-create' => 'Stripe: Create Charge',
            'Stripe_Charge-retrieve-refund' => 'Stripe: Refund Charge',
            'Stripe_InvoiceItem-create' => 'Stripe: Create Invoice Item',
            'Stripe_Invoice-create' => 'Stripe: Create Invoice',
            'Stripe_InvoiceItem-retrieve-update' => 'Stripe: Update Invoice Item',
            'Stripe_Invoice-retrieve-closed' => 'Stripe: Update Invoice',
            'Stripe_InvoiceItem-retrieve-delete' => 'Stripe: Delete Invoice Item',
            'Stripe_Invoice-retrieve-pay' => 'Stripe: Pay Invoice',
            'stripe_customer' => 'Stripe Webhook: Customer',
            'stripe_charge' => 'Stripe Webhook: Charge',
            'stripe_invoice' => 'Stripe Webhook: Invoice',
            'stripe_invoiceitem' => 'Stripe Webhook: Invoice Item',
            'stripe_transfer' => 'Stripe Webhook: Transfer',
        );
    }

    public static function dollarsToCents($dollars) {
        return $dollars * 100;
    }

    public static function centsToDollars($cents) {
        return $cents / 100;
    }

    public static function contentUrl() {
        return SYMPHONY_URL . '/extension/stripe/';
    }

    public static function emailPrimaryDeveloper($message) {
        if($primary = Symphony::Database()->fetchRow(0, "SELECT `first_name`, `last_name`, `email` FROM `tbl_authors` WHERE `primary` = 'yes'")) {
            $email = Email::create();

            $email->sender_name = (EmailGatewayManager::getDefaultGateway() == 'sendmail' ? Symphony::Configuration()->get('from_name', 'email_sendmail') : Symphony::Configuration()->get('from_name', 'email_smtp'));
            $email->sender_email_address = (EmailGatewayManager::getDefaultGateway() == 'sendmail' ? Symphony::Configuration()->get('from_address', 'email_sendmail') : Symphony::Configuration()->get('from_address', 'email_smtp'));

            $email->recipients = $email->setRecipients($primary['email']);
            $email->text_plain = $message;
            $email->subject = 'Stripe Error';

            return $email->send();
        }
    }

    public static function setStripeFieldsToUpdate($object, $fields) {
        foreach ($fields as $key => $val) {
            $object->$key = $val;
        }
        return $object;
    }

    public static function addStripeFieldsToSymphonyEventFields($response) {
        foreach ($response as $key => $val) {
            $key = str_replace('_', '-', $key);
            if (!is_array($val) && !empty($val)) {
                $result[$key] = $val;
            } elseif (!empty($val)) {
                foreach($val as $k => $v) {
                    if(!empty($v)) {
                        $key = str_replace('_', '-', $k);
                        $result[$key . '-' . $k] = $v;
                    }
                }
                self::addStripeFieldsToSymphonyEventFields($result);
            }
        }
        return $result;
    }
}