<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/stripe/lib/api/lib/Stripe.php');

class contentExtensionStripeCoupons extends AdministrationPage {

    public function __construct() {
        parent::__construct();
    }

    public function build($context) {
        parent::build($context);

        // Uncomment these lines to add scripts and stylesheets to this page:
        // $this->addStylesheetToHead(URL.'/extensions//assets/...', 'screen');
        $this->addScriptToHead(URL . '/extensions/stripe/assets/stripe.content.coupons.js');
    }

    public function __viewIndex() {
        $this->setTitle('Stripe Coupons');
        $this->setPageType('table');
        $this->appendSubheading('Stripe Coupons', Widget::Anchor('Create New', URL . '/symphony/extension/stripe/coupons/new/', 'Create a new entry', 'create button'));

        $tableHead = array(
            array('ID', 'col'),
            array('Duration', 'col'),
            array('Amount Off', 'col'),
            array('Max Redemptions', 'col')
        );

        // Retrieve all Stripe coupons
        Stripe::setApiKey(Stripe_General::getApiKey());
        $coupons = Stripe_Coupon::all();
        $coupons = $coupons->data;

        if(empty($coupons)) {
            $tableBody = array(Widget::TableRow(array(Widget::TableData('None found.', 'inactive', null, count($tableHead)))));
        } else {
            $tableData = array();
            foreach($coupons as $coupon) {
                $tableData[] = Widget::TableData(Widget::Anchor(General::limitWords($coupon->id), Administration::instance()->getCurrentPageURL() . 'edit/' . $coupon['id'], $coupon->id, 'content'));
                $tableData[] = Widget::TableData(General::limitWords(ucfirst($coupon->duration)) . ($coupon->duration == 'repeating' ? ' (' . $coupon->duration_in_months . ' Months) ' : ''));
                $tableData[] = Widget::TableData(General::limitWords(($coupon->amount_off != '' ? Stripe_General::centsToDollars($coupon->amount_off) : $coupon->percent_off . '%')));
                $tableData[] = Widget::TableData(General::limitWords($coupon->max_redemptions));

                $tableData[0]->appendChild(Widget::Input('items[' . $coupon->id . ']', $coupon->id, 'checkbox'));


                $tableBody[] = Widget::TableRow($tableData);

                unset($tableData);
            }
        }

        $table = Widget::Table(Widget::TableHead($tableHead), null, Widget::TableBody($tableBody), 'selectable');

        $this->Form->appendChild($table);

        $tableActions = new XMLElement('div');
        $tableActions->setAttribute('class', 'actions');

        $options = array(
            0 => array(null, false, __('With Selected...')),
            2 => array('delete', false, __('Delete'), 'confirm'),
        );

        $tableActions->appendChild(Widget::Apply($options));
        $this->Form->appendChild($tableActions);    }

    public function __actionIndex() {
        $checked = @array_keys($_POST['items']);

        if(is_array($checked) && !empty($checked))
        {
            if($_POST['with-selected'] == 'delete')
            {
                foreach($checked as $id)
                {
                    Stripe::setApiKey(Stripe_General::getApiKey());
                    $coupon = Stripe_Coupon::retrieve($id);
                    $coupon->delete();
                }

                redirect($_SERVER['REQUEST_URI']);
            }
        }

        // Redirect back to the page:
        redirect($_SERVER['REQUEST_URI']);
    }

