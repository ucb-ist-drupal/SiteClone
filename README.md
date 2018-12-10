# PLEASE NOTE:  This plugin was written for Terminus 0.13.x and no longer works.
This plugin has never worked with terminus 1.x.

Until ~11/2018 this plugin worked with Terminus 0.13.x, but around then it appears that Pantheon retired or changed some API end points and it stopped working. 

Multidevs can be used for most of the same purposes that SiteClone served.  Our team has moved to a multidev workflow and we do not plan on fixing or maintaining this code.  

# Terminus Plugin: site clone

## Summary
The command `terminus site clone` creates a new site which duplicates the environments, code (including git commits) and content of an existing Pantheon site. 
This command should work with any Pantheon-supported framework (Drupal or WordPress). 

## Installation
### Install the tarball
Beginning with release 0.1.1 it should work to simply uncompress the tarball linked on the [release page](https://github.com/ucb-ist-drupal/SiteClone/releases) in `~/terminus/plugins` or the location 
specified by your $TERMINUS_PLUGINS_DIR.  You should end up with this folder structure: `~/terminus/plugins/SiteClone`

### Install using git
1. Copy the plugin code to `~/terminus/plugins` or the location specified by your $TERMINUS_PLUGINS_DIR. 
2. In your new SiteClone directory run `composer dump-autoload` to create the autoload.php file required by the command. 
3. In your new SiteClone directory run `cp Custom/SiteCloneCustomTrait.php.default Custom/SiteCloneCustomTrait.php`. (The ".php" version of this file is ignored in .gitignore to facilitate custom code added by users.)

### Verify that the command is working
`terminus help site clone` should display the help text.

`terminus site clone --version` should display the version information.

## Use Cases
Why would we need to clone a site?  
 
### Moving a site between organizations
Scenario: An agency creates a site outside of our organization.  The preferred way to move the site to the desired organization is to create a new site and import backups 
of the original site. `terminus site clone` can do this in one command with the advantage of preserving git commits and environment states. 

### 'terminus site upstream-updates apply' dry runs
The normal precautionary procedure for updating a pantheon site is to create multidev environment off of the Test or Live environment, apply code updates and run `drush updb`
in this multidev envrionment and test the site.  If tests past, push the code and content to the desired environment. 
   
In some cases it can be useful to clone the site and do an update dry run without using multidev environments.

## Don't forget to disable mail on your cloned sites!
If your site sends email on cron or other hooks, you'll want to save yourself embarrassment by disabling mail on your cloned site. See the custom transformation methods referenced below.

## How to run this
```
$ terminus site clone  --source-site=example --target-site-prefix=clone --target-site-suffix=01 

...a healthy amount of info messages...

SOURCE SITE URLs (for reference)
+-----------+------------------------------------------------------------------------------+
| Key       | Value                                                                        |
+-----------+------------------------------------------------------------------------------+
| Dashboard | https://dashboard.pantheon.io/sites/337a6c97-XXXX#dev                        |
| Dev       | http://dev-example.pantheonsite.io                                           |
| Test      | http://test-example.pantheonsite.io                                          |
| Live      | http://live-example.pantheonsite.io                                          |
+-----------+------------------------------------------------------------------------------+

TARGET SITE URLs
+-----------+------------------------------------------------------------------------------+
| Key       | Value                                                                        |
+-----------+------------------------------------------------------------------------------+
| Dashboard | https://dashboard.pantheon.io/sites/9b25bdd9-XXXXX#dev                       |
| Dev       | http://dev-clone-example-01.pantheonsite.io                                  |
| Test      | http://test-clone-example-01.pantheonsite.io                                 |
| Live      | http://live-clone-example-01.pantheonsite.io                                 |
+-----------+------------------------------------------------------------------------------+

```

Run `terminus help site clone` to see all the options.

## Issues and improvements
Post 'em in the [project issue queue](https://github.com/ucb-ist-drupal/SiteClone/issues).

## Details

### Cloning site code
This code endeavors to mirror each environment between the source site and the target (the new copy being created).  If there are pending commits in the source site's live or test 
environments, those same commits should be pending in the corresponding environments on the cloned site. 

The code uses your local `git` to clone the source and target sites to your /tmp directory. (See *Possible Improvements* below.) The target site repository is reset to 
the latest commit of the source site and source site code is merged into the target repository. **An important assumption is that the target site uses the same upstream repository
as the source site.** Only use `--target-site-upstream=<upstream>` if you really know what you're doing.

`--source-site-git-depth` allows the user to shallow-clone the source site repository when it is huge and is unlikely to have diverged from the newly created target site more than N commits ago. 
This speeds up the `git clone` steps over slower connections, but **using too shallow a depth can cause the code merge to fail**.  

### Cloning site content (database and files)
The database and files are imported from the most recent backup of the corresponding source environment. Before proceeding with the content imports, the code checks that each initialized environment
on the source site 1) has database and files backups and 2) that these backups are < 48 hours old.  If necessary, fresh backups are created in source site environments. 
The content clone function uses the backup URL to avoid downloading/reuploading large files.

`--source-site-backups` causes backups to be created in all initialized source site environments regardless of existing backups and their ages.

