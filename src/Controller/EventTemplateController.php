<?php

namespace Drupal\effective_activism\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Entity\EventTemplateInterface;

/**
 * Class EventTemplateController.
 *
 *  Returns responses for Event template routes.
 */
class EventTemplateController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Event template  revision.
   *
   * @param int $event_template_revision
   *   The Event template  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($event_template_revision) {
    $event_template = $this->entityManager()->getStorage('event_template')->loadRevision($event_template_revision);
    $view_builder = $this->entityManager()->getViewBuilder('event_template');

    return $view_builder->view($event_template);
  }

  /**
   * Page title callback for a Event template  revision.
   *
   * @param int $event_template_revision
   *   The Event template  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($event_template_revision) {
    $event_template = $this->entityManager()->getStorage('event_template')->loadRevision($event_template_revision);
    return $this->t('Revision of %title from %date', ['%title' => $event_template->label(), '%date' => format_date($event_template->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Event template .
   *
   * @param \Drupal\effective_activism\Entity\EventTemplateInterface $event_template
   *   A Event template  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EventTemplateInterface $event_template) {
    $account = $this->currentUser();
    $langcode = $event_template->language()->getId();
    $langname = $event_template->language()->getName();
    $languages = $event_template->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $event_template_storage = $this->entityManager()->getStorage('event_template');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $event_template->label()]) : $this->t('Revisions for %title', ['%title' => $event_template->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all event template revisions") || $account->hasPermission('administer event template entities')));
    $delete_permission = (($account->hasPermission("delete all event template revisions") || $account->hasPermission('administer event template entities')));

    $rows = [];

    $vids = $event_template_storage->revisionIds($event_template);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\effective_activism\EventTemplateInterface $revision */
      $revision = $event_template_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $event_template->getRevisionId()) {
          $link = $this->l($date, new Url('entity.event_template.revision', ['event_template' => $event_template->id(), 'event_template_revision' => $vid]));
        }
        else {
          $link = $event_template->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.event_template.revision_revert', ['event_template' => $event_template->id(), 'event_template_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.event_template.revision_delete', ['event_template' => $event_template->id(), 'event_template_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['event_template_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
