<?php

declare(strict_types = 1);

use CRM_Share_ExtensionUtil as E;
use Civi\Api4\ShareNodePeering;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Share_Form_ShareNodePeering extends CRM_Core_Form {

  /**
   * @var array<string, mixed>
   */
  protected array $shareNodePeering = [];

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    parent::preProcess();

    if ($this->getAction() === CRM_Core_Action::NONE) {
      $this->setAction(CRM_Core_Action::ADD);
    }

    if (in_array($this->getAction(), [CRM_Core_Action::UPDATE, CRM_Core_Action::DELETE], TRUE)) {
      $shareNodePeeringId = CRM_Utils_Request::retrieve('id', 'Positive');

      $this->shareNodePeering = ShareNodePeering::get()
        ->addSelect('*')
        ->addWhere('id', '=', $shareNodePeeringId)
        ->execute()
        ->first() ?? [];

      if (empty($this->shareNodePeering)) {
        CRM_Core_Error::statusBounce(
          E::ts('Invalid ID parameter of ShareNodePeering'),
          $this->getRedirectUrl(),
          E::ts('Not Found')
        );
      }

      $this->assign('shareNodePeering', $this->shareNodePeering);
    }

    $formTitle = E::ts(match ($this->getAction()) {
      CRM_Core_Action::UPDATE => 'Update ShareNodePeering',
      CRM_Core_Action::DELETE => 'Delete ShareNodePeering',
      default => 'Add ShareNodePeering',
    });
    $this->setTitle($formTitle);

    // Set redirect destination.
    $this->controller->setDestination($this->getRedirectUrl());
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();

    $fields = CRM_Share_DAO_ShareNodePeering::getSupportedFields();
    unset($fields['id']);
    $buttons = [];

    if (in_array($this->getAction(), [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE], TRUE)) {
      foreach ($fields as $field) {
        $props = [];

        if (!empty($field['pseudoconstant'])) {
          $props['options'] = CRM_Share_DAO_ShareNodePeering::buildOptions($field['name']);
        }

        $this->addField($field['name'], $props);
      }

      $buttons[] = ['type' => 'submit', 'name' => E::ts('Save'), 'isDefault' => TRUE];
    }

    if (in_array($this->getAction(), [CRM_Core_Action::UPDATE, CRM_Core_Action::DELETE], TRUE)) {
      $this->add('hidden', 'id', $this->shareNodePeering['id']);
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
        $defaults[$elementName] = $this->shareNodePeering[$elementName];
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
      // Do not allow duplicate ShareNodePeering local nodes
      $shareNodePeeringLocalCount = ShareNodePeering::get()
        ->selectRowCount()
        ->addWhere('local_node', '=', $values['local_node'])
        ->execute()
        ->count();

      if (
        $shareNodePeeringLocalCount > 0
        && (int) $values['local_node'] !== ($this->shareNodePeering['local_node'] ?? 0)
      ) {
        $this->_errors['local_node'] = E::ts('A ShareNodePeering with this node already exists.');
      }

      // Do not allow duplicate ShareNodePeering remote nodes
      $shareNodePeeringRemoteCount = ShareNodePeering::get()
        ->selectRowCount()
        ->addWhere('remote_node', '=', $values['remote_node'])
        ->execute()
        ->count();

      if (
        $shareNodePeeringRemoteCount > 0
        && (int) $values['remote_node'] !== ($this->shareNodePeering['remote_node'] ?? 0)
      ) {
        $this->_errors['remote_node'] = E::ts('A ShareNodePeering with this node already exists.');
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
      ShareNodePeering::delete(TRUE)
        ->addWhere('id', '=', $values['id'])
        ->execute();
    }

    if (in_array($this->getAction(), [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE], TRUE)) {
      ShareNodePeering::save()
        ->addRecord($values)
        ->execute();
    }

    $statusMessage = E::ts(match ($this->getAction()) {
      CRM_Core_Action::UPDATE => 'The ShareNodePeering was successfully updated!',
      CRM_Core_Action::DELETE => 'The ShareNodePeering was successfully deleted!',
      default => 'The ShareNodePeering was successfully created!',
    });
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
    return CRM_Core_DAO_AllCoreTables::getEntityNameForClass('CRM_Share_DAO_ShareNodePeering');
  }

  private function getRedirectUrl() {
    return CRM_Utils_System::url('civicrm/share/node/peering', 'reset=1');
  }

}
