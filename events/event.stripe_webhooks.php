<?php

	require_once(TOOLKIT . '/class.event.php');
    require_once(EXTENSIONS . '/stripe/lib/class.stripegeneral.php');
    require_once(EXTENSIONS . '/stripe/lib/api/lib/Stripe.php');

	Class eventstripe_webhooks extends SectionEvent{

        public static function about(){
            return array(
                'name' => 'Stripe: Webhooks Router',
                'author' => array(
                    'name' => 'Mark Lewis',
                    'website' => 'http://www.delewis.com',
                    'email' => 'mark@delewis.com'),
                'version' => 'Mark Lewis',
                'release-date' => '2013-01-21'
            );
        }

        public function priority(){
            return self::kHIGH;
        }

        public static function allowEditorToParse(){
            return false;
        }

        public static function documentation(){
            return '
            <p>Attach this event to the page you have instructed Stripe to post its webhooks. This event should be accompanied by Symphony events which have a filter prefixed with "Stripe Webhook".</p>';
        }

        public function load(){
            Stripe::setApiKey(Stripe_General::getApiKey());

            $body = @file_get_contents('php://input');
            $event = json_decode($body, true);

            $type = explode('.', $event['type']);

            $sEvent = $this->__getRoute();

            switch($type[0]) {
                case 'charge':
                    $_POST['fields'] = Stripe_General::addStripeFieldsToSymphonyEventFields($event['data']['object']);
                    $_POST['action'][$sEvent['charge']] = 1;
                    break;
                case 'customer':
                    $_POST['fields'] = Stripe_General::addStripeFieldsToSymphonyEventFields($event['data']['object']);
                    $_POST['action'][$sEvent['customer']] = 1;
                    break;
                case 'invoice':
                    $_POST['fields'] = Stripe_General::addStripeFieldsToSymphonyEventFields($event['data']['object']);
                    $_POST['action'][$sEvent['invoice']] = 1;
                    break;
                case 'invoiceitem':
                    $_POST['fields'] = Stripe_General::addStripeFieldsToSymphonyEventFields($event['data']['object']);
                    $_POST['action'][$sEvent['invoiceitem']] = 1;
                    break;
                case 'transfer':
                    $_POST['fields'] = Stripe_General::addStripeFieldsToSymphonyEventFields($event['data']['object']);
                    $_POST['action'][$sEvent['transfer']] = 1;
                    break;
            }

        }

        private  function __getRoute(){
            $page = Frontend::Page()->resolvePage();
            $events = explode(',', $page['events']);

            $result = array();

            // Get each event's filters
            foreach ($events as $event) {
                if($event != 'stripe_webhooks') {
                    $class = 'event' . $event;
                    $ext = new $class();

                    // Fid Stripe event filter
                    foreach ($ext->eParamFILTERS as $filter) {
                        if(strstr($filter, 'stripe_'))
                            $name = str_replace('stripe_', '', $filter);
                    }
                    $result[$name] = $ext->ROOTELEMENT;
                }
            }
            return $result;
        }
    }
