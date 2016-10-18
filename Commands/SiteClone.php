<?php


namespace Terminus\Commands;

use Terminus\Collections\Sites;
use Terminus\Collections\TerminusCollection;
use Terminus\Commands\TerminusCommand;
use Terminus\Commands\SitesCommand;
use Terminus\Commands\SiteCommand;
use Terminus\Config;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Environment;
use Terminus\Runner;
use Terminus\Session;
use Terminus\Utils;

/**
 * Class CloneSiteCommand
 * @package Terminus\Commands
 * @command site
 */
class SiteCloneCommand extends TerminusCommand {

  protected $sites;
  protected $clone_path;
  protected $slash;
  //not used
  protected $oldpwd_command;

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

    if (Utils\isWindows()) {
      //FIXME: need windows to test
      $this->clone_path = 'c:\TEMP'; // ???
      $this->slash = '\\\\';
      $this->oldpwd_command = 'cd /d %OLDPWD%';
    }
    else {
      $this->clone_path = '/tmp';
      $this->slash = '/';
      $this->oldpwd_command = "cd -";
    }

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
   * [--target-site-org=<organization>]
   * : The organization in which to create the target site. Defaults to source site organization.
   *
   * [--target-site-upstream=<upstream>]
   * : The upstream repository (ID or name) to use for the target site. Defaults to source site upstream.
   *
   * [--target-site-git-depth=<number>]
   * : The value to assign '--depth' when git cloning. (Default: No depth. Get all commits.)
   *
   * [--source-site-git-depth=<number>]
   * : The value to assign '--depth' when git cloning. (Default: No depth. Get all commits.)
   *
   * [--git-reset-tag=<tag>]
   * : Tag to which the target site should be reset.
   *
   * [--no-disable-smtp]
   * : Do not disable SMTP. Usually it's best to ensure that no mail can be sent from a cloned site.
   *
   * [--no-remove-emails]
   * : Do not remove all user emails. Usually it's best to ensure that no mail can be sent from a cloned site.
   *
   * [--cms-version=<version>]
   * : Version number for CMS.  Should be dot-separated. Left-most number should be the major version.
   *
   * @subcommand clone
   *
   * @param array $args Array of main arguments
   * @param array $assoc_args Array of associative arguments
   *
   * @return null
   */
  public function siteClone($args, $assoc_args) {
    //TODO: Make sure there is a 'git' command.

    //TODO: Validate --cms

    // Validate source site
    // TODO: Use site->fetch()? To prevent aborting so you can give the user a msg?  See sites->get().
    $source_site = $this->sites->get($assoc_args['source-site']);

    $source_site_environments_info = $this->getEnvironmentsInfo($source_site);
    //$source_site_environments = array_column($source_site_environments_info, "id");
    $source_site_environments = array_column($source_site_environments_info, "initialized", "id");

    // Deploy code to the same envinornment as the source site.
    //FIXME: Needed?
    if ($source_site_environments['live'] == "true") {
      $target_site_environment = 'live';
    }
    elseif ($source_site_environments['test'] == "true") {
      $target_site_environment = 'test';
    }
    else {
      $target_site_environment = 'dev';
    }


    // Prompt for required options
    /*
    if (!isset($assoc_args['cms-version'])) {
      $assoc_args['cms-version'] = $this->input()->prompt(
        array('message' => 'Version number for CMS.  Should be dot-separated. Left-most number should be the major version')
      );

    }
    */

    // Set site names
    $source_site_name = $assoc_args['source-site'];
    $target_site_name = $this->targetSiteName($assoc_args);

    $source_clone_path = $this->clone_path . DIRECTORY_SEPARATOR . $source_site_name;
    $target_clone_path = $this->clone_path . DIRECTORY_SEPARATOR . $target_site_name;

    /*
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

            if ($this->helpers->launch->launchSelf(
                [
                  'command' => 'sites',
                  'args' => ['create',],
                  'assoc_args' => [
                    'label' => $target_site_name,
                    'site' => $target_site_name,
                    'org' => $target_site_org,
                    'upstream' => $target_site_upstream
                  ],
                ]
              ) != 0
            ) {
              $this->log()->error("Failed to create target site.  Aborting.");
              return 1;
            }

            // Make sure the new site is in git mode.
    */
            $target_site = $this->sites->get($target_site_name);
    /*
            $this->setConnectionMode($target_site, "git");


            // Git clone the code for the source and target sites
            $this->log()->info("Downloading site code...");

            foreach ([
                       'source' => $source_site_name,
                       'target' => $target_site_name
                     ] as $key => $site) {

              if (array_key_exists($key . '-site-git-depth', $assoc_args)) {
                $depth = $assoc_args[$key . '-site-git-depth'];
              }
              else {
                $depth = '';
              }

              $this->gitCloneSite($site, $depth);
            }

            $this->log()
              ->info("Merging {source} code into {target}", [
                'source' => $source_site_name,
                'target' => $target_site_name
              ]);

            if (!$this->doExec("cd $target_clone_path && git pull ../$source_site_name --no-squash")) {
              throw new TerminusException("Failed to merge {source} code into {target}", [
                'source' => $source_site_name,
                'target' => $target_site_name
              ]);
            }

            $output = [];
            if (!$this->doExec("cd $source_clone_path && git log --pretty=format:%h -1", "", $output)) {
              throw new TerminusException("Failed to find latest commit for {source} repository", ['source' => $this->clone_path . DIRECTORY_SEPARATOR . $source_site_name]);
            }

            if (!$this->gitResetRepository($target_clone_path, $output[0])) {
              throw new TerminusException("Failed to reset {target} reposistory to latest commit.", ['target' => $this->clone_path . DIRECTORY_SEPARATOR . $target_site_name]);
            }


            // Find undeployed commits for each environment.
            foreach ($source_site_environments as $environment => $initialized) {
              if (($environment == "dev") || ($initialized != "true")) {
                continue;
              }
              $env = $this->getEnvironment($source_site, $environment);
              $deployable_commits[$environment] = $env->countDeployableCommits();
            }

            // Throws an error if there is a failure.
            $this->recreateEnvironmentCode($target_site_name, $deployable_commits);
         */

    reset($source_site_environments);
    $message_once = TRUE;
    foreach ($source_site_environments as $environment => $initialized) {
      if ($initialized != "true") {
        continue;
      }
      /*
      $this->loadContentFromBackup($source_site, $environment, $target_site_name, $environment, [
        'database',
        'files'
      ]);
      */
      if (!isset($assoc_args['no-disable-smtp'])) {
        if ($message_once) {
          $this->log()->info("Disabling smtp_host on target site.");
          $message_once = FALSE;
        }

        // smtp_host is used by smtp module.  lazily not checking if that module exists.
        // not sure if there is a fallback to localhost smtp if smtp_host is unset or null...
        $this->doFrameworkCommand($target_site, $environment, "drush vset smtp_host 'NOEMAIL-FROM-CLONED-SITE.example.com'");
      }

      if (!isset($assoc_args['no-remove-emails'])) {
        if ($message_once) {
          $this->log()->info("Removing emails from users on target site.");
          $message_once = FALSE;
        }

        // now there's no way a user could get an auto-generated email.
        $this->doFrameworkCommand($target_site, $environment, "drush sqlq \"update users set mail = '' where uid <> 0\"");
      }
    }
    /*
    // Reset the target repository to a tag if requested
    if (array_key_exists('git-reset-tag', $assoc_args)) {
      if (!$this->resetGitRepositoryToTag($target_clone_path, $assoc_args['git-reset-tag'])) {
        $message = "Failed to set repo at {clone_path} to {tag}";
        $replacements = [
          'clone_path' => $target_clone_path,
          'tag' => $assoc_args['git-reset-tag']
        ];
        throw new TerminusException($message, $replacements, 1);
      }
    }

    // Copy contributed code from the source site to the target site
    $this->log()->info("Copying contributed code source site -> target site...");
    $cms = $source_site->get('framework');
    $cms_version = $this->getCmsVersion($cms, $source_site);
    if (($cms !== FALSE) && ($cms_version !== FALSE)) {
      if ($this->copyContribCode($cms, $cms_version, $source_site_name, $target_site_name)) {
        // Commit and push the new code.
        $this->log()->info("Pushing contributed code from {source} to {target}'s DEV environment.", ['source' => $source_site_name, 'target' => $target_site_name]);

        $commit_message = "Adding contributed code from $source_site_name.";
        if (!$this->gitAddCommitPush($this->clone_path . DIRECTORY_SEPARATOR . $target_site_name, $commit_message)) {
          throw new TerminusException("Failed to push code to {site}", ["site" => $target_site_name]);
        }
      }
    }

*/

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

    // If the site has already been cloned, 'git pull'
    $clone_path = $this->clone_path . DIRECTORY_SEPARATOR . $site_name;
    if (is_dir($clone_path . DIRECTORY_SEPARATOR . '.git')) {
      $this->log()
        ->info("Found {clone_path}. Attempting 'git pull'.", ['clone_path' => $clone_path]);

      if (!$this->doExec("cd $clone_path && git pull")) {
        $this->log->error("Failed to git pull {site}", ['site' => $site_name]);
        // remove it so we can attempt 'git clone'
        // FIXME: Windows.
        $this->doExec("rm -rf $clone_path");
      }
      else {
        return TRUE;
      }
    }

    $depth_option = "";
    if (!empty($depth)) {
      $depth_option = " --depth $depth";
    }

    $git_command = $this->getConnectionInfo($site_name, "dev", "git_command");
    $git_command = preg_replace("/ $site_name\$/", " " . $this->clone_path . $this->slash . $site_name, $git_command);
    $this->log()
      ->info("Git cloning$depth_option {site}...", ["site" => $site_name]);

    if ($this->doExec($git_command . $depth_option)) {
      $this->log()->error("Failed to clone {site}", ['site' => $site_name]);
      return FALSE;
    }

    return TRUE;
  }

