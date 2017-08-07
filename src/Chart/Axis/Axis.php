<?php

namespace Drupal\effective_activism\Chart\Axis;

use Drupal\effective_activism\Chart\ChartInterface;

/**
 * Axis class.
 */
abstract class Axis implements AxisInterface {

  const TYPE_CATEGORIES = 0;

  const TYPE_LINE = 1;

  const TYPE_COLUMN = 2;

  /**
   * The charts that this axis is attached to.
   *
   * @var array
   */
  private $charts;

  /**
   * The type of axis.
   *
   * @var strnig
   */
  private $type;

  /**
   * The settings for the axis.
   *
   * @var \Drupal\effective_acitism\Chart\Axis\AxisSettingsInterface
   */
  private $settings;

  /**
   * The data for the axis.
   *
   * @var array
   */
  private $data;

  /**
   * {@inheritdoc}
   */
  public function __construct($type, array $settings, array $data) {
    $this->charts = [];
    $this->type = $type;
    $this->settings = $settings;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    foreach ($this->charts as $chart) {
      $chart->update();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isAttached(ChartInterface $chart) {
    return in_array($chart, $this->charts);
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ChartInterface $chart) {
    if (!in_array($chart, $this->charts)) {
      $this->charts[] = $chart;
      $chart->attach($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function detachFrom(ChartInterface $chart) {
    $key = array_search($chart, $this->charts);
    if ($key !== FALSE) {
      unset($this->charts[$key]);
      $chart->detach($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterSettings(array $settings) {
    $this->settings = array_merge($this->settings, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function alterData(array $data, $reset = FALSE) {
    if ($reset === TRUE) {
      $this->data = $data;
    }
    else {
      $this->data = array_merge($this->data, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

}