    public function __viewEdit() {
        // Append any Page Alerts from the form's
        if(isset($this->_context[2])){
            switch($this->_context[2]){
                case 'created':
                    $this->pageAlert(
                        __(
                            'Coupon created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Coupons</a>',
                            array(
                                DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
                                Stripe_General::contentUrl() . 'coupons/new/',
                                Stripe_General::contentUrl() . 'coupons/',
                            )
                        ),
                        Alert::SUCCESS);
                    break;
            }
        }

        if(isset($this->_context[1])) {
            Stripe::setApiKey(Stripe_General::getApiKey());
            try {
                $coupon = Stripe_Coupon::retrieve($this->_context[1]);
                $fields = array(
                    'id' => $coupon->id,
                    'duration_in_months' => $coupon->duration_in_months,
                    'redeem_by' => $coupon->redeem_by,
                    'times_redeemed' => $coupon->times_redeemed,
                    'percent_off' => $coupon->percent_off,
                    'livemode' => $coupon->livemode,
                    'duration' => $coupon->duration,
                    'amount_off' => $coupon->amount_off,
                    'currency' => $coupon->currency,
                    'max_redemptions' => $coupon->max_redemptions
                );

            } catch(Stripe_Error $e) {
            // Todo
            }
        }
        $this->setTitle('Stripe Coupon ' . $coupon->id);
        $this->setPageType('form');
        $this->appendSubheading($coupon->id);
        $this->insertBreadcrumbs(array(
            Widget::Anchor('Stripe Coupons', Stripe_General::contentUrl() . 'coupons/'),
        ));

        $this->Form->setAttribute('class', 'two columns');

        $primary = new XMLElement('fieldset');
        $primary->setAttribute('class', 'primary column');

        $id = Widget::Label('ID');
        $id->appendChild(Widget::Input('fields[id]', $fields['id'], 'text', array('disabled' => 'disabled')));
        $primary->appendChild($id);

        $secondary = new XMLElement('fieldset');
        $secondary->setAttribute('class', 'secondary column');

        // Repeating and duration in months
        $picker = new XMLElement('div');
        $picker->setAttribute('id', 'duration');

        // Duration options
        $options = array(
            array('forever', ($fields['duration'] == 'forever'), 'Forever'),
            array('once', ($fields['duration'] == 'once'), 'Once'),
            array('repeating', ($fields['duration'] == 'repeating'), 'Repeating')
        );

        $duration = Widget::Label('Duration');
        $select_duration = Widget::Select('fields[duration]', $options, array('disabled' => 'disabled'));
        $select_duration->setAttribute('class', 'picker');
        $duration->appendChild($select_duration);
        $picker->appendChild($duration);

        $pickable = new XMLElement('div');
        $pickable->setAttribute('class', 'pickable');
        $pickable->setAttribute('id', 'repeating');

        $duration_in_months = Widget::Label('Duration in Months');
        $duration_in_months->appendChild(Widget::Input('fields[duration_in_months]', (string)$fields['duration_in_months'], 'text', array('disabled' => 'disabled')));
        $pickable->appendChild($duration_in_months);
        $picker->appendChild($pickable);
        $secondary->appendChild($picker);

        // Repeating and duration in months
        $picker = new XMLElement('div');
        $picker->setAttribute('id', 'type');

        // Duration options
        $options = array(
            array('amount_off', false, 'Fixed Amount'),
            array('percent_off', false, 'Percentage')
        );

        $type = Widget::Label('Type');
        $select_type = Widget::Select('dummy', $options, array('disabled' => 'disabled'));
        $select_type->setAttribute('class', 'picker');
        $type->appendChild($select_type);
        $picker->appendChild($type);

        $pickable = new XMLElement('div');
        $pickable->setAttribute('class', 'pickable');
        $pickable->setAttribute('id', 'percent_off');

        $percent_off = Widget::Label('Percent Off');
        $percent_off->appendChild(Widget::Input('fields[percent_off]', (string)$fields['percent_off'], 'text', array('disabled' => 'disabled')));
        $pickable->appendChild($percent_off);
        $picker->appendChild($pickable);

        $pickable = new XMLElement('div');
        $pickable->setAttribute('class', 'pickable');
        $pickable->setAttribute('id', 'amount_off');

        $amount_off = Widget::Label('Amount Off');
        $amount_off->appendChild(Widget::Input('fields[amount_off]', (string)$fields['amount_off'], 'text', array('disabled' => 'disabled')));
        $pickable->appendChild($amount_off);
        $picker->appendChild($pickable);

        $secondary->appendChild($picker);

        $max_redemptions = Widget::Label('Max Redemptions');
        $max_redemptions->appendChild(Widget::Input('fields[max_redemptions]', (string)$fields['max_redemptions'], 'text', array('disabled' => 'disabled')));
        $primary->appendChild($max_redemptions);

        $redeem_by = Widget::Label('Redeem By (UTC format)');
        $redeem_by->appendChild(Widget::Input('fields[redeem_by]', (string)$fields['redeem_by'], 'text', array('disabled' => 'disabled')));
        $primary->appendChild($redeem_by);

        // Currency options
        $options = array(
            array('usd', ($fields['currency'] == 'usd'), 'USD'),
        );

        $currency = Widget::Label('Currency');
        $currency->appendChild(Widget::Select('fields[currency]', $options, array('disabled' => 'disabled')));
        $secondary->appendChild($currency);

        $this->Form->appendChild($primary);
        $this->Form->appendChild($secondary);
    }

