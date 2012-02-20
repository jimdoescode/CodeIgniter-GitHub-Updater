<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * The user name of the git hub user who owns the repo
 */
$config['github_user'] = '';

/**
 * The repo on GitHub we will be updating from
 */
$config['github_repo'] = '';

/**
 * The branch to update from
 */
$config['github_branch'] = '';

/**
 * The current commit the files are on.
 * 
 * NOTE: You should only need to set this initially it will be
 * automatically set by the library after subsequent updates.
 */
$config['current_commit'] = '';

/**
 * A list of files to never perform an update on
 */
$config['ignored_files'] = array('application/config/config.php',
                                 'application/config/github_updater.php');

/**
 * Flag to indicate if the downloaded and extracted update files
 * should be removed
 */
$config['clean_update_files'] = true;