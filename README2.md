# The Scent - Aromatherapy E-commerce Platform

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT) <!-- Example badge -->
[![PHP Version](https://img.shields.io/badge/PHP-8.3.6-blue.svg)](https://www.php.net/) <!-- Example badge -->
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0.41-orange.svg)](https://www.mysql.com/) <!-- Example badge -->

Welcome to The Scent, a premium e-commerce platform dedicated to handcrafted aromatherapy and wellness products. Inspired by nature and crafted with expertise, The Scent offers globally sourced botanical blends designed to nurture the mind, body, and soul.

**[Live Demo Placeholder: Add Link Here]**

**(Placeholder: Add a compelling screenshot or GIF of the application here)**

## ✨ Features

**Frontend & User Experience:**

*   **Visually Rich Homepage:** Engaging hero section with video background and animated SVG mist effects.
*   **Product Showcase:** Elegant display of product collections with hover effects.
*   **Interactive Scent Quiz:** A multi-step inline quiz to help users discover their perfect aroma profile based on needs and preferences.
*   **Parallax Effects:** Subtle scroll-based animations for enhanced visual depth (e.g., About section image).
*   **Interactive Ingredient Map:** Visually explore the global origins of key botanical ingredients with informative tooltips.
*   **Dark/Light Mode:** User-toggleable theme for comfortable browsing in different lighting conditions.
*   **Ambient Audio:** Optional background nature sounds to enhance the immersive experience.
*   **Testimonial Showcase:** Customer reviews displayed with subtle fade-in animations.
*   **Responsive Design:** Adapts gracefully to various screen sizes (desktop, tablet, mobile).
*   **Newsletter Signup:** Frontend interface for capturing user email addresses.
*   **Accessibility Considerations:** Includes ARIA labels, focus indicators, and respects `prefers-reduced-motion`.

**Backend & Functionality (Inferred from Tech Stack & E-commerce Nature):**

*   **Dynamic Content:** Powered by PHP for serving dynamic product information, pages, and handling user interactions.
*   **Database Integration:** Uses MySQL to store product details, user information, orders, etc.
*   **Product Management:** Backend system (implied) for adding, updating, and managing products and inventory.
*   **Shopping Cart & Checkout:** Core e-commerce functionality (implied) for users to purchase products.
*   **User Accounts:** (Implied) Functionality for user registration, login, and order history.
*   **Newsletter Management:** Backend logic (implied) to handle newsletter subscriptions.

## 🛠️ Tech Stack

*   **Backend:** PHP 8.3.6
*   **Database:** MySQL 8.0.41
*   **Web Server:** Apache 2.4.58
*   **OS:** Ubuntu 24.04.1 LTS
*   **Frontend:**
    *   HTML5
    *   CSS3 (Custom Properties, Flexbox, Grid, Animations, Transitions)
    *   JavaScript (ES6+, DOM Manipulation, Event Handling, Fetch API - implied for backend comms)
    *   Font Awesome (for icons)
    *   Google Fonts

## 🚀 Getting Started

Follow these instructions to set up the project locally for development or testing.

**Prerequisites:**

*   Apache 2.4+ with `mod_rewrite` enabled
*   PHP 8.3.6 with necessary extensions (e.g., `mysqli`, `pdo_mysql`, `mbstring`)
*   MySQL 8.0+
*   Git

**Installation:**

1.  **Clone the repository:**
    ```bash
    git clone <your-repository-url> the-scent
    cd the-scent
    ```

2.  **Configure Apache:**
    *   Set up an Apache Virtual Host to point to the project's public directory (e.g., `/path/to/the-scent/` or `/path/to/the-scent/public/` - adjust based on project structure).
    *   Example minimal VirtualHost config (`/etc/apache2/sites-available/the-scent.conf`):
      ```apache
      <VirtualHost *:80>
          ServerName the-scent.local
          DocumentRoot /path/to/the-scent/ # Adjust if there's a public/ subfolder
          <Directory /path/to/the-scent/>
              Options Indexes FollowSymLinks
              AllowOverride All # Important for .htaccess if used
              Require all granted
          </Directory>
          ErrorLog ${APACHE_LOG_DIR}/the-scent-error.log
          CustomLog ${APACHE_LOG_DIR}/the-scent-access.log combined
      </VirtualHost>
      ```
    *   Enable the site and mod_rewrite:
      ```bash
      sudo a2ensite the-scent.conf
      sudo a2enmod rewrite
      sudo systemctl restart apache2
      ```
    *   Add `127.0.0.1 the-scent.local` to your `/etc/hosts` file.

3.  **Database Setup:**
    *   Create a MySQL database and user:
      ```sql
      CREATE DATABASE the_scent_db;
      CREATE USER 'the_scent_user'@'localhost' IDENTIFIED BY 'your_strong_password';
      GRANT ALL PRIVILEGES ON the_scent_db.* TO 'the_scent_user'@'localhost';
      FLUSH PRIVILEGES;
      ```
    *   Import the database schema. (Assuming a schema file exists, e.g., `database/schema.sql`):
      ```bash
      mysql -u the_scent_user -p the_scent_db < database/schema.sql
      ```
    *   (Optional) Import any initial seed data:
      ```bash
      mysql -u the_scent_user -p the_scent_db < database/seeds.sql
      ```

4.  **Configure Application:**
    *   Locate the configuration file (e.g., `config.php`, `config/database.php`, or potentially a `.env` file if implemented).
    *   Copy any example/template config file (e.g., `cp .env.example .env`).
    *   Update the configuration file with your database credentials (host, database name, username, password).
    *   Configure any other necessary settings (e.g., base URL).

5.  **Permissions:**
    *   Ensure Apache/PHP has write permissions for any necessary directories (e.g., `cache/`, `logs/`, `uploads/` - adjust based on project structure):
      ```bash
      # Example: Grant write permission to the web server group (e.g., www-data)
      sudo chown -R $USER:www-data storage/ logs/ cache/
      sudo chmod -R 775 storage/ logs/ cache/
      ```

**Running the Application:**

*   Open your web browser and navigate to `http://the-scent.local` (or the domain you configured in your hosts file and Apache VirtualHost).

## 🏗️ Project Structure (Example)

```
the-scent/
├── app/                 # Core application logic (controllers, models)
├── config/              # Configuration files (database, app settings)
├── public/              # Publicly accessible files (index.php, assets)
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── videos/
│   └── index.php        # Entry point
├── resources/           # Views/templates
├── routes/              # Route definitions (e.g., web.php)
├── storage/             # Storage (logs, cache, uploads) - Needs write permissions
├── database/            # Migrations, seeds, schema.sql
├── vendor/              # Composer dependencies (if used)
├── .env.example         # Example environment file
├── .env                 # Environment variables (ignored by git)
├── composer.json        # PHP dependencies (if used)
└── README.md
```
*(Note: Adjust the structure above based on the actual project layout.)*

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/your-feature-name`).
3.  Make your changes.
4.  Commit your changes (`git commit -m 'Add some feature'`).
5.  Push to the branch (`git push origin feature/your-feature-name`).
6.  Open a Pull Request.

Please ensure your code adheres to existing coding standards and includes tests where applicable.

## 📜 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details (Placeholder: Create a LICENSE.md file with the MIT license text).

## 📧 Contact

(Placeholder: Add contact information or project maintainer details if desired)