  protected function getConnectionInfo($site_name, $env, $field = "") {
    //FIXME: require passing the site object as in setConnectionMode. This way we don't hit the API multiple times.
    $site = $this->sites->get($site_name);
    $environment = $site->environments->get($env);
    $info = $environment->connectionInfo();

    if (!empty($field)) {
      return $info[$field];
    }
    else {
      return FALSE;
    }

    return $info;
  }

  protected function getEnvironment(\Terminus\Models\Site $site, $env) {
    return $site->environments->get($env);
  }

  protected function getEnvironmentsInfo(\Terminus\Models\Site $site) {
    $env = $site->environments->all();
    $data = array_map(
      function ($env) {
        return $env->serialize();
      },
      $site->environments->all()
    );
    return $data;
  }

  protected function recreateEnvironmentCode($site_name, array $env_deployable_commits) {

    $clone_path = $this->clone_path . DIRECTORY_SEPARATOR . $site_name;
    //FIXME: Is there a programmatic way of determining the name of the command?
    $deploy_note = "Deployed by 'terminus site clone'";

    // If the source site has not initialized test nor live environments.
    if (!array_key_exists('live', $env_deployable_commits) && !array_key_exists('test', $env_deployable_commits)) {
      if (!$this->doExec("cd $clone_path && git push -f", TRUE)) {
        throw new TerminusException("Failed to recreate code for dev environment.");
      }

      return TRUE;
    }

    // If there are no pending commits in test or live
    if (array_key_exists('live', $env_deployable_commits) && array_key_exists('test', $env_deployable_commits) &&
      ($env_deployable_commits['live'] == 0) && ($env_deployable_commits['test'] == 0)
    ) {
      // we can deploy the dev code all the way to live
      if (!($this->doExec("cd $clone_path && git push -f", TRUE) &&
        $this->deployToEnvironment($site_name, "live", $deploy_note)
      )
      ) {
        throw new TerminusException("Failed to recreate code for live environment.");
      }

      return TRUE;
    }

    // We'll do some merges to create the code for test and/or live. First make a copy of the repo's original state.
    if (!($this->doExec("cd $clone_path && git checkout -b original", TRUE) &&
      $this->doExec("cd $clone_path && git checkout master", TRUE))
    ) {
      throw new TerminusException("Failed to create branch with original copy of code.");
    }


    // Do Live...
    if (array_key_exists('live', $env_deployable_commits) && $env_deployable_commits['live'] > 0) {
      // git reset HEAD~0 returns exit status of 0
      $number_commits = $env_deployable_commits['live'] + $env_deployable_commits['test'];
      if (!($this->doExec("cd $clone_path && git reset --hard HEAD~$number_commits", TRUE) &&
        $this->doExec("cd $clone_path && git push -f", TRUE) &&
        //deploy to test and live
        $this->deployToEnvironment($site_name, "live", $deploy_note)
      )
      ) {
        throw new TerminusException("Failed to recreate code for live environment.");
      }

      // reset master branch to the copy we created above.
      if (!$this->doExec("cd $clone_path && git merge original", TRUE)) {
        throw new TerminusException("Failed to reset dev to original state.");
      }

    }

    // ...then do Test...
    if (array_key_exists('test', $env_deployable_commits) && $env_deployable_commits['test'] > 0) {

      if (!($this->doExec("cd $clone_path && git reset --hard HEAD~" . $env_deployable_commits['test'], TRUE) &&
        $this->doExec("cd $clone_path && git push -f", TRUE)
      )
      ) {
        throw new TerminusException("Failed to recreate code for test environment.");
      }

      if ($env_deployable_commits['live'] == 0) {
        // The live environment hasn't been created yet
        $deploy_to = 'live';
      }
      else {
        $deploy_to = 'test';
      }

      if (!$this->deployToEnvironment($site_name, $deploy_to, $deploy_note)) {
        throw new TerminusException("Failed to deploy to {env}.", ['env' => $deploy_to]);
      }

      // reset master branch to the copy we created above because dev has commits that are pending in test.
      if (!$this->doExec("cd $clone_path && git merge original", TRUE)) {
        throw new TerminusException("Failed to reset dev to orignal state.");
      }

      // push commits to dev that are pending in test
      if (!$this->doExec("cd $clone_path && git push -f", TRUE)
      ) {
        throw new TerminusException("Failed to push commit to dev environment.");
      }

    }
    else {
      // No pending commits in Test -- Test is at the same commit as dev
      if (!($this->doExec("cd $clone_path && git push -f", TRUE) &&
        // push commits to dev and test
        $this->deployToEnvironment($site_name, "test", $deploy_note)
      )
      ) {
        throw new TerminusException("Failed to recreate code for test environment.");
      }
    }

    return TRUE;
  }