    public function __viewNew() {
        if(isset($_POST['fields'])) {
            $fields = $_POST['fields'];
        }

        $this->setTitle('Add a Stripe Coupon');
        $this->setPageType('form');
        $this->appendSubheading('Untitled');
        $this->insertBreadcrumbs(array(
            Widget::Anchor('Stripe Coupons', Stripe_General::contentUrl() . 'coupons/'),
        ));

        $this->Form->setAttribute('class', 'two columns');

        $primary = new XMLElement('fieldset');
        $primary->setAttribute('class', 'primary column');

        $id = Widget::Label('ID');
        $id->appendChild(Widget::Input('fields[id]', $fields['id'], 'text'));
        $primary->appendChild($id);

        $secondary = new XMLElement('fieldset');
        $secondary->setAttribute('class', 'secondary column');

        // Repeating and duration in months
        $picker = new XMLElement('div');
        $picker->setAttribute('id', 'duration');

        // Duration options
        $options = array(
            array('forever', ($fields['duration'] == 'forever'), 'Forever'),
            array('once', ($fields['duration'] == 'once'), 'Once'),
            array('repeating', ($fields['duration'] == 'repeating'), 'Repeating')
        );

        $duration = Widget::Label('Duration');
        $select_duration = Widget::Select('fields[duration]', $options);
        $select_duration->setAttribute('class', 'picker');
        $duration->appendChild($select_duration);
        $picker->appendChild($duration);

        $pickable = new XMLElement('div');
        $pickable->setAttribute('class', 'pickable');
        $pickable->setAttribute('id', 'repeating');

        $duration_in_months = Widget::Label('Duration in Months');
        $duration_in_months->appendChild(Widget::Input('fields[duration_in_months]', (string)$fields['duration_in_months'], 'text'));
        $pickable->appendChild($duration_in_months);
        $picker->appendChild($pickable);
        $secondary->appendChild($picker);

        // Repeating and duration in months
        $picker = new XMLElement('div');
        $picker->setAttribute('id', 'type');

        // Duration options
        $options = array(
            array('amount_off', false, 'Fixed Amount'),
            array('percent_off', false, 'Percentage')
        );

        $type = Widget::Label('Type');
        $select_type = Widget::Select('dummy', $options);
        $select_type->setAttribute('class', 'picker');
        $type->appendChild($select_type);
        $picker->appendChild($type);

        $pickable = new XMLElement('div');
        $pickable->setAttribute('class', 'pickable');
        $pickable->setAttribute('id', 'percent_off');

        $percent_off = Widget::Label('Percent Off');
        $percent_off->appendChild(Widget::Input('fields[percent_off]', (string)$fields['percent_off'], 'text'));
        $pickable->appendChild($percent_off);
        $picker->appendChild($pickable);

        $pickable = new XMLElement('div');
        $pickable->setAttribute('class', 'pickable');
        $pickable->setAttribute('id', 'amount_off');

        $amount_off = Widget::Label('Amount Off');
        $amount_off->appendChild(Widget::Input('fields[amount_off]', (string)$fields['amount_off'], 'text'));
        $pickable->appendChild($amount_off);
        $picker->appendChild($pickable);

        $secondary->appendChild($picker);

        $max_redemptions = Widget::Label('Max Redemptions');
        $max_redemptions->appendChild(Widget::Input('fields[max_redemptions]', (string)$fields['max_redemptions'], 'text'));
        $primary->appendChild($max_redemptions);

        $redeem_by = Widget::Label('Redeem By (UTC format)');
        $redeem_by->appendChild(Widget::Input('fields[redeem_by]', (string)$fields['redeem_by'], 'text'));
        $primary->appendChild($redeem_by);

        // Currency options
        $options = array(
            array('usd', ($fields['currency'] == 'usd'), 'USD'),
        );

        $currency = Widget::Label('Currency');
        $currency->appendChild(Widget::Select('fields[currency]', $options));
        $secondary->appendChild($currency);

        $this->Form->appendChild($primary);
        $this->Form->appendChild($secondary);

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input('action[save]', 'Create Coupon', 'submit', array('accesskey' => 's')));

        $this->Form->appendChild($div);
    }

    public function __actionEdit() {
        return $this->__actionNew();
    }

    public function __actionNew() {
        if(array_key_exists('save', $_POST['action'])) {
            $isNew = ($this->_context[0] !== "edit");
            $fields = $_POST['fields'];

            Stripe::setApiKey(Stripe_General::getApiKey());

            if($isNew) {
                try {
                    if($fields['amount_off'] != '' && $fields['percent_off'] == '') {
                        // Expect dollars and convert for Stripe
                        unset($fields['percent_off']);
                        $fields['amount_off'] = Stripe_General::dollarsToCents($fields['amount_off']);
                    }

                    if($fields['amount_off'] == '' && $fields['percent_off'] != '') {
                        // Expect dollars and convert for Stripe
                        unset($fields['amount_off']);
                    }

                    if($fields['redeem_by'] == '') {
                        unset($fields['redeem_by']);
                    } else {
                        $fields['redeem_by'] = strtotime($fields['redeem_by']);
                    }

                    if($fields['duration'] != 'repeating') {
                        unset($fields['duration_in_months']);
                    }

                    Stripe_Coupon::create($fields);
                    redirect(Stripe_General::contentUrl() . 'coupons/edit/' . $fields['id'] . '/created/');
                } catch(Stripe_Error $e) {
                    $body = $e->getJsonBody();
                    $err = $body['error'];
                    return $this->pageAlert(
                        __('Error encountered.  ') . $err['message'], Alert::ERROR
                    );
                }
            } else {
                try {
                    $coupon = Stripe_Coupon::retrieve($fields['id']);
                    $coupon->name = $fields['name'];
                    redirect(Stripe_General::contentUrl() . 'coupons/edit/' . $fields['id'] . '/created/');
                } catch(Stripe_Error $e) {
                    $body = $e->getJsonBody();
                    $err = $body['error'];
                    return $this->pageAlert(
                        __('Error encountered.  ') . $err['message'], Alert::ERROR
                    );
                }
            }
        }
    }
}