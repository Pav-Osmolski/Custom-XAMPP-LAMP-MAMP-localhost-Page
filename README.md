# Custom XAMPP localhost index page
An informative custom XAMPP localhost index page that displays your current projects and provides handy links.
Technically this localhost index can be easily adapted to suit any other local apache development environment.

Please feel free to fork and make your own changes!

## Features:

- Search functionality for all project folders specified
- Real-time clock
- Displays current versions of Apache, PHP and MySQL
- Mac OS X style dock with links to relevant web-sites
- Modern responsive look
- Theme switcher for light and dark
- Peace of mind 🧘

![search functionality](screenshots/index.png)

## How to install:

1. Clone this repo to a location on your hard disk, e.g. `C:/xampp/htdocs/`
2. Update the `config.php` with your local MySQL login credentials
3. Ensure the path to xampp is correct within the `$apacheVersion` variable
4. Ensure the path to `phpinfo.php` is correct
5. Modify the PHP script inside each column to suit your needs
6. Customise to your delight