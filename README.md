CodeIgniter GitHub Updater
===============================

This library is meant to handle updates to remote CodeIgniter instances. These updates 
are tied to a GitHub repository. Any time the repository is updated you can run the 
update command of this library to have your CodeIgniter files updated to the current 
version of the repository.

For more information check out my blog.
http://jimdoescode.blogspot.com/2012/02/keep-your-ci-instances-up-to-date-with.html

Usage
------
Copy the files under your application directory. 

Edit the github_updater.php config file.

Then load the library like this:

$this->load->library('github_updater');

$success = $this->github_updater->update();
		
License
-------
This library is licensed under the MIT license. 

