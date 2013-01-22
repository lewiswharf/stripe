<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/stripe/api/lib/Stripe.php');

class contentExtensionStripePlans extends AdministrationPage {

    public function __construct() {
        parent::__construct();
    }

    public function build($context) {
        parent::build($context);

        // Uncomment these lines to add scripts and stylesheets to this page:
        // $this->addStylesheetToHead(URL.'/extensions//assets/...', 'screen');
        $this->addScriptToHead(URL . '/extensions/stripe/assets/stripe.content.js');
    }

    public function __viewIndex() {
        $this->setTitle('Stripe Plans');
        $this->setPageType('table');
        $this->appendSubheading('Stripe Plans', Widget::Anchor('Create New', URL . '/symphony/extension/stripe/plans/new/', 'Create a new entry', 'create button'));

        $tableHead = array(
            array('Name', 'col'),
            array('Interval', 'col'),
            array('Amount', 'col'),
            array('Currency', 'col'),
            array('Trial Period Days', 'col')
        );

        // Retrieve all Stripe plans
        Stripe::setApiKey(Stripe_General::getApiKey());
        $plans = Stripe_Plan::all();
        $plans = $plans->data;

        if(empty($plans)) {
            $tableBody = array(Widget::TableRow(array(Widget::TableData('None found.', 'inactive', null, count($tableHead)))));
        } else {
            $tableData = array();
            foreach($plans as $plan) {
                $tableData[] = Widget::TableData(Widget::Anchor(General::limitWords($plan->name), Administration::instance()->getCurrentPageURL() . 'edit/' . $plan['id'], $plan->name, 'content'));
                $tableData[] = Widget::TableData(General::limitWords(ucfirst($plan->interval)));
                $tableData[] = Widget::TableData(General::limitWords(Stripe_General::centsToDollars($plan->amount)));
                $tableData[] = Widget::TableData(General::limitWords(strtoupper($plan->currency)));
                $tableData[] = Widget::TableData(General::limitWords($plan->trial_period_days));

                $tableData[0]->appendChild(Widget::Input('items[' . $plan->id . ']', $plan->id, 'checkbox'));


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
                    $plan = Stripe_Plan::retrieve($id);
                    $plan->delete();
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
                case 'saved':
                    $this->pageAlert(
                        __(
                            'Plan updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Plans</a>',
                            array(
                                DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
                                Stripe_General::contentUrl() . 'plans/new/',
                                Stripe_General::contentUrl() . 'plans/',
                            )
                        ),
                        Alert::SUCCESS);
                    break;

                case 'created':
                    $this->pageAlert(
                        __(
                            'Plan created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Plans</a>',
                            array(
                                DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
                                Stripe_General::contentUrl() . 'plans/new/',
                                Stripe_General::contentUrl() . 'plans/',
                            )
                        ),
                        Alert::SUCCESS);
                    break;
            }
        }

        if(isset($this->_context[1])) {
            Stripe::setApiKey(Stripe_General::getApiKey());
            try {
                $plan = Stripe_Plan::retrieve($this->_context[1]);
                $fields = array(
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'interval' => $plan->interval,
                    'trial_period_days' => $plan->trial_period_days,
                    'amount' => Stripe_General::centsToDollars($plan->amount),
                    'currency' => $plan->currency
                );

            } catch(Stripe_Error $e) {
                // Todo
            }
        }
        $this->setTitle('Edit a Stripe Plan');
        $this->setPageType('form');
        $this->appendSubheading($fields['name']);
        $this->insertBreadcrumbs(array(
            Widget::Anchor('Stripe Plans', Stripe_General::contentUrl() . 'plans/'),
        ));


        $this->Form->setAttribute('class', 'two columns');

        $primary = new XMLElement('fieldset');
        $primary->setAttribute('class', 'primary column');

        $name = Widget::Label('Name');
        $name->appendChild(Widget::Input('fields[name]', $fields['name'], 'text'));
        $primary->appendChild($name);

        $id = Widget::Label('ID');
        $id->appendChild(Widget::Input('fields[id]', $fields['id'], 'text', array('disabled' => 'disabled')));
        $primary->appendChild($id);

        $trial = Widget::Label('Trial Period Days');
        $trial->appendChild(Widget::Input('fields[trial_period_days]', $fields['trial_period_days'], 'text', array('disabled' => 'disabled')));
        $primary->appendChild($trial);

        $secondary = new XMLElement('fieldset');
        $secondary->setAttribute('class', 'secondary column');

        $amount = Widget::Label('Amount');
        $amount->appendChild(Widget::Input('fields[amount]', (string)$fields['amount'], 'text', array('disabled' => 'disabled')));
        $secondary->appendChild($amount);

        // Currency options
        $options = array(
            array('usd', ($fields['currency'] == 'usd'), 'USD'),
        );

        $currency = Widget::Label('Currency');
        $currency->appendChild(Widget::Select('fields[currency]', $options, array('disabled' => 'disabled')));
        $secondary->appendChild($currency);

        // Interval options
        $options = array(
            array('month', ($fields['interval'] == 'month'), 'Month'),
            array('year', ($fields['interval'] == 'year'), 'Year')
        );

        $interval = Widget::Label('Interval');
        $interval->appendChild(Widget::Select('fields[interval]', $options, array('disabled' => 'disabled')));
        $secondary->appendChild($interval);

        $this->Form->appendChild($primary);
        $this->Form->appendChild($secondary);

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input('action[save]', 'Save Plan', 'submit', array('accesskey' => 's')));

