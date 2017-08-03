<?php

namespace Drupal\effective_activism\Chart\Axis;

use Drupal\effective_activism\Chart\ChartInterface;

/**
 * Interface for the axis class.
 */
interface AxisInterface {

  /**
   * Constructor.
   *
   * @param string $type
   *   The type of axis, such as category, line or column.
   * @param array $settings
   *   The settings for the axis.
   * @param array $data
   *   The data for the axis.
   */
  public function __construct($type, array $settings, array $data);

  /**
   * Update all charts that this axis is attached to.
   */
  public function update();

  /**
   * Returns TRUE if axis is attached to chart, FALSE otherwise.
   *
   * @param \Drupal\effective_activism\Chart\ChartInterface $chart
   *   The chart to check.
   *
   * @return bool
   *   Returns TRUE if Axis is attached.
   */
  public function isAttached(ChartInterface $chart);

  /**
   * Alter the settings of this axis.
   *
   * @param array $settings
   *   New settings for this axis.
   */
  public function alterSettings(array $settings);

  /**
   * Alter the data of this axis.
   *
   * @param array $data
   *   New data for this axis.
   *
   * @param bool $reset
   *   If TRUE, resets existing values before merging new data.
   */
  public function alterData(array $data, $reset = FALSE);

  /**
   * Get the type of the axis.
   *
   * @return int
   *   The type of the axis.
   */
  public function getType();

  /**
   * Set the type of the axis.
   *
   * @param int $type
   *   The type of the axis.
   */
  public function setType($type);

}
