<?php

namespace Drupal\effective_activism\Controller;

use Drupal;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the Facebook web hook.
 */
class FacebookWebHookController extends ControllerBase {

  /**
   * Returns a render array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   A render array.
   */
  public function content(Request $request) {
    $verifyToken = Drupal::config('effective_activism.settings')->get('facebook_verify_token');
    $appSecret = Drupal::config('effective_activism.settings')->get('facebook_app_secret');
    $hubChallenge = (int) $request->query->get('hub_challenge');
    $hubVerityToken = $request->get('hub_verify_token');
    $response = new HtmlResponse();
    if (!isset($verifyToken) || !isset($appSecret)) {
      $response
        ->setContent('missing setting vars')
        ->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
      return $response;
    }
    elseif ($hubVerityToken === $verifyToken) {
      $response
        ->setContent($hubChallenge)
        ->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
      Drupal::logger('effective_activism')->debug('Verify matched');
      Drupal::logger('effective_activism')->debug(json_encode($request->query->all()));
      return $response;
    }
    elseif (!empty($request->headers->get('X-Hub-Signature'))) {
      $signature = $request->headers->get('X-Hub-Signature');
      if ($signature === sha1($request->getContent() . $appSecret)) {
        // Signature match, look up user facebook id and afa group and add accordingly.
        Drupal::logger('effective_activism')->debug('Signature matched');
        Drupal::logger('effective_activism')->debug($request->getContent());
      }
      else {
        Drupal::logger('effective_activism')->debug('Signature did not match');
        Drupal::logger('effective_activism')->debug($request->getContent());
      }
    }
    else {
      $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
      Drupal::logger('effective_activism')->notice('Verify did not match and/or signature was not found');
      Drupal::logger('effective_activism')->notice(json_encode($request->request->all()));
      return $response;
    }
  }

}
