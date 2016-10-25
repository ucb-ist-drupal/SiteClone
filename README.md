# Terminus Plugin: site clone

## Summary
The command `terminus site clone` creates a new site which duplicates the environments, code and content of an existing Pantheon site.

## Installation
1. Copy the plugin code to ~/terminus/plugins or the location specified by your $TERMINUS_PLUGINS_DIR. 
2. In your new SiteClone directory run `composer dump-autoload --optimize` to create the autoload.php file required by the command. 
3. In your new SiteClone directory run `cp Custom/SiteCloneCustomTrait.php.default Custom/SiteCloneCustomTrait.php`. (The ".php" version of this file is ignored in .gitignore to facilitate custom code added by users.)

## Don't forget to disable mail on your cloned sites!

## Use Cases
Why would we need to clone a site?  
 
### Moving a site between organizations
Scenario: An agency creates a site outside of our organization.  The preferred way to move the site to the desired organization is to create a new site and import backups 
of the original site. `terminus site clone` can do this in one command with the advantage of preserving git commits and environment states. 

### 'terminus apply-updates' dry runs
The normal precautionary procedure for updating a pantheon site is to create multidev environment off of the Test or Live environment, apply code updates and run `drush updb`
in this multidev envrionment and test the site.  If tests past, push the code and content to the desired environment. 
   
In some cases it's useful to clone the site and do an update dry run with out using multidev environments.

## Details
This code endeavors to mirror each environment between the source site and the target (the new copy being created).  If there are pending commits in the live or test 
environment in the source site, those same commits should be pending in the corresponding environments on the cloned site. The database and files are imported from 
the most recent backup of the corresponding source environment. If backups are not scheduled for the source site, it's best to backup all environments before creating
the cloned site. **Feature: create backup first.**


## Possible improvements
Pull requests welcome.

### Terminus 1.0 support
When Terminus 1.0 is stable, this plugin will need to be refactored to satisfy new plugin requirements.

### Windows support
No testing has been done on Windows. Search the code for "windows".  

### Drupal8
Cloning Drupal8 sites has not been implemented.

### WordPress support
Cloning WordPress sites has not been implemented.

### Multidev environments not auto-created
Presently only the dev, test and live environments are created. Pull requests welcome. 



## Thanks
Greg Anderson: Advice on composer requirements and autoloading. 
Andrew Taylor: Brainstorming. Pantheon git tags could have been used to sync up environment commits.
