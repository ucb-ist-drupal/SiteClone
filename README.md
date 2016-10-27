# Terminus Plugin: site clone

## Summary
The command `terminus site clone` creates a new site which duplicates the environments, code (including git commits) and content of an existing Pantheon site. 
This command should work with any Pantheon-supported framework (Drupal or WordPress). 

## Installation
1. Copy the plugin code to `~/terminus/plugins` or the location specified by your $TERMINUS_PLUGINS_DIR. 
2. In your new SiteClone directory run `composer dump-autoload` to create the autoload.php file required by the command. 
3. In your new SiteClone directory run `cp Custom/SiteCloneCustomTrait.php.default Custom/SiteCloneCustomTrait.php`. (The ".php" version of this file is ignored in .gitignore to facilitate custom code added by users.)

## Use Cases
Why would we need to clone a site?  
 
### Moving a site between organizations
Scenario: An agency creates a site outside of our organization.  The preferred way to move the site to the desired organization is to create a new site and import backups 
of the original site. `terminus site clone` can do this in one command with the advantage of preserving git commits and environment states. 

### 'terminus upstream-updates apply' dry runs
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

## Details

### Cloning site code
This code endeavors to mirror each environment between the source site and the target (the new copy being created).  If there are pending commits in the source site's live or test 
environments, those same commits should be pending in the corresponding environments on the cloned site. 

The code uses your local `git` to clone the source and target sites to your /tmp directory.  (See note below on Windows support.)

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

## Possible improvements
Pull requests welcome.

### Terminus 1.0 support
When Terminus 1.0 is stable, this plugin will probably need to be refactored to satisfy new plugin requirements.

### Tests!
Tests should be added when/if this plugin is ported to Terminus 1.0.

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
