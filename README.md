# 👨🏻‍💻 Custom XAMPP/LAMP/MAMP Localhost Index Page

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

- [✨ Features](#-features)
- [🛠️ How to Install](#-how-to-install)
- [📸 Screenshots](#-screenshots)
- [📁 Project Structure](#-project-structure)

## ✨ Features

- **Instant Project Search** – Live filter through all your local folders with ease  
- **Flexible Column Layout** – Draggable, resizable, and fully customisable folder views  
- **Real-Time Clock** – Because knowing the time is still a thing  
- **Environment Snapshot** – Instantly see which versions of Apache, PHP, and MySQL you're running  
- **Smart Apache Control** – Safely restart the active Apache instance based on your OS and setup  
- **Live System Monitoring** – AJAX-powered CPU, memory, and disk usage at a glance  
- **Quick Config Panel** – Update paths, ports, and settings without breaking a sweat  
- **PHP Error Management** – Toggle error display and logging on the fly  
- **Virtual Hosts Overview** – View and validate your active VHost configurations  
- **Apache Error Log Toggle** – One-click access to the latest server logs  
- **Custom Dock** – macOS-style dock with editable shortcuts to your key tools and sites  
- **Responsive Interface** – Sleek, modern design that adapts to all screen sizes  
- **Theme Switcher** – Light mode. Dark mode. You choose.  
- **Low-Stress Local Dev** – Designed to stay out of your way 🧘 so you can focus on building

## 🛠️ How to Install

1. Clone this repo to a location on your hard disk, e.g. `C:/xampp/htdocs/`
2. Run `npm install` in the repo's location to install dev dependencies
3. Set your custom user config by navigating to the Settings page in the footer
4. Customise to your delight
5. Run `npm run build` to compile any changed SCSS or JavaScript

## 🖼️ Screenshots

![search functionality](screenshots/index-dark.png)

![search functionality](screenshots/settings.png)

![search functionality](screenshots/index-light.png)

## 🗂️ Project Structure

A quick overview of the core files and folders in this project, so you’re never left wondering what does what.

---

### 📄 Root Files

| File                     | Description |
|--------------------------|-------------|
| `apache_error_log.php`   | Fetches and returns Apache error log entries via AJAX. |
| `system_stats.php`       | Provides live server stats (CPU, memory, disk) using AJAX. |
| `toggle_apache.php`      | Safely restarts the currently running Apache instance. |
| `config.php`             | Default configuration including MySQL credentials and Apache paths. |
| `index.php`              | Main entry point. Displays the homepage with all widgets and layout. |
| `phpinfo.php`            | Outputs PHP environment details via `phpinfo()` — handy for debugging. |
| `system_stats.php`       | Backend logic for system stat readings, used by AJAX. |
| `user_config.php`        | Auto generated user-defined overrides saved from the settings UI. |
| `package.json`           | Lists build dependencies and Webpack/Babel/Sass configuration. |
| `webpack.config.js`      | Webpack build pipeline for JS and SCSS. |

---

### 🧩 Partials (`partials/`)

| File                     | Description |
|--------------------------|-------------|
| `dock.json`              | Stores dock layout and links in JSON format. |
| `dock.php`               | Renders the customizable macOS-style dock. |
| `folders.json`           | Defines folder configurations, incl. paths, filters, link templates, and display rules. |
| `folders.php`            | Dynamically scans and lists local project folders. |
| `footer.php`             | The shared footer, includes theme toggle and settings link. |
| `header.php`             | Shared `<head>` setup, includes all essential meta and scripts. |
| `info.php`               | Displays system information like PHP, Apache, and MySQL versions. |
| `link_templates.json`    | Defines reusable HTML link templates for folder display, referenced by folders.json. |
| `settings.php`           | The settings interface for configuring paths, dock, and logs. |
| `submit.php`             | Handles the saving of user-configured settings. |

---

### 🛠️ JavaScript (`assets/js/`)

| File/Folder              | Description |
|--------------------------|-------------|
| `main.js`                | Webpack entry point — initialises all modules. |
| `modules/`               | Modular ES6 scripts (e.g. `clock.js`, `dock.js`, `columns.js`) |

---

### 🎨 SCSS (`assets/scss/`)

| File              | Description |
|-------------------|-------------|
| `_fonts.scss`     | Custom fonts used in the project. |
| `_keyframes.scss` | Keyframe animations used throughout the site. |
| `_main.scss`      | Layout and style rules for the homepage. |
| `_mobile.scss`    | Responsive styles and layout adjustments for mobile devices. |
| `_reset.scss`     | Basic reset for cross-browser consistency. |
| `_root.scss`      | Root-level variables and global CSS custom properties. |
| `style.scss`      | SCSS entry point that imports all partials. |

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
