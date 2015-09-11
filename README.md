# AVH Event Manager Permalinks

## Introduction

This plugin is to be used in conjunction with the WordPress plugin "[Events Manager](http://wp-events-plugin.com/)", 
referenced as EM in this document.  

EM lacks support for custom permalink for events. This plugin will take care of this.  

## Requirements
- WordPress 4.1 or higher
- PHP 5.4 or higher
- WordPress plugin Event Manager 5.5.5 or higher

# ATTENTION

Due to time restraints I didn't test the plugin completely and won't be able to test untill mid April 2015. I just wanted 
to put it out here in case there are adventourus people out there that want to help out.
If things don't work, just delete the plugin and revert any changes to EM setting you made and you're back to normal.

## Usage
After you install and activate this plugin you need to change the permalink setting in EM. The structure tags look 
similar as the structure tags in WordPress. Also make sure you end your permalink with the *name* tag. Check out the 
[Implemented structure tags](#implemented-structure-tags) to see what tags are available.

## Important
Currently this plugin is in alpha, things might break and they probably will.  
This plugin does not cover every possibility imaginable when it comes to extending the permalinks.  
I implemented structure tags for the permalinks that I needed at the time I wrote this plugin, March 31, 2015.  

## Requests
If you want a structure tag added to this plugin there are two options:  
1. You fork this repository, create the code needed to support the new structure tag and do a PR.  
2. You create an issue.  

## Notes
- When creating an issue be aware that not all request are possible, for example a permalink with multiple categories is 
just not possible. It's not possible in WordPress itself, and won't be possible in this plugin.    
- Permalinks for categories and tags always end in the category/tag slug. This won't be changed, as this is the same 
in WordPress itself.  

## Implemented structure tags

### Events
- '%event_year%'
- '%event_monthnum%'
- '%event_monthname_short%'
- '%event_monthname_long%'
- '%event_day%'
- '%event_name%'
- '%event_owner%'
- '%event_location%'

### Locations
- %location_name%
