<?php

require_once TOOLKIT . '/fields/field.input.php';
if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

Class FieldStripe_Customer_ID extends FieldInput {

    public function __construct(){
        parent::__construct();
        $this->_name = __('Stripe: Customer ID');
        $this->_required = true;

        $this->set('required', 'yes');
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
        $value = General::sanitize($data['value']);
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL), 'text'));

        if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
        else $wrapper->appendChild($label);
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function findCustomerIdFromEntryId($data) {
        $field = Symphony::Database()->fetchRow(0, "
				SELECT `id` FROM `tbl_fields`
				WHERE `type` = 'stripe_customer_id'
				LIMIT 1"
        );

        $entry = Symphony::Database()->fetchRow(0, sprintf("
				SELECT `value` FROM `tbl_entries_data_%d`
				WHERE `entry_id` = '%s'
				LIMIT 1",
            $field['id'], addslashes($data)
        ));

        return $entry['value'];
    }

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

    /*-------------------------------------------------------------------------
    Filtering:
-------------------------------------------------------------------------*/

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
        $field_id = $this->get('id');

        // Custom
        if(!preg_match('/^cus_/i', $data[0])) {
            $data[0] = $this->findCustomerIdFromEntryId($data[0]);
        }
        // End Custom

        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], array('value', 'handle'), $joins, $where);
        }
        else if ($andOperation) {
            foreach ($data as $value) {
                $this->_key++;
                $value = $this->cleanValue($value);
                $joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
                $where .= "
						AND (
							t{$field_id}_{$this->_key}.value = '{$value}'
							OR t{$field_id}_{$this->_key}.handle = '{$value}'
						)
					";
            }
        }

        else {
            if (!is_array($data)) $data = array($data);

            foreach ($data as &$value) {
                $value = $this->cleanValue($value);
            }

            $this->_key++;
            $data = implode("', '", $data);
            $joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
            $where .= "
					AND (
						t{$field_id}_{$this->_key}.value IN ('{$data}')
						OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
					)
				";
        }

        return true;
    }


}
