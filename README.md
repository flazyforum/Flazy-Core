## Flazy  
======
We are starting the project again. New design and everything. Just stay in touch.

Index
-----

* [Description](#description)
* [Installation](#installation)
* [Features](#features)
* [Compatibility](#compatibility)
* [Upgrading](#upgrading)
* [Frequently asked questions](#frequently-asked-questions)
* [License](#license)
* [Acknowledgements](#acknowledgements)
* [Translating](#translating)

Description
-----------
 We are making the next generation in open-source forum software. A web application not bloated with superfluous features or complicated configuration settings. Flazy is a simple yet powerful tool — everything you need to use in a simple forum software. Flazy can be used for all kind of forums and websites big and small. Integrating with your website can't be easier than this. You can find all kind of tutorials for tunning in our wiki. And the best thing is that you can do everything free of charge. (Free as in free beer.)

Installation
--------
1. Download the latest version of Flazy
2. Upload or copy all the files in the directory where you want to run your forum (example: /home/user/www/flazy).
3. Open the file install.php with your browser (example:http://example.com/flazy/install.php)
4. Follow the instructions about the installation.


Features
--------
Forum
* Unlimited Forums 
* Create topics or polls
* Reply to topics, or quote previous posts
* Post up and down ratings
* Topic and forum subscription with email notifications

Moderation
* Still in the making

Administration
* Still in the making

Security
* Still in the making

Miscellaneous
* RSS feeds for each forum
* Search through topics (Title and Post)
* Report system for topics, posts and users
* BBCode support using Decoda
* Mark topics as read (Session)
* Log created topics and posts (Session)



Compatibility
-------------
* Web-server (Apache is recommended)
* PHP 5.6 and newer versions (PHP 7.1 included)
* Database: MYSQL 4.1.2 and newer versions. PostgreSQL 7.0 and newer versions or SQLite 2 (SQLite 3 still dosn't work properly).

Upgrading
---------

* **Make a backup (That measn a backup of your files and your database.)** 
* **Do not remove the previous version** (or else your settings will maybe get lost)
* Download the new version
* Open your forum
* Update the database if it needs it
* Done

Frequently asked questions
--------------------------

Having problems with Flazy ? Check out the [FAQ](https://github.com/flazy-us/Flazy-Forum-Stable/wiki/FAQ) before reporting a bug or problem that may already be known.

License
-------

Flazy  is under the [GNU General Public License v2.0 License](https://github.com/flazy-us/Flazy-Forum-Beta/blob/master/LICENSE).

Acknowledgements
----------------

This project also uses many other open source libraries such as:

<table>
    <tr>
        <td><strong>Project</strong></td>
        <td><strong>License</strong></td>
        <td><strong>Website</strong></td>
    </tr>
    <tr>
        <td>Font Awesome</td>
        <td>The MIT License</td>
        <td>http://fontawesome.io/</td>
    </tr>	

</table>


Translating
-----------
The res/values-* dirs are kept up to date automatically via Crowdin Translate Extension. See [our translation page](https://crowdin.com/project/flazy-us) if you would like to contribute.

The application is available in many languages, but if yours is not included, or if it needs updating or improving, please create an account and use the translation system (powered by Crowdin Translate Extension) and make your changes.
