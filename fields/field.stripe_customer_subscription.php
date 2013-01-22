<?php

require_once TOOLKIT . '/fields/field.input.php';
if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

Class FieldStripe_Customer_Subscription extends FieldInput {

    public function __construct(){
        parent::__construct();
        $this->_name = __('Stripe: Customer Subscription');
        $this->_required = true;

        $this->set('required', 'yes');
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
        $value = General::sanitize($data['value']);
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL), 'text', ($this->get('disabled') != 'yse' ? array('disabled' => 'disabled') : NULL)));

        if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
        else $wrapper->appendChild($label);
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
        // CHECK OK TO CALL PARENT OF PARENT?
        Field::displaySettingsPanel($wrapper, $errors);

        $div = new XMLElement('div', NULL, array('class' => 'three columns'));
        $this->appendShowAssociationCheckbox($div);
        $this->appendRequiredCheckbox($div);

        $label = Widget::Label();
        $label->setAttribute('class', 'column');
        $input = Widget::Input("fields[".$this->get('sortorder')."][disabled]", 'yes', 'checkbox');

        if ($this->get('disabled') == 'yes') $input->setAttribute('checked', 'checked');
        $label->setValue($input->generate() .' '. __('Disable editing of this field on publish page'));

        $div->appendChild($label);
        $wrapper->appendChild($div);
    }

    public function commit(){
        if(!parent::commit()) return false;

        $id = $this->get('id');

        if($id === false) return false;

        $fields = array();

        $fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
        $fields['disabled'] = ($this->get('disabled') ? $this->get('disabled') : 'yes');

        return FieldManager::saveSettings($id, $fields);
    }

}
