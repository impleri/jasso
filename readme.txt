=== JaSSO ===

Joomla Single Sign-On for Kayako V4

**WARNING**: This project is still in development and is currently not ready for production use. Please install at your own risk.

Once placed within the Kayako script directory, this creates an authorisation script which will allow a single sign-on between Kayako and Joomla. While Kayako's LoginShare already has the ability for one-way addition (from Joomla to Kayako in our case), this extends the LoginShare functionality by providing the second direction (from Kayako to Joomla) and synchronisation between the two. Additionally, this provides a wrapper script which will automatically log a user into Kayako when the user visits the Kayako site and is logged into Joomla (to minimise the frequency of a second login) by sharing the session information between Joomla and Kayako. All of this is accomplished without editing any files in Joomla or Kayako, thereby maintaining their independence and integrity.

Requires Joomla 1.6 or greater.

== Installation ==

1. Edit /jasso/joomla.php and set JPATH_BASE to your Joomla installation (e.g. /home/USER/public_html/joomla/)
3. Upload the /jasso/ and /__swift/ directories to your Kayako root directory (e.g. /home/USER/public_html/kayako/)
4. Set up Kayako to use http://example.com/kayako/jasso/auth.php for the LoginShare URL
5. Optionally, set up the Staff LoginShare to use http://example.com/kayako/jasso/auth.php?site=staff
6. Optionally, install the Joomla extension (jasso.zip) to keep Kayako user data in sync with Joomla's (options are available in the Joomla admin area)

== Changelog ==

= 0.8.7 =
* Second beta release
* Login through Kayako works and syncs users in Joomla and Kayako
* Added authentication plugin for Joomla to allow logins by email address

= 0.8.5 =
* First beta release
* Autologin hook into Kayako
* Added Community Builder

= 0.8.0 =
* Public alpha release
* Basic Joomla authentication
