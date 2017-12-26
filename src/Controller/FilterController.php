<?php

namespace Drupal\effective_activism\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\effective_activism\Entity\FilterInterface;

/**
 * Class FilterController.
 *
 *  Returns responses for Filter routes.
 */
class FilterController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Filter  revision.
   *
   * @param int $filter_revision
   *   The Filter  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($filter_revision) {
    $filter = $this->entityManager()->getStorage('filter')->loadRevision($filter_revision);
    $view_builder = $this->entityManager()->getViewBuilder('filter');

    return $view_builder->view($filter);
  }

  /**
   * Page title callback for a Filter  revision.
   *
   * @param int $filter_revision
   *   The Filter  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($filter_revision) {
    $filter = $this->entityManager()->getStorage('filter')->loadRevision($filter_revision);
    return $this->t('Revision of %title from %date', ['%title' => $filter->label(), '%date' => format_date($filter->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Filter .
   *
   * @param \Drupal\effective_activism\Entity\FilterInterface $filter
   *   A Filter  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(FilterInterface $filter) {
    $account = $this->currentUser();
    $langcode = $filter->language()->getId();
    $langname = $filter->language()->getName();
    $languages = $filter->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $filter_storage = $this->entityManager()->getStorage('filter');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $filter->label()]) : $this->t('Revisions for %title', ['%title' => $filter->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all filter revisions") || $account->hasPermission('administer filter entities')));
    $delete_permission = (($account->hasPermission("delete all filter revisions") || $account->hasPermission('administer filter entities')));

    $rows = [];

    $vids = $filter_storage->revisionIds($filter);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\effective_activism\FilterInterface $revision */
      $revision = $filter_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $filter->getRevisionId()) {
          $link = $this->l($date, new Url('entity.filter.revision', ['filter' => $filter->id(), 'filter_revision' => $vid]));
        }
        else {
          $link = $filter->link($date);
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
              'url' => Url::fromRoute('entity.filter.revision_revert', ['filter' => $filter->id(), 'filter_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.filter.revision_delete', ['filter' => $filter->id(), 'filter_revision' => $vid]),
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

    $build['filter_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
