# Custom XAMPP/WAMP/LAMP/MAMP localhost index page
An informative custom Apache MySQL PHP localhost index page that displays your current projects, useful statistics and provides handy links. Requires PHP 8.0 or higher.
Technically this localhost index can be adapted to suit any other local apache development environment. I've attempted to add cross-platform logic so that it works on both Windows, Linux and Mac platforms.

Please feel free to fork and make your own changes!

## Features:

- Search functionality for all local project folders specified
- Resizable and draggable columns
- Real-time clock
- Displays the current version of Apache, PHP and MySQL
- System stats using AJAX showing CPU Load, Memory Usage and Disk Space
- Configuration page for quick and easy setup
- Toggle PHP error handling and logging
- Virtual Hosts List
- Button toggle to display the Apache error log
- Mac OS X style dock with links to relevant web-sites
- Modern responsive look
- Theme switcher for light and dark
- Peace of mind 🧘 (hopefully!)

![search functionality](screenshots/index-dark.png)

![search functionality](screenshots/settings.png)

![search functionality](screenshots/index-light.png)

## How to install:

1. Clone this repo to a location on your hard disk, e.g. `C:/xampp/htdocs/`
2. Run `npm install` in the repo's location to install dev dependencies
3. Set your config
    - Update the default `config.php` with your local MySQL login credentials, Apache and HTDOCS path<br/>
     — OR —
    - Set your custom user config by navigating to the Settings page in the footer
4. Modify the PHP code within `partials/folders.php` to suit your needs
5. Modify the HTML code within `partials/dock.php` to add your favourite links
6. Customise to your delight
7. Run `npm run build` to compile any changed SCSS or JavaScript