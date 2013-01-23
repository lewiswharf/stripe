<?php

require_once(TOOLKIT . '/class.datasource.php');

Class datasourcecoupons extends DataSource{

    public $dsParamROOTELEMENT = 'coupons';

    public function __construct($env=NULL, $process_params=true){
        parent::__construct($env, $process_params);
        $this->_dependencies = array();
    }

    public function about(){
        return array(
            'name' => 'Stripe: Coupons',
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
        require_once(EXTENSIONS . '/stripe/lib/api/lib/Stripe.php');

        Stripe::setApiKey(Stripe_General::getApiKey());
        $coupons = Stripe_Coupon::all();
        $coupons = $coupons['data'];
        foreach($coupons as $coupon) {
            $entry = new XMLElement('entry');
            $entry->setAttribute('id', $coupon->id);
            $entry->setAttribute('livemode', ($coupon->livemode ? 'true' : 'false'));

            $entry->appendChild(new XMLElement('duration-in-months', $coupon->duration_in_months));
            $entry->appendChild(new XMLElement('redeem-by', $coupon->redeem_by));
            $entry->appendChild(new XMLElement('times-redeemed', $coupon->times_redeemed));
            $entry->appendChild(new XMLElement('percent-off', $coupon->percent_off));
            $entry->appendChild(new XMLElement('duration', $coupon->duration));
            $entry->appendChild(new XMLElement('amount-off', $coupon->amount_off));
            $entry->appendChild(new XMLElement('currency', $coupon->currency));
            $entry->appendChild(new XMLElement('max-redemptions', $coupon->max_redemptions));

            $result->appendChild($entry);
        }

        return $result;
    }

}
