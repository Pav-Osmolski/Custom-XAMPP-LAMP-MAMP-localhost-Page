# 👨🏻‍💻 Custom XAMPP/LAMP/MAMP Localhost Index Page

![PHP Version](https://img.shields.io/badge/PHP-5.4+-blue)
![Platform](https://img.shields.io/badge/platform-Windows%20%7C%20macOS%20%7C%20Linux-lightgrey)
![License](https://img.shields.io/badge/license-MIT-green)
![Webpack](https://img.shields.io/badge/Bundler-Webpack-orange)
![SCSS](https://img.shields.io/badge/Styles-SCSS%20%26%20Babel-purple)
![Modular JS](https://img.shields.io/badge/JavaScript-ES6%20Modules-yellow)
![Last Commit](https://img.shields.io/github/last-commit/Pav-Osmolski/Custom-XAMPP-LAMP-MAMP-localhost-Page)

An informative and fully modular custom local homepage for xAMP stacks (Apache, MySQL, PHP). This locahost index page showcases your projects, displays system stats, and provides admin tools — all now powered by a modern Webpack build process. Technically this can be adapted to suit any local Apache PHP development environment.

✅ Requires **PHP 5.4+**  
✅ Works on **Windows, macOS, and Linux**  
✅ Built with **Webpack, Babel, Sass, and module-based JS**

It is intended to be used with AMP stacks such as:

- [XAMPP](https://www.apachefriends.org/)
- [AMPPS](https://ampps.com/)
- [LAMP](https://www.digitalocean.com/community/tutorials/how-to-install-lamp-stack-on-ubuntu)
- [MAMP & MAMP PRO](https://www.mamp.info/)

Please feel free to fork and make your own changes!

## 📚 Table of Contents

- [✨ Features](#features)
- [🛠️ How to Install](#how-to-install)
- [📸 Screenshots](#screenshots)
- [📁 Project Structure](#project-structure)

## Features

- **Instant Project Search** – Live filter through all your local folders with ease  
- **Flexible Column Layout** – Draggable, resizable, and fully customisable folder views  
- **Real-Time Clock** – Because knowing the time is still a thing  
- **Environment Snapshot** – Instantly see which versions of Apache, PHP, and MySQL you're running  
- **Smart Apache Control** – Safely restart the active Apache instance based on your OS and setup  
- **Live System Monitoring** – AJAX-powered CPU, memory, and disk usage at a glance  
- **Quick Config Panel** – Update paths, ports, and settings without breaking a sweat  
- **PHP Error Management** – Toggle error display and logging on the fly  
- **Virtual Hosts Overview** – View and validate active VHosts, with SSL certificate management
- **Apache Error Log Toggle** – One-click access to the latest server logs  
- **Custom Dock** – macOS-style dock with editable shortcuts to your key tools and sites  
- **Responsive Interface** – Sleek, modern design that adapts to all screen sizes  
- **Theme Switcher** – Light mode. Dark mode. You choose.  
- **Low-Stress Local Dev** – Designed to stay out of your way 🧘 so you can focus on building

## How to Install

1. Clone this repo to a location on your hard disk, e.g. `C:/xampp/htdocs/`
2. Run `npm install` in the repo's location to install dev dependencies
3. Set your custom user config by navigating to the Settings page in the footer
4. Customise to your delight
5. Run `npm run build` to compile any changed SCSS or JavaScript

## Screenshots

![search functionality](screenshots/index-dark.png)

![search functionality](screenshots/settings.png)

![search functionality](screenshots/index-light.png)

## Project Structure

A quick overview of the core files and folders in this project, so you’re never left wondering what does what.

---

### 📄 Root Files

| File                     | Description |
|--------------------------|-------------|
| `index.php`              | Main entry point. Displays the homepage with all widgets and layout. |
| `package.json`           | Lists build dependencies and Webpack/Babel/Sass configuration. |
| `webpack.config.js`      | Webpack build pipeline for JS and SCSS. |

---

### ⚙️ Config (`config/`)

| File                     | Description |
|--------------------------|-------------|
| `config.php`             | Default configuration including MySQL credentials and Apache path settings. |
| `user_config.php`        | Auto generated user-defined overrides saved from the settings UI. |
| `debug.php`              | Logs raw shell commands (with optional context) to `logs/localhost-page.log`. |
| `dock.json`              | Stores dock layout and links in JSON format. |
| `folders.json`           | Defines folder configurations, incl. paths, filters, link templates, and display rules. |
| `link_templates.json`    | Defines reusable HTML link templates for folder display, referenced by folders.json. |

---

### 📜 Certificate Generator Scripts (`crt/`)

These scripts are automatically used by `utils/generate_cert.php` to generate self-signed certificates for local development environments.

| Script                    | Purpose |
|---------------------------|---------|
| `make-cert-silent.bat`    | Generates a `.crt` and `.key` using OpenSSL silently via Windows Batch script. |
| `make-cert-silent.sh`     | Bash script to generate a cert/key pair non-interactively using OpenSSL. |

> 💡 These scripts are auto-copied from `crt/` if missing from `apache/crt/` or outdated.

---

### 🧩 Partials (`partials/`)

| File                     | Description |
|--------------------------|-------------|
| `dock.php`               | Renders the customizable macOS-style dock. |
| `folders.php`            | Dynamically scans and lists local project folders. |
| `footer.php`             | The shared footer, includes theme toggle and settings link. |
| `header.php`             | Shared `<head>` setup, includes all essential meta and scripts. |
| `info.php`               | Displays system information like PHP, Apache, and MySQL versions. |
| `settings.php`           | The settings interface for configuring paths, dock, and logs. |
| `submit.php`             | Handles the saving of user-configured settings. |
| `vhosts.php`             | Lists and validates Apache virtual hosts, including SSL and hosts file checks. |

---

### 🧰 Utils (`utils/`)

| File                     | Description |
|--------------------------|-------------|
| `apache_error_log.php`   | Fetches and returns Apache error log entries via AJAX. |
| `apache_inspector.php`   | Detects Apache installation details like version, modules, and config paths. |
| `generate_cert.php`      | Generates SSL certificates. |
| `open_folder.php`        | Opens a specified folder path in the system file explorer (cross-platform). |
| `phpinfo.php`            | Outputs PHP environment details via `phpinfo()` — handy for debugging. |
| `system_stats.php`       | Provides live server stats (CPU, memory, disk) using AJAX. |
| `toggle_apache.php`      | Safely restarts the currently running Apache instance. |

---

### 🛠️ JavaScript (`assets/js/`)

| File/Folder              | Description |
|--------------------------|-------------|
| `main.js`                | Webpack entry point — initialises all modules. |
| `modules/`               | Modular ES6 scripts (e.g. `clock.js`, `dock.js`, `columns.js`) |

---

### 🎨 SCSS (`assets/scss/`)

| Folder           | Description |
|------------------|-------------|
| `base/`          | Base-level styles including fonts, resets, and root CSS variables. |
| `components/`    | Reusable UI components such as dock, folders, forms, tooltips, and system modules. |
| `layout/`        | Page layout structure including header, footer, and main content styles. |
| `pages/`         | Styles specific to individual pages like settings and PHP info. |
| `utils/`         | SCSS utilities including keyframes, media queries, mixins, and variables. |
| `style.scss`     | The main SCSS entry point that imports all partials. |

---

### 🔤 Fonts (`fonts/`)

| File                     | Description |
|--------------------------|-------------|
| `Ubuntu-Regular.woff2`   | Regular variant. |
| `Ubuntu-Bold.woff2`      | Bold variant. |
| `Ubuntu-Light.woff2`     | Light variant. |
| `Ubuntu-Medium.woff2`    | Medium variant. |
| `css2.css`               | `@font-face` rules for Ubuntu. |

---

Explore and customise — this project is made to be yours! 😎
