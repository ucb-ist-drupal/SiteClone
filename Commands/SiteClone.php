<?php


namespace Terminus\Commands;

use Terminus\Collections\Sites;
use Terminus\Commands\TerminusCommand;
use Terminus\Commands\SitesCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Session;
use Terminus\Utils;

/**
 * Class CloneSiteCommand
 * @package Terminus\Commands
 * @command site
 */
class SiteCloneCommand extends TerminusCommand {

  /**
   * Create a new site which duplicates the environments, code and content of an existing Pantheon site.
   *
   * @param array $options Options to construct the command object
   * @return CloneSiteCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = TRUE;
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
   * [--target-site-git-depth]
   * : The value to assign '--depth' when cloning. (Default: No depth. Get all commits.)
   *
   * [--tag-reset=<tag>]
   * : Tag to which the target site should be reset.
   *
   * @subcommand clone
   *
   * @param array $args Array of main arguments
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

    //Create the target site
    $this->log()->info("Creating the target site...");

    if (!$this->helpers->launch->launchSelf(
      [
        'command' => 'sites',
        'args' => ['create',],
        'assoc_args' => [
          'label' => $target_site,
          'site' => $target_site,
          'org' => $target_site_org,
          'upstream' => $target_site_upstream
        ],
      ]
    )
    ) {
      $this->log()->error("Failed to create target site.  Aborting.");
      return 1;
    }

    // Copy customizations from the source site to the target site
    $this->log()->info("Downloading site code...");

    if (array_key_exists('target-site-git-depth', $assoc_args)) {
      $depth = $assoc_args['target-site-git-depth'];
    }
    else {
      $depth = '';
    }

    foreach ([
               'source' => $assoc_args['source-site'],
               'target' => $target_site
             ] as $key => $site) {
      if ($key == 'target') {
        $this->gitCloneSite($site, $depth);
      }
      else {
        // Shallow clone the source site
        $this->gitCloneSite($site, 1);
      }
    }

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

  protected function gitCloneSite($site_name, $depth = '') {
    if (Utils\isWindows()) {
      //FIXME: need windows
      $dest_dir = 'c:\TEMP'; // ???
      $slash = '\\';
    }
    else {
      $dest_dir = '/tmp';
      $slash = '/';
    }

    $output = [];
    $return = '';

    // If the site has already been cloned, 'git pull'
    $clone_path = $dest_dir . $slash . $site_name;
    if (is_dir($clone_path . $slash . '.git')) {
      $this->log()->info("Found {clone_path}. Attempting 'git pull'.", ['clone_path' => $clone_path]);
      //FIXME: Following probably doesn't work on windows?
      $git_command = "cd $clone_path && git pull && cd -";
      exec($git_command, $output, $return);
      if ($return != 0) {
        $this->log->error("Failed to git pull {site}", ['site' => $site_name]);
        // remove it so we can attempt 'git clone'
        // FIXME: Windows.
        exec("rm -rf $clone_path");
      }
      else {
        return TRUE;
      }
    }

    $depth_option = "";
    if (!empty($depth)) {
      $depth_option = " --depth $depth";
    }

    $git_command = $this->getConnectionInfoField($site_name, "dev", "git_command");
    $git_command = preg_replace("/ $site_name\$/", " " . $dest_dir . $slash . $site_name, $git_command);
    $this->log()
      ->info("Git cloning$depth_option {site}...", ["site" => $site_name]);
    exec($git_command . $depth_option, $output, $return);

    if ($return != 0) {
      $this->log->error("Failed to clone {site}", ['site' => $site_name]);
      return FALSE;
    }

    return TRUE;
  }

  protected function getConnectionInfoField($site_name, $env, $field = "") {
    $site = $this->sites->get($site_name);
    $environment = $site->environments->get($env);
    $info = $environment->connectionInfo();

    if (!empty($field)) {
      return $info[$field];
    }

    return $info;
  }

}