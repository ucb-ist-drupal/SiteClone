<?php


namespace SiteClone\Traits;

use Terminus\Commands;
use Terminus\Exceptions\TerminusException;

trait SiteCloneTrait {

  private function doTerminusDrush(\Terminus\Models\Site $site, $environment, $command) {
    $environment = $site->environments->get($environment);
    $result = $environment->sendCommandViaSsh($command);

    return $result;
  }

  public function doFrameworkCommand(\Terminus\Models\Site $site, $environment, $command) {
    $framework = $site->get('framework');

    if ($framework == 'drupal') {
      return $this->doTerminusDrush($site, $environment, $command);
    }
    else {
      throw new TerminusException("execFrameworkCommnd not implemented for {cms}.", ['cms' => $framework]);
    }
  }

}