  protected function loadContentFromBackup(\Terminus\Models\Site $source_site, $source_env, $target_site_name, $target_env, array $elements = [
    'code',
    'database',
    'files'
  ]) {

    $source_site_name = $source_site->get('name');

    foreach ($elements as $element) {
      $message_context = [
        'source_site' => $source_site_name,
        'source_env' => $source_env,
        'target_site' => $target_site_name,
        'target_env' => $target_env,
        'element' => $element
      ];
      $url = $this->getLatestBackupUrl($source_site, $source_env, $element);

      $this->log()->info("Importing content: {source_site} {source_env} {element} to {target_site} {target_env} {element}.", $message_context);

      if ($this->helpers->launch->launchSelf(
          [
            'command' => 'site',
            'args' => ['import-content'],
            'assoc_args' => [
              'site' => $target_site_name,
              'env' => $target_env,
              'url' => $url,
              'element' => $element
            ],
          ]
        ) != 0
      ) {
        $this->log()->error("Failed to import {source_site} {source_env} {element} to {target_site} {target_env} {element}.", $message_context);
        return FALSE;
      }
    }
  }


  protected function getLatestBackupUrl(\Terminus\Models\Site $site, $env, $element) {
    $backups = $this->getBackups($site, $env, $element);
    $latest_backup = array_shift($backups);
    return $latest_backup->getUrl();
  }

