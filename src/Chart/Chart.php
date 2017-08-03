<?php

namespace Drupal\effective_activism\Chart;

use Drupal\effective_activism\Chart\Axis\AxisInterface;
use Drupal\effective_activism\Chart\Axis\Axis;

abstract class Chart implements ChartInterface {

  /**
   * @var array
   *   The settings for the chart.
   */
  private $settings;

  /**
   * @var array
   *   The axes attached to this chart.
   */
  private $axes;

  /**
   * {@inheritdoc}
   */
  function __construct(array $settings) {
    $this->settings = $settings;
    $this->axes = [];
  }

  /**
   * {@inheritdoc}
   */
  public function attach(AxisInterface $axis) {
    if (!in_array($axis, $this->axes)) {
      $this->axes[] = $axis;
      $axis->attachTo($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function detach(AxisInterface $axis) {
    $key = array_search($axis, $this->axes);
    if($key !== FALSE) {
      unset($this->axes[$key]);
      $axis->detachFrom($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    
  }

  /**
   * {@inheritdoc}
   */
  abstract public function render();

  /**
   * {@inheritdoc}
   */
  protected function getCategoryAxes() {
    $category_axis = [];
    foreach ($this->axes as $axis) {
      if ($axis->getType() === Axis::TYPE_CATEGORIES) {
        $category_axis[] = $axis;
      }
    }
    return $category_axis;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDataAxes() {
    $data_axis = [];
    foreach ($this->axes as $axis) {
      if ($axis->getType() !== Axis::TYPE_CATEGORIES) {
        $data_axis[] = $axis;
      }
    }
    return $data_axis;
  }

  public function getSettings() {
    return $this->settings;
  }

}
