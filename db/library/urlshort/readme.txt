urlShort 1.1.2 http://urlshort.sourceforge.net

urlShort is a simple PHP/MySQL script for creating shortened URLs similar to TinyURL. urlShort offers custom short names and an API for creating shortened URLs. urlShort requires PHP/MySQL and supports mod_rewrite. It is released under the GNU GPL.

-----------------------------------------------------------------------

Changelog:

jQuery call
Install path fix

-----------------------------------------------------------------------

Known Issues:

api.php?short=http://urlshort.com/1 returns 500 HTTP code, should be 200

-----------------------------------------------------------------------

To install:

1. Upload the files to your website. It should either be in your root public_html directory or a subdomain.

2. Create a MySQL database and user for urlShort. 

3. Import the urlshort.sql file:

      ((
      
         mysql -u <urlshort_user> -p <urlshort_db> < urlshort.sql
      
      ))

Or using the import tab in phpMyAdmin

4. Edit the configuration file includes/conf.php for your server.

5. Enjoy!