## Plugins for this plugin :-)
Mosts distributions are going to require some idiosyncratic mucking-about to make the cloned site work correctly. The needed customizations fall into two categories which we'll call 
**"code transformations"** and **"content transformations."** A code transformation modifies code (for example Drupal's settings.php) before the code is pushed to the site environments.
 A content transformation modifies the database that is imported to an environment to do things like disable mail delivery or modify [pathologic paths](https://www.drupal.org/project/pathologic) 
  
Users can create custom transformation methods in `Custom/SiteCloneCustomTrait.php`. This file is listed in the project's .gitignore, but could be version controlled in a separate repository and then
symbolically linked into SiteClone/Custom.

`--no-custom=transformCode_002,transformContent_001` can be used to selectively skip custom transformation methods. 

See the example transformation methods in `Custom/SiteCloneCustomTrait.php.default`.

### Drush and wp commands

If custom transformations use `doFrameworkCommand()` to issue `drush` or `wp` commands, disable SSH strict hostkey checking for Pantheon hosts 
in ~/.ssh/config. (This way you will avoid interactive confirmations from ssh):
 
```
# Pantheon containers will trigger strict host checking for "terminus drush" commands
host *.drush.in
   StrictHostKeyChecking no
```

## FAQ
### Q: Why do I sometimes see Pantheon commit warnings like the following?
```
remote: PANTHEON WARNING:
remote:
remote: Commit: A commit between 5b8c22fccc6a6167eac965f0391422a06a7e3ebf and e5e3c1e32cd30c82923399e511fc45b16c6a715b
remote: Contains changes to CHANGELOG.txt which is a part of Drupal core.
```
**A: Normally you can disregard these commit warnings.**
The 'site clone' process sometimes needs to reset a local git repository to an older commit and use `git push -f`. Doing this can trigger these warnings since our process doesn't conform to the normal Pantheon workflow.

### Q: I notice that when content is cloned to the target site it often loads the enviornments out of order.
E.g. I see it do 'test' then 'live' then 'dev.'

**A: Importing content to environments can be done in any order.**
This is not the same as deploying code to environments, which should be done the dev->test->live order.

### Q: Should I worry if I see cURL errors like these?
```
[2016-11-11 01:32:50] [info] Importing content: american-cultures dev files to upgrade-testing-0110-american-cultures-kl-01 dev files.
    source_site: 'american-cultures'
    source_env: 'dev'
    target_site: 'upgrade-testing-0110-american-cultures-kl-01'
    target_env: 'dev'
    element: 'files'
............[2016-11-11 01:33:49] [error] cURL error 7: Failed to connect to terminus.pantheon.io port 443: Operation timed out (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)
```
**A: Yes**
This indicates that the files were not completely loaded into the dev environment. This could be the result of a lapse of connectivity for the computer from which the command was run. Or it might be a temporary problem with Pantheon.
It's alway a good idea to search your output for "error." It would be great if the command could summarize errors in a future version.

### Q: What should I do about the error "File import exceeds the maximum allowed size of 1GB"?
```
[2016-11-14 18:24:53] [info] Importing content: american-cultures test files to upgrade-testing-0110-american-cultures-kl-02 test files.
    source_site: 'american-cultures'
    source_env: 'test'
    target_site: 'upgrade-testing-0110-american-cultures-kl-02'
    target_env: 'test'
    element: 'files'
....................
[2016-11-14 18:26:08] [error] File import exceeds the maximum allowed size of 1GB
```
**A: The files on the target site will be incomplete.**
If no other issues arise, the clone will work, but the incomplete files will probably result in broken images or node attachments...what have you.

No good workaround for this is known at this point.

It's interesting that > 1GB file were added to the source site in the first place.

## Possible improvements
Pull requests welcome.

### Terminus 1.0 support
When Terminus 1.0 is stable, this plugin will probably need to be refactored to satisfy new plugin requirements.

### Tests!
Tests should be added when/if this plugin is ported to Terminus 1.0.

### Probably don't need to clone the source site locally
As it turns out, we could probably merge from the remote source site repository.  This could speed things up.

### Summarize errors at end of command
Sometimes (mainly in customTransformation menthods) we use `$this->log()-error("foo")` but we don't abort the command.  It would be nice if we could summarize any existing 
errors for the user at just before we display the urls.   

### Add Windows support
A bit more work is needed to support Windows. Search the code for "windows".  

### WordPress support
Cloning WordPress sites has not been implemented.

### Automatically add team members to the cloned site
* `--source-site-team`: Cloned site should have the same team members/roles as the source site.
* `--target-site-team=<roleA:email1,email2;roleB:email1,email2>`: Specify team-members and roles. 

### Multidev environments not auto-created
Presently only the dev, test and live environments are created. Pull requests welcome.
 
### Optionally tag the cloned site
Tag with "Clone of $source_site_name". This would make it easier to filter for clones on organization dashboards.

## Thanks
* Greg Anderson: Advice on composer requirements and autoloading. 
* Andrew Taylor: Brainstorming. Pantheon git tags could have been used to sync up environment commits.

## Authors
* Brian Wood, bwood@berkeley.edu