  protected function getBackups(\Terminus\Models\Site $site, $env, $element) {
    //FIXME: require passing the env object to save API calls
    $env = $site->environments->get($env);
    $backups = $env->backups->getFinishedBackups($element);

    return $backups;
  }

  protected function setConnectionMode(\Terminus\Models\Site $site, $mode, $env = "dev") {
    //FIXME: require passing the env object to save API calls
    $environment = $site->environments->get($env);
    $workflow = $environment->changeConnectionMode($mode);
    if (is_string($workflow)) {
      $this->log()->info($workflow);
    }
    else {
      $workflow->wait();
      $this->workflowOutput($workflow);
    }
  }

  protected function deployToEnvironment($site, $to_env, $note) {
    if ($to_env == 'dev') {
      return FALSE;
    }
    elseif ($to_env == 'test') {
      $envs = ['test'];
    }
    else {
      $envs = ['test', 'live'];
    }

    foreach ($envs as $env) {
      $this->log()->info("Deploying {site} code to {env}.", [
        'site' => $site,
        'env' => $env
      ]);

      if ($this->helpers->launch->launchSelf(
          [
            'command' => 'site',
            'args' => ['deploy'],
            'assoc_args' => [
              'site' => $site,
              'env' => $env,
              'note' => $note
            ],
          ]
        ) != 0
      ) {
        $this->log()
          ->error("Failed to deploy {site} to {env}.", [
            'site' => $site,
            'env' => $to_env
          ]);
        return FALSE;
      }
    }

    return TRUE;
  }

  protected function gitResetRepository($clone_path, $sha) {
    if (!$this->doExec("cd $clone_path && git reset --hard $sha", FALSE)) {
      return FALSE;
    }

    return TRUE;
  }

  protected function resetGitRepositoryToTag($clone_path, $tag) {
    $output = [];

    if (!$this->doExec("cd $clone_path && git rev-list -n 1 $tag", TRUE, $output)) {
      return FALSE;
    }

    $sha = $output[0];

    if (!($this->gitResetRepository($clone_path, $sha) &&
      $this->doExec("cd $clone_path && git push -f", FALSE))
    ) {
      return FALSE;
    }

    return TRUE;
  }

