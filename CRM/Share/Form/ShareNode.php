<?php

declare(strict_types = 1);

use CRM_Share_ExtensionUtil as E;
use Civi\Api4\ShareNode;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Share_Form_ShareNode extends CRM_Core_Form {

  /**
   * @var array<string, mixed>
   */
  protected array $shareNode = [];

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    parent::preProcess();

    if ($this->getAction() === CRM_Core_Action::NONE) {
      $this->setAction(CRM_Core_Action::ADD);
    }

    if (in_array($this->getAction(), [CRM_Core_Action::UPDATE, CRM_Core_Action::DELETE], TRUE)) {
      $shareNodeId = CRM_Utils_Request::retrieve('id', 'Positive');

      $this->shareNode = ShareNode::get()
        ->addSelect('*')
        ->addWhere('id', '=', $shareNodeId)
        ->execute()
        ->first() ?? [];

      if (empty($this->shareNode)) {
        $url = CRM_Utils_System::url('civicrm/share/node');
        CRM_Core_Error::statusBounce(E::ts('Invalid ID parameter of ShareNode'), $url, E::ts('Not Found'));
      }

      $this->assign('shareNode', $this->shareNode);
    }

    $formTitle = E::ts(match ($this->getAction()) {
      CRM_Core_Action::UPDATE => 'Update ShareNode <em>%1</em> ',
      CRM_Core_Action::DELETE => 'Delete ShareNode <em>%1</em> ',
      default => 'Add ShareNode',
    }, [1 => $this->shareNode['short_name'] ?? NULL]);
    $this->setTitle($formTitle);

    // Set redirect destination.
    $this->controller->setDestination($this->getRedirectUrl());
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();

    $fields = CRM_Share_DAO_ShareNode::getSupportedFields();
    unset($fields['id']);
    $buttons = [];

    if (in_array($this->getAction(), [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE], TRUE)) {
      foreach ($fields as $field) {
        $this->addField($field['name']);
      }

      $buttons[] = ['type' => 'submit', 'name' => E::ts('Save'), 'isDefault' => TRUE];
    }

    if (in_array($this->getAction(), [CRM_Core_Action::UPDATE, CRM_Core_Action::DELETE], TRUE)) {
      $this->add('hidden', 'id', $this->shareNode['id']);
    }

    if ($this->getAction() === CRM_Core_Action::DELETE) {
      $buttons[] = ['type' => 'submit', 'name' => E::ts('Delete'), 'isDefault' => TRUE];
    }

    $buttons[] = ['type' => 'cancel', 'name' => E::ts('Cancel'), 'class' => 'cancel'];
    $this->addButtons($buttons);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
  }

  /**
   * {@inheritDoc}
   */
  public function setDefaultValues() {
    $defaults = [];

    if ($this->getAction() === CRM_Core_Action::UPDATE) {
      foreach ($this->getRenderableElementNames() as $elementName) {
        $defaults[$elementName] = $this->shareNode[$elementName];
      }
    }

    return $defaults;
  }

  /**
   * {@inheritDoc}
   */
  public function validate() {
    parent::validate();

    $values = $this->exportValues();

    if (in_array($this->getAction(), [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE], TRUE)) {
      // Do not allow duplicate ShareNode short names
      $shareNodesCount = ShareNode::get()
        ->selectRowCount()
        ->addWhere('short_name', '=', $values['short_name'])
        ->execute()
        ->count();

      if ($shareNodesCount > 0 && $values['short_name'] !== ($this->shareNode['short_name'] ?? '')) {
        $this->_errors['short_name'] = E::ts('A ShareNode with this short name already exists.');
      }
    }

    return (0 == count($this->_errors));
  }

  /**
   * {@inheritDoc}
   */
  public function postProcess(): void {
    parent::postProcess();

    $values = $this->exportValues($this->getRenderableElementNames());

    if (in_array($this->getAction(), [CRM_Core_Action::UPDATE, CRM_Core_Action::DELETE], TRUE)) {
      $values = [...$values, ...$this->exportValues('id')];
    }

    if ($this->getAction() === CRM_Core_Action::DELETE) {
      ShareNode::delete(TRUE)
        ->addWhere('id', '=', $values['id'])
        ->execute();
    }

    if (in_array($this->getAction(), [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE], TRUE)) {
      ShareNode::save()
        ->addRecord($values)
        ->execute();
    }

    $statusMessage = E::ts(match ($this->getAction()) {
      CRM_Core_Action::UPDATE => 'The ShareNode <em>%1</em> was successfully updated!',
      CRM_Core_Action::DELETE => 'The ShareNode <em>%1</em> was successfully deleted!',
      default => 'The ShareNode <em>%1</em> was successfully created!',
    }, [1 => $values['short_name'] ?? $this->shareNode['short_name']]);
    CRM_Core_Session::setStatus($statusMessage, E::ts('Success'), 'success');
  }

  /**
   * {@inheritDoc}
   */
  public function cancelAction() {
    $this->controller->setDestination($this->getRedirectUrl());
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames(): array {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return array_search($this->getAction(), CRM_Core_Action::$_names, TRUE);
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return CRM_Core_DAO_AllCoreTables::getEntityNameForClass('CRM_Share_DAO_ShareNode');
  }

  private function getRedirectUrl() {
    return CRM_Utils_System::url('civicrm/share/node', 'reset=1');
  }

}
