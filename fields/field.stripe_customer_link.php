<?php

require_once(EXTENSIONS . "/selectbox_link_field/fields/field.selectbox_link.php");
if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

Class FieldStripe_Customer_Link extends FieldSelectBox_Link {


    public function __construct(){
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

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null){
        // CHECK OK TO CALL PARENT OF PARENT?
        Field::displaySettingsPanel($wrapper, $errors);

        $sections = SectionManager::fetch(NULL, 'ASC', 'sortorder');
        $options = array();

        if(is_array($sections) && !empty($sections)) foreach($sections as $section){
            $section_fields = $section->fetchFields();
            if(!is_array($section_fields)) continue;

            $fields = array();
            foreach($section_fields as $f){
                if($f->get('id') != $this->get('id') && $f->canPrePopulate() && $f->get('type') == 'stripe_customer_id') {
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
        $label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][related_field_id][]', $options));

        // Add options
        if(isset($errors['related_field_id'])) {
            $wrapper->appendChild(Widget::Error($label, $errors['related_field_id']));
        }
        else $wrapper->appendChild($label);

        $div = new XMLElement('div', NULL, array('class' => 'three columns'));
        $this->appendRequiredCheckbox($div);

        $label = Widget::Label();
        $label->setAttribute('class', 'column');
        $input = Widget::Input("fields[".$this->get('sortorder')."][disabled]", 'yes', 'checkbox');

        if ($this->get('disabled') == 'yes') $input->setAttribute('checked', 'checked');
        $label->setValue($input->generate() .' '. __('Disable editing of this field on publish page'));

        $div->appendChild($label);
        $this->appendShowAssociationCheckbox($div);
        $wrapper->appendChild($div);
    }

    public function commit(){
        // CHECK OK TO CALL PARENT OF PARENT?
        if(!Field::commit()) return false;

        $id = $this->get('id');

        if($id === false) return false;

        $fields = array();
        $fields['field_id'] = $id;
        if($this->get('related_field_id') != '') $fields['related_field_id'] = $this->get('related_field_id');
        $fields['show_association'] = $this->get('show_association') == 'yes' ? 'yes' : 'no';
        $fields['related_field_id'] = implode(',', $this->get('related_field_id'));

        if(!FieldManager::saveSettings($id, $fields)) return false;

        $this->removeSectionAssociation($id);
        foreach($this->get('related_field_id') as $field_id){
            $this->createSectionAssociation(NULL, $id, $field_id, $this->get('show_association') == 'yes' ? true : false);
        }

        return true;
    }

}
