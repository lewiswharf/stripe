<?php

require_once(EXTENSIONS . "/selectbox_link_field/fields/field.selectbox_link.php");
if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

Class FieldStripe_Customer_Link extends FieldSelectBox_Link {


    public function __construct() {
        parent::__construct();
        $this->_name = __('Stripe: Customer Link');
        $this->_required = true;
        $this->_showassociation = true;

        // Default settings
        $this->set('show_column', 'no');
        $this->set('show_association', 'yes');
        $this->set('required', 'yes');
        $this->set('related_field_id', array());
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null) {
        $status = self::__OK__;

        if (!is_array($data)) return array('relation_id' => $this->findEntryIdFromCustomerId($data));

        $result = array();

        foreach ($data as $a => $value) {
            $result['relation_id'][] = $this->findEntryIdFromCustomerId($value);
        }

        return $result;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
        return $this->buildDSRetrievalSQL($data, $joins, $where, $andOperation);
    }

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation=false){
        $field_id = $this->get('id');

        // Custom
        if(preg_match('/^cus_/i', $data[0])) {
            $data[0] = $this->findEntryIdFromCustomerId($data[0]);
        }
        // End Custom

        if(preg_match('/^sql:\s*/', $data[0], $matches)) {
            $data = trim(array_pop(explode(':', $data[0], 2)));

            // Check for NOT NULL (ie. Entries that have any value)
            if(strpos($data, "NOT NULL") !== false) {
                $joins .= " LEFT JOIN
									`tbl_entries_data_{$field_id}` AS `t{$field_id}`
								ON (`e`.`id` = `t{$field_id}`.entry_id)";
                $where .= " AND `t{$field_id}`.relation_id IS NOT NULL ";

            }
            // Check for NULL (ie. Entries that have no value)
            else if(strpos($data, "NULL") !== false) {
                $joins .= " LEFT JOIN
									`tbl_entries_data_{$field_id}` AS `t{$field_id}`
								ON (`e`.`id` = `t{$field_id}`.entry_id)";
                $where .= " AND `t{$field_id}`.relation_id IS NULL ";

            }
        }
        else {
            $negation = false;
            $null = false;
            if(preg_match('/^not:/', $data[0])) {
                $data[0] = preg_replace('/^not:/', null, $data[0]);
                $negation = true;
            }
            else if(preg_match('/^sql-null-or-not:/', $data[0])) {
                $data[0] = preg_replace('/^sql-null-or-not:/', null, $data[0]);
                $negation = true;
                $null = true;
            }

            foreach($data as $key => &$value) {
                // for now, I assume string values are the only possible handles.
                // of course, this is not entirely true, but I find it good enough.
                if(!is_numeric($value) && !is_null($value)){
                    $related_field_ids = $this->get('related_field_id');
                    $id = null;

                    foreach($related_field_ids as $related_field_id) {
                        try {
                            $return = Symphony::Database()->fetchCol("id", sprintf("
									SELECT
										`entry_id` as `id`
									FROM
										`tbl_entries_data_%d`
									WHERE
										`handle` = '%s'
									LIMIT 1", $related_field_id, Lang::createHandle($value)
                            ));

                            // Skipping returns wrong results when doing an
                            // AND operation, return 0 instead.
                            if(!empty($return)) {
                                $id = $return[0];
                                break;
                            }
                        } catch (Exception $ex) {
                            // Do nothing, this would normally be the case when a handle
                            // column doesn't exist!
                        }
                    }

                    $value = (is_null($id)) ? 0 : $id;
                }
            }

            if($andOperation) {
                $condition = ($negation) ? '!=' : '=';
                foreach($data as $key => $bit){
                    $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id$key` ON (`e`.`id` = `t$field_id$key`.entry_id) ";
                    $where .= " AND (`t$field_id$key`.relation_id $condition '$bit' ";

                    if($null) {
                        $where .= " OR `t$field_id$key`.`relation_id` IS NULL) ";
                    }
                    else {
                        $where .= ") ";
                    }
                }
            }
            else {
                $condition = ($negation) ? 'NOT IN' : 'IN';

                // Apply a different where condition if we are using $negation. RE: #29
                if($negation) {
                    $condition = 'NOT EXISTS';
                    $where .= " AND $condition (
							SELECT *
							FROM `tbl_entries_data_$field_id` AS `t$field_id`
							WHERE `t$field_id`.entry_id = `e`.id AND `t$field_id`.relation_id IN (".implode(", ", $data).")
						)";
                }
                // Normal filtering
                else {
                    $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
                    $where .= " AND (`t$field_id`.relation_id $condition ('".implode("', '", $data)."') ";

                    // If we want entries with null values included in the result
                    $where .= ($null) ? " OR `t$field_id`.`relation_id` IS NULL) " : ") ";
                }
            }
        }

        return true;
    }

    public function findEntryIdFromCustomerId($data) {
        $field = Symphony::Database()->fetchRow(0, "
				SELECT `id` FROM `tbl_fields`
				WHERE `type` = 'stripe_customer_id'
				LIMIT 1"
        );

        $entry = Symphony::Database()->fetchRow(0, sprintf("
				SELECT `entry_id` FROM `tbl_entries_data_%d`
				WHERE `value` = '%s'
				LIMIT 1",
            $field['id'], addslashes($data)
        ));

        return $entry['entry_id'];
    }

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

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {
//       print_r($data);
        $value = General::sanitize($this->findCustomerIdFromEntryId($data['relation_id']));
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix, (strlen($value) != 0 ? $value : NULL), 'text'));

        if ($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
        else $wrapper->appendChild($label);
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
        // CHECK OK TO CALL PARENT OF PARENT?
        Field::displaySettingsPanel($wrapper, $errors);

        $sections = SectionManager::fetch(NULL, 'ASC', 'sortorder');
        $options = array();

        if (is_array($sections) && !empty($sections)) foreach ($sections as $section) {
            $section_fields = $section->fetchFields();
            if (!is_array($section_fields)) continue;

            $fields = array();
            foreach ($section_fields as $f) {
                if ($f->get('id') != $this->get('id') && $f->canPrePopulate() && $f->get('type') == 'stripe_customer_id') {
                    $fields[] = array(
                        $f->get('id'),
                        is_array($this->get('related_field_id')) ? in_array($f->get('id'), $this->get('related_field_id')) : false,
                        $f->get('label'),
                        $f->get('type')
                    );
                    $options[] = array(
                        'label' => $section->get('name'),
                        'options' => $fields
                    );
                }
            }
        }

        $label = Widget::Label(__('Values'));
        $label->appendChild(Widget::Select('fields[' . $this->get('sortorder') . '][related_field_id][]', $options));

        // Add options
        if (isset($errors['related_field_id'])) {
            $wrapper->appendChild(Widget::Error($label, $errors['related_field_id']));
        } else $wrapper->appendChild($label);

        $div = new XMLElement('div', NULL, array('class' => 'three columns'));
        $this->appendRequiredCheckbox($div);

        $label = Widget::Label();
        $label->setAttribute('class', 'column');
        $input = Widget::Input("fields[" . $this->get('sortorder') . "][disabled]", 'yes', 'checkbox');

        if ($this->get('disabled') == 'yes') $input->setAttribute('checked', 'checked');
        $label->setValue($input->generate() . ' ' . __('Disable editing of this field on publish page'));

        $div->appendChild($label);
        $this->appendShowAssociationCheckbox($div);
        $wrapper->appendChild($div);
    }

    public function commit() {
        // CHECK OK TO CALL PARENT OF PARENT?
        if (!Field::commit()) return false;

        $id = $this->get('id');

        if ($id === false) return false;

        $fields = array();
        $fields['field_id'] = $id;
        if ($this->get('related_field_id') != '') $fields['related_field_id'] = $this->get('related_field_id');
        $fields['show_association'] = $this->get('show_association') == 'yes' ? 'yes' : 'no';
        $fields['related_field_id'] = implode(',', $this->get('related_field_id'));

        if (!FieldManager::saveSettings($id, $fields)) return false;

        $this->removeSectionAssociation($id);
        foreach ($this->get('related_field_id') as $field_id) {
            $this->createSectionAssociation(NULL, $id, $field_id, $this->get('show_association') == 'yes' ? true : false);
        }

        return true;
    }

}
