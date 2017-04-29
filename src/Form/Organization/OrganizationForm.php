<?php

namespace Drupal\effective_activism\Form\Organization;

use Drupal;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Constant;
use Drupal\effective_activism\Entity\Group;
use Drupal\effective_activism\Entity\ResultType;
use Drupal\effective_activism\Helper\ResultTypeHelper;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Form controller for Organizer edit forms.
 *
 * @ingroup effective_activism
 */
class OrganizationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\effective_activism\Entity\Organization */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    // Hide fields.
    $form['user_id']['#attributes']['class'][] = 'hidden';
    $form['revision_log_message']['#attributes']['class'][] = 'hidden';
    // Set organization entity id.
    $form_state->setTemporaryValue('entity_id', $entity->id());
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->setNewRevision();
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label organization.', [
          '%label' => $entity->label(),
        ]));
        // Create tagging vocabulary for organization.
        $name = t('@organization tags', ['@organization' => $entity->label()]);
        $vid = sprintf('tags_%d', $entity->id());
        $vocabulary = Vocabulary::create([
          'vid' => $vid,
          'name' => $name,
        ]);
        $vocabulary->save();
        // Create a group when creating an organization.
        $group = Group::create([
          'title' => Constant::GROUP_DEFAULT_VALUES['title'],
          'organization' => $entity->id(),
          'managers' => Drupal::currentUser()->id(),
        ]);
        $group->save();
        // Create default result types for new organizations.
        foreach (Constant::DEFAULT_RESULT_TYPES as $import_name => $settings) {
          $result_type = ResultType::create([
            'id' => ResultTypeHelper::getUniqueId($import_name),
            'label' => $settings['label'],
            'importname' => $import_name,
            'description' => $settings['description'],
            'datatypes' => $settings['datatypes'],
            'organization' => $entity->id(),
            'groups' => [
              $group->id() => $group->id(),
            ],
          ]);
          if ($result_type->save() === SAVED_NEW) {
            ResultTypeHelper::updateBundleSettings($result_type);
            ResultTypeHelper::addTaxonomyField($result_type);
          }
        }
        break;

      default:
        drupal_set_message($this->t('Saved the %label organization.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.organization.canonical', ['organization' => $entity->id()]);
  }
}