  protected function gitAddCommitPush($clone_path, $commit_message = "") {
    if (!($this->doExec("cd $clone_path && git add -A", TRUE) &&
      $this->doExec("cd $clone_path && git commit -m \"$commit_message\"", TRUE) &&
      $this->doExec("cd $clone_path && git push origin master", TRUE))
    ) {
      return FALSE;
    }

    return TRUE;
  }


  protected function copyContribCode($cms, $version, $source_site_name, $target_site_name) {

    $code_was_copied = FALSE;
    $parts = explode('.', $version);
    $major_version = array_shift($parts);

    if (($cms = "drupal") && ($major_version < 8) && ($major_version > 4)) {
      $source_contrib_dir = $this->clone_path . DIRECTORY_SEPARATOR . $source_site_name . DIRECTORY_SEPARATOR . "sites" . DIRECTORY_SEPARATOR . "all";
      $target_contrib_dir = $this->clone_path . DIRECTORY_SEPARATOR . $target_site_name . DIRECTORY_SEPARATOR . "sites" . DIRECTORY_SEPARATOR . "all";

      if ($handle = opendir($source_contrib_dir)) {

        while (FALSE !== ($item = readdir($handle))) {
          if (($item == '.') || ($item == '..')) {
            continue;
          }

          if (is_dir($source_contrib_dir . DIRECTORY_SEPARATOR . $item)) {
            $result = $this->copyDirectoryRecursively($source_contrib_dir . DIRECTORY_SEPARATOR . $item, $target_contrib_dir . DIRECTORY_SEPARATOR . $item);

            // If $code_was_copied becomes TRUE don't set it back to FALSE.
            if (!$code_was_copied && $result) {
              $code_was_copied = TRUE;
            }
          }
        }

        closedir($handle);
      }
    }
    else {
      throw new TerminusException("copyContribCode not implemented for {cms} version {version}.", [
        'cms' => $cms,
        'version' => $version
      ]);
    }

    return $code_was_copied;
  }

  protected function getCmsVersion($cms, $source_site) {
    if ($cms == "drupal") {
      // drush command
      $result = $this->doFrameworkCommand($source_site, "dev", "drush st --format=json");
      if ($result['exit_code'] != 0) {
        $this->log()
          ->error("Failed to determine site's {cms} version.", ["cms" => $cms]);
        return FALSE;
      }
      else {
        $data = json_decode($result['output']);
        // deal with a hyphenated property name.
        return $data->{'drupal-version'};
      }
    }
    else {
      throw new TerminusException("getCmsVersion not implemented for {cms}.", ['cms' => $cms]);
    }

  }

  //TODO: Methods south of here should be in a utilities class
  protected function doExec($cmd, $verbose = FALSE, &$output = []) {
    $exit = '';

    if ($verbose) {
      $this->log()->info("", ["Exec" => $cmd]);
    }

    exec($cmd, $output, $exit);

    if ($exit != 0) {
      if ($verbose) {
        $this->log()
          ->error("", ["Output" => implode("\n", $output)]);
      }
      return FALSE;
    }

    if (($verbose) && (count($output))) {
      $this->log()
        ->info("", ["Output" => implode("\n", $output)]);
    }

    return TRUE;
  }

  protected function doTerminusDrush(\Terminus\Models\Site $site, $environment, $command) {
    $this->environment = $site->environments->get($environment);
    $result = $this->environment->sendCommandViaSsh($command);

    return $result;
  }

  protected function doFrameworkCommand(\Terminus\Models\Site $site, $environment, $command) {
    if ($site->get('framework') == 'drupal') {
      return $this->doTerminusDrush($site, $environment, $command);
    }
    else {
      throw new TerminusException("execFrameworkCommnd not implemented for {cms}.", ['cms' => $cms]);
    }
  }

  //TODO: Should be in a utilities class
  protected function copyDirectoryRecursively($source, $dest) {

    $code_was_copied = FALSE;

    foreach (
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST) as $item
    ) {
      if ($item->isDir()) {
        $subpath = $iterator->getSubPathName();
        $target_dir = $dest . DIRECTORY_SEPARATOR . $subpath;
        if (!is_dir($target_dir)) {
          mkdir($target_dir);
          $code_was_copied = TRUE;
        }
      }
      else {
        $subpath = $iterator->getSubPathName();
        $target_file = $dest . DIRECTORY_SEPARATOR . $subpath;
        if (!is_file($target_file)) {
          copy($item, $target_file);
          $code_was_copied = TRUE;
        }
      }
    }

    return $code_was_copied;
  }

}
