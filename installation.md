
cd /var/www/html/

```git
sudo git clone https://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@github.com/alvin-kiveu/Shop.git
```

Create Apache configuration file for the new domain:

```bash
sudo nano /etc/apache2/sites-available/shop.trymysoftware.online.conf
```



```apache
<VirtualHost *:80>
    ServerName shop.trymysoftware.online
    ServerAdmin mail@shop.trymysoftware.online
    DocumentRoot /var/www/html/Shop

    <Directory /var/www/html/Shop/>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined

    <IfModule mod_dir.c>
        DirectoryIndex index.html index.php
    </IfModule>
</VirtualHost>
```

giveSubdomainSSLPermission



8. Enable the site and required modules:

```bash
sudo a2ensite shop.trymysoftware.online.conf
sudo a2enmod rewrite
sudo systemctl restart apache2  
```


```bash
sudo certbot --apache -d shop.trymysoftware.online
```


```bash
sudo chown -R www-data:www-data /var/www/html/Shop/ && sudo chmod -R 755 /var/www/html/Shop/
```

```bash
sudo systemctl restart apache2
9. Check the Apache configuration:

```bash
sudo apache2ctl configtest
# Should output: Syntax OK
```

WordPress has been installed. Thank you, and enjoy!

Username	ums
Password Umeskia@46

```bash
sudo systemctl restart apache2
```

