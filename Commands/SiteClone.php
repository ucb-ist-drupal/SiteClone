<?php


namespace Terminus\Commands;

use Terminus\Collections\Sites;
use Terminus\Commands\TerminusCommand;
use Terminus\Commands\SitesCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Session;

/**
 * Class CloneSiteCommand
 * @package Terminus\Commands
 * @command site
 */
class SiteCloneCommand extends TerminusCommand  {

  /**
   * Create a new site which duplicates the environments, code and content of an existing Pantheon site.
   *
   * @param array $options Options to construct the command object
   * @return CloneSiteCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * Create a new site which duplicates the environments, code and content of an existing Pantheon site.
   *
   * --source-site=<site>
   * : (Required) Name of the existing site to be cloned.
   *
   * [--target-site=<site>]
   * : Name of the new site which will be a copy of the source site.
   *
   * [--target-site-prefix=<prefix>]
   * : Target site name will be source site name prefixed with this string.
   *
   * [--target-site-suffix=<suffix>]
   * : Target site name will be source site name suffixed with this string.
   *
   * [--target-site-org]
   * : The organization in which to create the target site. Defaults to source site organization.
   *
   * [--target-site-upstream]
   * : The upstream repository to use for the target site. Defaults to source site upstream.
   *
   * [--tag-reset=<tag>]
   * : Tag to which the target site should be reset.
   *
   * @subcommand clone
   *
   * @param array $args       Array of main arguments
   * @param array $assoc_args Array of associative arguments
   *
   * @return null
   */
  public function siteClone($args, $assoc_args) {
    //$this->output()->outputDump($assoc_args);
    //$this->input()->siteName(['args' => $assoc_args,])

    // Validate source site
    // TODO: Use site->fetch()? To prevent aborting so you can give the user a msg?  See sites->get().
    $source_site = $this->sites->get($assoc_args['source-site']);

    // Set target site name
    $target_site = $this->targetSiteName($assoc_args);

    // Set target site upstream
    if (array_key_exists('target-site-upstream', $assoc_args)) {
      $target_site_upstream = $assoc_args['target-site-upstream'];
    }
    else {
      $target_site_upstream = $source_site->upstream->id;
    }

    // Set target site org
    if (array_key_exists('target-site-org', $assoc_args)) {
      $target_site_org = $assoc_args['target-site-org'];
    }
    else {
      $target_site_org = $source_site->get('organization');
    }


    $create_args = [
      'label' => $target_site,
      'site' => $target_site,
      'org' => $target_site_org,
      'upstream' => $target_site_upstream
    ];

    $this->helpers->launch->launchSelf(
      [
        'command'    => 'sites',
        'args'       => ['create',],
        'assoc_args' => $create_args,
      ]
    );

  }

  /**
   * Generate target site name.
   *
   * @param $assoc_args
   * @return string
   */
  protected function targetSiteName($assoc_args) {
    if (array_key_exists('target-site', $assoc_args)) {
      return $assoc_args['target-site'];
    }

    $target_site = $assoc_args['source-site'];

    if (array_key_exists('target-site-prefix', $assoc_args)) {
      $target_site = $assoc_args['target-site-prefix'] . '-' . $target_site;
    }

    if (array_key_exists('target-site-suffix', $assoc_args)) {
      $target_site .= '-' . $assoc_args['target-site-suffix'];
    }

    return $target_site;
  }

}