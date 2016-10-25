# Terminus Plugin: site clone

## Summary
The command `terminus site clone` creates a new site which duplicates the environments, code and content of an existing Pantheon site.

## Installation
1. Copy the plugin code to ~/terminus/plugins or the location specified by your $TERMINUS_PLUGINS_DIR. 
2. In your new SiteClone directory run `composer dump-autoload --optimize` to create the autoload.php file required by the command. 
3. In your new SiteClone directory run `cp Custom/SiteCloneCustomTrait.php.default Custom/SiteCloneCustomTrait.php`. (The ".php" version of this file is ignored in .gitignore to facilitate custom code added by users.)

## Don't forget to disable mail on your cloned sites!

## Use Cases

## Details
This process relies heavily on git.  The code execs git commands.  


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