        $this->Form->appendChild($div);
    }

    public function __viewNew() {
        if(isset($_POST['fields'])) {
            $fields = $_POST['fields'];
        }

        $this->setTitle('Add a Stripe Plan');
        $this->setPageType('form');
        $this->appendSubheading('Untitled');
        $this->insertBreadcrumbs(array(
            Widget::Anchor('Stripe Plans', Stripe_General::contentUrl() . 'plans/'),
        ));

        $this->Form->setAttribute('class', 'two columns');

        $primary = new XMLElement('fieldset');
        $primary->setAttribute('class', 'primary column');

        $name = Widget::Label('Name');
        $name->appendChild(Widget::Input('fields[name]', $fields['name'], 'text'));
        $primary->appendChild($name);

        $id = Widget::Label('ID');
        $id->appendChild(Widget::Input('fields[id]', $fields['id'], 'text'));
        $primary->appendChild($id);

        $trial = Widget::Label('Trial Period Days');
        $trial->appendChild(Widget::Input('fields[trial_period_days]', $fields['trial_period_days'], 'text'));
        $primary->appendChild($trial);

        $secondary = new XMLElement('fieldset');
        $secondary->setAttribute('class', 'secondary column');

        $amount = Widget::Label('Amount');
        $amount->appendChild(Widget::Input('fields[amount]', (string)$fields['amount'], 'text'));
        $secondary->appendChild($amount);

        // Currency options
        $options = array(
            array('usd', ($fields['currency'] == 'usd'), 'USD'),
        );

        $currency = Widget::Label('Currency');
        $currency->appendChild(Widget::Select('fields[currency]', $options));
        $secondary->appendChild($currency);

        // Interval options
        $options = array(
            array('month', ($fields['interval'] == 'month'), 'Month'),
            array('year', ($fields['interval'] == 'year'), 'Year')
        );

        $interval = Widget::Label('Interval');
        $interval->appendChild(Widget::Select('fields[interval]', $options));
        $secondary->appendChild($interval);

        $this->Form->appendChild($primary);
        $this->Form->appendChild($secondary);

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input('action[save]', 'Create Plan', 'submit', array('accesskey' => 's')));

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
                    // Expect dollars and convert for Stripe
                    $fields['amount'] = Stripe_General::dollarsToCents($fields['amount']);

                    Stripe_Plan::create($fields);
                    redirect(Stripe_General::contentUrl() . 'plans/edit/' . $fields['id'] . '/created/');
                } catch(Stripe_Error $e) {
                    $body = $e->getJsonBody();
                    $err = $body['error'];
                    return $this->pageAlert(
                        __('Error encountered.  ') . $err['message'], Alert::ERROR
                    );
                }
            } else {
                try {
                    $plan = Stripe_Plan::retrieve($this->_context[1]);
                    $plan->name = $fields['name'];
                    $plan->save();
                    redirect(Administration::instance()->getCurrentPageURL() . 'saved/');
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