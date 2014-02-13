PHPepl
======

Created by: [@mrjoelkemp](http://www.twitter.com/mrjoelkemp)
Modified by [Jude Aakjaer](http://github.com/santouras) for [Learnable](https://learnable.com) and [SitePoint](http://sitepoint.com)

### Purpose
To provide a clean, multi-line PHP evaluation environment.

### Running it locally

The online version of PHPepl is sandboxed. The exposed `eval` is sandboxed at the server configuration layer plus some blacklisting of methods at the application level via [PHP-Sandbox](https://github.com/fieryprophet/php-sandbox).

To serve this application locally, you'll need a web server and PHP:

* Mac: [MAMP](http://www.mamp.info/en/index.html)
* Windows: [WAMP](http://www.wampserver.com/en/)

### Composer install

    cd phpepl
    php composer.phar install

You can then point your apache server to serve files from the `/phpepl/public` root folder

* Namely, you should be able to visit the app (`/phpepl/public/index.html`) from `http://localhost` (include a custom port if necessary)

The app will automatically disable the sandbox and give you free reign over the REPL to
execute any commands.

### License

[MIT](LICENSE)