#Stripe

Stripe payments extension for Symphony CMS. This extension allows direct access to Stripe's API using Symphony CMS concepts; no PHP is necessary.

##Usage

The Symphony CMS Stripe Extension uses filters at its heart to carry out Stripe API calls.

In order to use this extension it will be important to become familiar with the required arguments and responses of each filter. If you want response data saved from the Stripe response into a section, that section needs to have the filter and the section's field handles must match Stripe's response (`_` are automatically converted into `-`, and vice a versa). This allows the greatest flexibility allowing the Symphony CMS developer to choose what information they want to save in the section.

Use the Stripe: Webhooks Router event in combination with events that have a webhook filter.

##Filters
Interacting with your Stripe account is done by applying filters to events. Only apply a single Stripe filter to an event. You could try doing more than one filter but it's not something I have considered - mileage may vary.

Filters have required fields. Additional information regarding arguments can be obtained from the individual links below. To pass arguments to the filter to be used in the Stripe API call, prefixed names with `stripe`. For instance:

    <input type="hidden" value="cus_0ZZPeHkzl4MacV" name="stripe[id]" />

In the above example, the filter will pass this field value as an 'id` argument. If the Stripe API call is successful, the filter will include any response from Stripe and add it to the event's fields array prior to saving into the Symphony section.

####Create Customer

<https://stripe.com/docs/api?lang=php#create_customer>

####Update Customer

<https://stripe.com/docs/api?lang=php#update_customer>

####Delete Customer

<https://stripe.com/docs/api?lang=php#delete_customer>

####Update Customer Subscription

<https://stripe.com/docs/api?lang=php#update_subscription>

####Cancel Customer Subscription

<https://stripe.com/docs/api?lang=php#cancel_subscription>

####Delete Customer Discount

<https://stripe.com/docs/api?lang=php#delete_discount>

####Create Charge

<https://stripe.com/docs/api?lang=php#create_charge>

####Refund Charge

<https://stripe.com/docs/api?lang=php#refund_charge>

####Create Invoice Item

<https://stripe.com/docs/api?lang=php#create_invoiceitem>

####Create Invoice

<https://stripe.com/docs/api?lang=php#create_invoice>

####Update Invoice Item

<https://stripe.com/docs/api?lang=php#update_invoiceitem>

####Update Invoice

<https://stripe.com/docs/api?lang=php#update_invoice>

####Delete Invoice Item

<https://stripe.com/docs/api?lang=php#delete_invoiceitem>

####Pay Invoice

<https://stripe.com/docs/api?lang=php#pay_invoice>

###Webhook Filters

Webhook Filters must be accompanied by the **Stripe: Webhook Router** event for them to be executed.

####Stripe Webhook: Customer

<https://stripe.com/docs/api?lang=php#customers>

####Stripe Webhook: Charge

<https://stripe.com/docs/api?lang=php#charges>

####Stripe Webhook: Invoice

<https://stripe.com/docs/api?lang=php#invoices>

####Stripe Webhook: Invoice Item

<https://stripe.com/docs/api?lang=php#invoiceitems>

####Stripe Webhook: Transfer

<https://stripe.com/docs/api?lang=php#transfers>
