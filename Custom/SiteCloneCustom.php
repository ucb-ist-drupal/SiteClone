<?php

namespace SiteClone\Custom;

require_once __DIR__ . '/../vendor/autoload.php';

use SiteClone\Traits\SiteCloneTrait;


class SiteCloneCustom {

  use SiteCloneTrait;
  
  // Use this file to add custom code. See README.md for details.

  public function transformContent_001(\Terminus\Commands\SiteCloneCommand $command, \Terminus\Models\Site $site, $environment, $assoc_args) {
    // Add your code here.
    return TRUE;
  }

  public function transformCode_001(\Terminus\Commands\SiteCloneCommand $command, \Terminus\Models\Site $site, $environment, $assoc_args) {
    // Add your code here.
    return TRUE;
  }

}