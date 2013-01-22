<?php

require_once(TOOLKIT . '/class.datasource.php');

Class datasourceplans extends DataSource{

    public $dsParamROOTELEMENT = 'plans';

    public function __construct($env=NULL, $process_params=true){
        parent::__construct($env, $process_params);
        $this->_dependencies = array();
    }

    public function about(){
        return array(
            'name' => 'Stripe: Plans',
            'author' => array(
                'name' => 'Mark Lewis',
                'website' => 'http://www.delewis.com'
            ),
            'release-date' => '2013-01-15'
        );
    }

    public function grab(){

        $result = new XMLElement($this->dsParamROOTELEMENT);

        require_once(EXTENSIONS . '/stripe/lib/class.stripegeneral.php');
        require_once(EXTENSIONS . '/stripe/api/lib/Stripe.php');

        Stripe::setApiKey(Stripe_General::getApiKey());
        $plans = Stripe_Plan::all();
        $plans = $plans['data'];
        foreach($plans as $plan) {
            $entry = new XMLElement('entry');
            $entry->setAttribute('id', $plan->id);
            $entry->setAttribute('livemode', ($plan->livemode ? 'true' : 'false'));

            $entry->appendChild(new XMLElement('trial-period-days', $plan->trial_period_days));
            $entry->appendChild(new XMLElement('interval_count', $plan->interval_count));
            $entry->appendChild(new XMLElement('interval', $plan->interval));
            $entry->appendChild(new XMLElement('amount', Stripe_General::centsToDollars($plan->amount)));
            $entry->appendChild(new XMLElement('name', $plan->name));
            $entry->appendChild(new XMLElement('currency', $plan->currency));

            $result->appendChild($entry);
        }

        return $result;
    }

}
