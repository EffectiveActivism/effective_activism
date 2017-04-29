<?php

namespace Drupal\effective_activism\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DataAddController.
 *
 * @package Drupal\effective_activism\Controller
 */
class DataAddController extends ControllerBase {

  /**
   * Initializes the controller.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for the Data entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $type_storage
   *   The storage for the Data type.
   */
  public function __construct(EntityStorageInterface $storage, EntityStorageInterface $type_storage) {
    $this->storage = $storage;
    $this->typeStorage = $type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('data'),
      $entity_type_manager->getStorage('data_type')
    );
  }

  /**
   * Displays add links for available bundles/types for entity data .
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the data bundles/types that can be added or
   *   if there is only one type/bunlde defined for the site, the function
   *   returns the add page for that bundle/type.
   */
  public function add(Request $request) {
    $types = $this->typeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
          '%bundle' => 'Data',
          '@link' => $this->l($this->t('Go to the type creation page'), Url::fromRoute('entity.data_type.add_form')),
        ]),
      ];
    }
    return ['#theme' => 'data_content_add_list', '#content' => $types];
  }

  /**
   * Presents the creation form for data entities of given bundle/type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $data_type, Request $request) {
    $entity = $this->storage->create([
      'type' => $data_type->id(),
    ]);
    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $data_type) {
    return t('Create of bundle @label',
      ['@label' => $data_type->label()]
    );
  }

}
