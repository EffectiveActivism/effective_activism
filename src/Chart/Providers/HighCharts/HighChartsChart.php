<?php

namespace Drupal\effective_activism\Chart\Providers\HighCharts;

use Drupal\effective_activism\Chart\Chart;
use Drupal\effective_activism\Chart\ChartInterface;

class HighChartsChart extends Chart implements ChartInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $x_axes = [];
    foreach ($this->getCategoryAxes() as $category_axis) {
      $x_axes[] = (object) array_merge(['categories' => $category_axis->getData()], $category_axis->getSettings());
    }
    $y_axes = [];
    $series = [];
    foreach ($this->getDataAxes() as $data_axis) {
      $y_axes[] = (object) $data_axis->getSettings();
      $series[] = (object) array_merge($data_axis->getSettings(), ['data' => $data_axis->getData()]);
    }
    return (object) array_merge($this->getSettings(), [
      'xAxis' => $x_axes,
      'yAxis' => $y_axes,
      'series' => $series,
    ]);
  }
}
