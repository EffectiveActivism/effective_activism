<?php

namespace Drupal\effective_activism\Chart;

use Drupal\effective_activism\Chart\Axis\AxisInterface;

interface ChartInterface {

  /**
   * Constructor.
   *
   * @param array $settings
   *   Settings for the chart.
   */
  public function __construct(array $settings);

  /**
   * Attach an axis to the chart.
   *
   * @param \Drupal\effective_activism\Chart\Axis\AxisInterface $axis
   *   The axis to attach.
   */
  public function attach(AxisInterface $axis);

  /**
   * Detach an axis from the chart.
   *
   * @param \Drupal\effective_activism\Chart\Axis\AxisInterface $axis
   *   The axis to detach.
   */
  public function detach(AxisInterface $axis);

  /**
   * Updates the chart.
   */
  public function update();

  /**
   * Assembles and renders the chart.
   *
   * @return string
   *   The HighChart script to display the chart.
   */
  public function render();

}
