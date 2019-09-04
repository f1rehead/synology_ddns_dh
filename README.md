# synology_ddns_dh
Synology DDNS Updater for Dreamhost users

PHP script to accept DDNS update requests from the Synology DiskStation Manager (DSM) DDNS updater. I don't remember if I wrote this from scratch or found it. It's in my coding style so I could have written it, but I also could have reformatted it. It's been updated quite a bit over the years.

Requirements
------------
- A purchased domain name with the DNS hosted by DreamHost
- A configured fully-hosted website on DreamHost
- A Synology NAS device with DiskStation Manager (DSM)

The instructions below assume that you have already purchased a domain, are using DreamHost for your DNS hosting, and have a fully-hosted website on DreamHost. These are requirements. This script will only work if you're using DreamHost for your DNS.

Setting up the DDNS domain name in DreamHost
--------------------------------------------
Log in to the DreamHost Control Panel - https://panel.dreamhost.com/ . Create the DDNS entry by clicking on Domains > [your domain] > DNS and entering the name in the "Add a custom DNS record to [your domain]:" section. Make sure you choose "A" in the Type menu to create an 'A' record. Click the "Add Record Now" button to finish.

Allow API access in DreamHost
-----------------------------
Now head over to the API section - https://panel.dreamhost.com/?tree=home.api (Weirdly, I don't think it's possible to navigate to that page from the Panel home...). Enter someting memorable in the Comment field. In the list of functions, choose "All dns functions", and then click the "Generate a new API Key now!" button. You will need the API Key to configure the script.

Configure the script
--------------------
Open the script in your favorite text editor and set these variables: $DH_API_KEY, $HOSTS, and $PASSWD. Review $APP_NAME, $DH_API_BASE, and $DEF_COMMENT to make sure those are good.

- $DH_API_KEY : The API key you generated in the previous step.
- $HOSTS : An array of hostnames that are allowed to be updated by this script.
- $PASSWD : A simple, unencrypted password to check against the DDNS query string you will be creating in a later step. NOTE: this is an unencrypted string so don't use any of your normal passwords here. It's just for really basic authorization not for security.
- $APP_NAME : An identifier to use for the DreamHost API UUID prefix and, if you want, in the default comment when updating the DDNS IP address.
- $DH_API_BASE : This won't need to be changed unless DreamHost changes the API services host, which they probably won't.
- $DEF_COMMENT : A default comment to enter in the DNS record. The default comment is the last date and time an update was made. If you don't want a comment, just make it blank.

That should be all you need to do to the script. Copy it into a location on your DreamHost site that is accessible via HTTP or HTTPS.

Set up a Custom DDNS Provider in Synology DSM
---------------------------------------------
Log in to your Synology DSM and click on Control Panel > External Access > DDNS.
Click the "Customize" button to create a new DDNS provider. Enter a name for your service provider. In the Query URL field enter the URL to the script like this, filling in your domain name and path (if any; I put mine at the root level of the website):
https://[your domain]/[your path]/synology_ddns_dh?host=__HOSTNAME__&passwd=__PASSWORD__&myip=__MYIP__

For example:
https://example.com/cgis/synology_ddns_dh?host=__HOSTNAME__&passwd=__PASSWORD__&myip=__MYIP__


Create a DDNS Host in Synology DSM
----------------------------------
Log in to your Synology DSM and click on Control Panel > External Access > DDNS.
Click the "Add" button to create a new DDNS hostname. In the Service Provider dropdown, select the DDNS Provider you created in the previous step. Enter the hostname that you want to update (must match what you set in the script), your name or email or anything you want in the Username/Email field (this is not used by the script but is required by DSM), and in the Password field enter the password you put in the script. Click "Test Connection" to verify everything is working fine. If not, check the hostname and password to ensure they match the script. Click "OK" once everything is working.






