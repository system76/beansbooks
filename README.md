# Beans

## Getting Started

This guide will walk you through getting a local instance of BeansBooks running. 
This is useful for development and testing, but should not be followed strictly 
for running a live environment.  In order to get started, you'll need the 
following:  

  *  Apache 2
  *  PHP 5.3+
  *  MySQL 5+
  *  Git Client

On Ubuntu, you can run the following to get up to speed:  

    sudo apt-get update  
    sudo apt-get install apache2 php5 libapache2-mod-php5 php5-cli php5-mysql php5-mcrypt php5-gd mysql-server mysql-client git  
  
Once you've installed all of the prerequesites, create a directory where you 
want the source to reside, then download the code from git into that directory. 
The following will create a directory called 'source' within your home directory 
and install BeansBooks there.

    cd ~
    mkdir source
    cd source
    git clone --recursive https://github.com/system76/beansbooks.git
    cd beansbooks

Copy the example.htaccess file to .htaccess within your working directory

    cp example.htaccess .htaccess

If you are not planning on hosting with SSL, then we need to comment out two
lines in the .htaccess file.  Open the file for editing:

    nano .htaccess

Look for the following two lines:

    RewriteCond %{HTTPS} !=on
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

Add a # character before them:

    #RewriteCond %{HTTPS} !=on
    #RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

Additionally, you'll need to update the permissions on two directories before proceeding:

    chmod 770 -R application/logs
    chmod 770 -R application/cache

Finally, your web user ( presumably, www-data ) will require access to the owner of
your application directory.  Presuming you've setup BeansBooks to run locally, it's easiest 
to add www-data to your user group.

    sudo usermod -a -G `whoami` www-data

If you'd like a more secure solution, you should create a user specifically 
for BeansBooks and install everything within a sub-folder of the home 
directory for that user.  In that case, you could want to replace \`whoami\` 
in the above solution with the name of the user you created.

You should now have everything you need to run BeansBooks locally.  Next, we'll 
configure and setup several dependencies to enable your application to run.

## Configuring Packages

Before configuring BeansBooks itself, we need to setup the environment to run 
it. We're going to quickly setup a local MySQL database, Apache Virtual Host, 
and create the correct permissions on our code.

### MySQL

When setting up the packages in "Getting Started" above, you should have been 
prompted to create a root password for MySQL.  You'll need this for the next 
set of steps.  Run the following to connect to MySQL - you should provide the 
password that you created earlier when prompted.

    mysql -h localhost -u root -p

Next - enter the following lines one by one.  Please note - this sets the 
password for your database user to "beansdb" and should probably be changed. 
Go ahead and replace "beansdb" with a strong password.

    CREATE USER 'beans'@'localhost' IDENTIFIED BY  'beansdb';  
    GRANT USAGE ON * . * TO  'beans'@'localhost' IDENTIFIED BY  'beansdb' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;  
    CREATE DATABASE IF NOT EXISTS  `beans` CHARACTER SET utf8 COLLATE utf8_general_ci;
    GRANT ALL PRIVILEGES ON `beans`.* TO 'beans'@'localhost';
    exit  

Great!  Now you've setup your database and user.  Please make a note of the 
username ( beans ) and password you set above.  

### Apache

First things first, enable Mod_Rewrite:

    sudo a2enmod rewrite

Now we're going to setup Apache to serve BeansBooks locally.  In order to 
determine where are going to set our document root, we need to run the following 
in a terminal:  

    pwd

Whatever the output of that is - make a note of it.  It will be the "document 
root" for your virtual host.

We're going to setup our instance of BeansBooks to be found at http://beansbooks/ - 
this is convenient as it will neither interfere with an actual domain, and 
can be configured fairly easily.  Go ahead and run the following command:  

    sudo nano /etc/apache2/sites-available/beansbooks

That will open a text editor for a new virtual host configuration - go ahead and 
copy and paste the following into the file.  Make sure to replace PWDHERE with 
the result of running "pwd" above - it will probably looking something like 
/home/yourusername/source/beansbooks and should be inserted without any trailing / .  

**TIP: To paste into the editor that you've opened, use Control + Shift + "v"**

    <VirtualHost *:80>
        ServerName beansbooks 
        ServerAlias beansbooks 

        DocumentRoot PWDHERE            
        <Directory PWDHERE>
            Options FollowSymLinks
            AllowOverride All
            Order allow,deny
            allow from all
        </Directory>
    </VirtualHost>

After pasting in and editing the above code, hit Control + "x" to exit. If it prompts you 
to save your changes, hit "y".  Then run the following to disable the default virtual host, 
enable the beans virtual host and reload the Apache configuration.  

    sudo a2dissite 000-default
    sudo a2ensite beansbooks
    sudo service apache2 reload
  
Then we need to add an entry to your hosts file to be able to load the local 
instance of beans.  

	sudo sh -c "echo '127.0.0.1 beansbooks' >> /etc/hosts"
  
## Configure BeansBooks  

Now we need to configure your BeansBooks application.

Copy example.config.php to config.php in application/classes/beans/ and fill in the appropriate information.

    cd applcation/classes/beans/
    cp example.config.php config.php
    chmod 660 config.php
    nano config.php

It's important that your config file is not world-readable.  The keys that encrypt your data, 
in addition to your database and email credentials, should be secure.

There are quite a few values that should be changed in the file, however it's mostly
self explanatory.  For starters, every place that you see "INSERT_STRONG_KEY" should have
a unique, long ( at least 128 characters ), string of random characters.  You can generate
random data from here: https://www.grc.com/passwords.htm

Also note that you should enter the MySQL username and password you setup above under
the "database" section.

Lastly, email support is optional - though it enables quite a few useful features when 
communication with customers and vendors.  If you have an SMTP email provider, you should
enter the correct information in the "email" section.

## Installation

At this point you should be able to navigate to http://beansbooks/ to finish the installation
process.  If you would prefer to run the installation and initial database setup from 
the command line you can do the following:

    php index.php --uri=/install/manual --name="Your Name" --password="password" --email="you@email.address" --accounts="full"

## SSL Support

If you would like to serve your instance of BeansBooks over SSL, you just need to add SSL
support to your web server:

    sudo a2enmod ssl

Then go ahead and edit your virtual host to support SSL connections:

    sudo nano /etc/apache2/sites-available/beans

    <IfModule mod_ssl.c>
        <VirtualHost *:443>
            ServerName beansbooks
            ServerAlias beansbooks
            
            DocumentRoot PWDHERE            
            <Directory PWDHERE>
                Options FollowSymLinks
                AllowOverride All
                Order allow,deny
                allow from all
            </Directory>

            SSLEngine on

            SSLCertificateFile /path/to/ssl/mydomain.com.crt
            SSLCertificateKeyFile /path/to/ssl/mydomain.com.unlocked.key

            <FilesMatch "\.(cgi|shtml|phtml|php)$">
                SSLOptions +StdEnvVars
            </FilesMatch>

            BrowserMatch "MSIE [2-6]" nokeepalive ssl-unclean-shutdown downgrade-1.0 force-response-1.0
            BrowserMatch "MSIE [17-9]" ssl-unclean-shutdown
        </VirtualHost>
    </IfModule>

When you're done making changes, make sure to restart Apache.