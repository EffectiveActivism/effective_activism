<?php

namespace Drupal\effective_activism\Form\Import;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\effective_activism\Helper\ImportParser\CSVParser;

/**
 * Form controller for Import edit forms.
 *
 * @ingroup effective_activism
 */
class ImportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $form['timezone_notice'] = [
      '#type' => 'item',
      '#title' => $this->t('Timezones and dates'),
      '#markup' => $this->t('The dates of imported events will be imported relative to the selected groups timezone. For example, if the imported event has a start time of 11:00 am, and the group selected for the import has the timezone "Europe/Copenhagen (UTC +1)", the start time will be 11:00 am (UTC +1)'),
    ];
    // Hide fields.
    $form['user_id']['#attributes']['class'][] = 'hidden';
    $form['revision_log_message']['#attributes']['class'][] = 'hidden';
    // Entity bundle specializations.
    $build_info = $form_state->getBuildInfo();
    switch ($build_info['form_id']) {
      case 'import_csv_add_form':
        // Add validation.
        $form['#validate'][] = 'Drupal\effective_activism\Helper\ImportHelper::validateCsv';
        // Add import instructions.
        $form['instructions'] = [
          '#type' => 'details',
          '#title' => t('Instructions on how to import a CSV file'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];
        $form['instructions']['text'] = [
          '#type' => 'item',
          '#markup' => t('A CSV file must adhere to the following format.') . '<p>' .
          t('The first row must contain the following column names:') . '<em>' . implode(', ', CSVParser::CSVHEADERFORMAT) . '</em></br>' .
          t('The rows after the first row contain the events to be imported.') . '</br>' .
          t('Example:') . '</br><table><thead><tr><td>' . implode('</td><td>', CSVParser::CSVHEADERFORMAT) . '</td></tr></thead><tbody><tr><td>2016-12-13 11:00</td><td>2016-12-13 13:00</td><td>Kultorvet, Copenhagen, Denmark</td><td>By the fountain</td><td>My custom title</td><td>My custom description</td><td>leafleting | 4 | 0 | 1 | 0 | 1000 | Flyer design B</td></tr></tbody></table>' .
          t('<strong>Start date and end date</strong>') . '</br>' .
          t('<em>Required</em>') . '</br>' .
          t('Dates are required for each event and must match the <a href="https://en.wikipedia.org/wiki/ISO_8601" target="_blank">ISO 8601</a> format: YYYY-MM-DD HH:MM') . '</br>' .
          t('Example: 2016-12-13 11:00') . '</p><p>' .
          t('<strong>Address</strong>') . '</br>' .
          t('The address of an event should be a proper address. Any extra location information, such as "By the fountain", "Room B", etc. that aren’t part of a real address should be added to the <em>address_extra_information</em> column instead. If possible, use addresses with city and country appended.') . '</br>' .
          t('Example: Grenzacherstrasse 10, Basel, Switzerland') . '</p><p>' .
          t('<strong>Results</strong>') . '</br>' .
          t('Results consist of six values: name of result, participant count, duration in minutes, hours, days, and quantifiable value. Values are separated by the "pipe" character ( | ). Each row can contain another result for the same event.') . '</br>' .
          t('Example: leafleting | 9 | 30 | 2 | 0 | 4000') . '</br>' .
          t('<em>This reads: a leafleting result | 9 participants | duration: 30 minutes | 2 hours | 0 days | 4000 leaflets</em>') . '</p>',
        ];
        break;

      case 'import_csv_edit_form':
        // Restrict access to existing import entities.
        $form['parent']['#disabled'] = TRUE;
        $form['field_file_csv']['#disabled'] = TRUE;
        break;
    }
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
        drupal_set_message($this->t('Created the import.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the import.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.import.canonical', ['import' => $entity->id()]);
  }

}
