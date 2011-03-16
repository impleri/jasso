=== JaSSO ===

Joomla Single Sign-On for Kayako V4

**WARNING**: This project is still in development and is currently not ready for production use. Please install at your own risk.

Once placed within the Kayako script directory, this creates an authorisation script which will allow a single sign-on between Kayako and Joomla. While Kayako's LoginShare already has the ability for one-way addition (from Joomla to Kayako in our case), this extends the LoginShare functionality by providing the second direction (from Kayako to Joomla) and synchronisation between the two. Additionally, this provides a wrapper script which will automatically log a user into Kayako when the user visits the Kayako site and is logged into Joomla (to minimise the frequency of a second login) by sharing the session information between Joomla and Kayako. All of this is accomplished without editing any files in Joomla or Kayako, thereby maintaining their independence and integrity.

== Installation ==

1. Edit joomla.php and set JPATH_BASE to your Joomla installation (e.g. /home/USER/public_html/joomla/)
3. Upload the jasso directory to your Kayako root directory (e.g. /home/USER/public_html/kayako/)
4. Set up Kayako to use http://example.com/kayako/jasso/auth.php for the LoginShare URL
5. Optionally, set up the Staff LoginShare to use http://example.com/kayako/jasso/auth.php?site=staff
6. Optionally, create a wrapper menu entry in Joomla to http://example.com/kayako/jasso/index.php for the autologin wrapper

== Changelog ==

= 0.8.0 =
* First public alpha release
* Basic Joomla authentication
