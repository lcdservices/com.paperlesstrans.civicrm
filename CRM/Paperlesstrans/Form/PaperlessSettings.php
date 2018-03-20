<?php

/**
 * Form controller class.
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Paperlesstrans_Form_PaperlessSettings extends CRM_Core_Form {

  public function buildQuickForm() {
    $this->add(
      'checkbox',
      'enable_public_future_recurring_start',
      ts('Enable public selection of future recurring start dates.')
    );

    $days = array('-1' => 'disabled');
    for ($i = 1; $i <= 28; $i++) {
      $days["$i"] = "$i";
    }
    $attr = array(
      'size' => 29,
      'style' => 'width:150px',
      'required' => FALSE,
    );
    $day_select = $this->add(
    // Field type.
      'select',
    // Field name.
      'days',
      ts('Restrict allowable days of the month for Recurring Contributions'),
      $days,
      FALSE,
      $attr
    );

    $day_select->setMultiple(TRUE);
    $day_select->setSize(29);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    //set defaults
    $result = CRM_Core_BAO_Setting::getItem('Paperless Payments Extension', 'paperless_settings');
    $defaults = (empty($result)) ? array() : $result;
    $this->setDefaults($defaults);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // Export form elements.
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   *
   */
  public function postProcess() {
    $values = $this->exportValues();
    foreach (array('qfKey', '_qf_default', '_qf_PaperlessSettings_submit', 'entryURL') as $key) {
      if (isset($values[$key])) {
        unset($values[$key]);
      }
    }
    CRM_Core_BAO_Setting::setItem($values, 'Paperless Payments Extension', 'paperless_settings');
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
