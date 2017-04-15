<?php

namespace Drupal\effective_activism\Hook;

/**
 * Provides an interface for hook classes.
 *
 * @ingroup effective_activism
 */
interface HookInterface {

  /**
   * Return an instance of this class.
   *
   * @return HookImplementation
   *   Instance of this class.
   */
  public static function getInstance();

  /**
   * Invokes the hook.
   *
   * @param array $args
   *   The arguments of the hook.
   */
  public function invoke(array $args);

}
