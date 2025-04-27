Below is a step‐by‐step guide on installing PHPMailer in Ubuntu 24.04.01. There are two popular methods: using Composer (highly recommended for managing dependencies) or a manual installation. Let’s walk through both.

---

## Method 1: Installing via Composer (Recommended)

1. **Ensure PHP is Installed**  
   First, check if PHP is available on your system. In your terminal, run:  
   ```bash
   php -v
   ```  
   If PHP is not installed, update your package list and install it:
   ```bash
   sudo apt update
   sudo apt install php
   ```

2. **Install Composer**  
   Composer is the de facto dependency manager for PHP. Install it using apt:
   ```bash
   sudo apt update
   sudo apt install composer
   ```  
   Confirm the installation by checking its version:
   ```bash
   composer --version
   ```

3. **Set Up Your Project Directory**  
   Navigate to your project folder or create a new one:
   ```bash
   mkdir -p ~/my_php_project
   cd ~/my_php_project
   ```

4. **Install PHPMailer with Composer**  
   In your project directory, run:
   ```bash
   composer require phpmailer/phpmailer
   ```  
   This command will download PHPMailer into your `vendor` directory and update your `composer.json`.

5. **Using PHPMailer in Your Code**  
   In your PHP script, include Composer’s autoloader and instantiate PHPMailer:
   ```php
   <?php
   require 'vendor/autoload.php';

   use PHPMailer\PHPMailer\PHPMailer;
   use PHPMailer\PHPMailer\Exception;

   $mail = new PHPMailer(true);

   try {
       //Server settings
       $mail->isSMTP();
       $mail->Host       = 'smtp.example.com';
       $mail->SMTPAuth   = true;
       $mail->Username   = 'your_username@example.com';
       $mail->Password   = 'your_password';
       $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
       $mail->Port       = 587;

       //Recipients
       $mail->setFrom('from@example.com', 'Mailer');
       $mail->addAddress('recipient@example.com', 'Receiver');  

       //Content
       $mail->isHTML(true);
       $mail->Subject = 'Here is the subject';
       $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
       $mail->AltBody = 'This is the plain text version of the email content';

       $mail->send();
       echo 'Message has been sent';
   } catch (Exception $e) {
       echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
   }
   ?>
   ```  
   This snippet gives you a basic example of sending an email via SMTP with PHPMailer.

---

## Method 2: Manual Installation

1. **Download the Latest PHPMailer Release**  
   Visit the [PHPMailer GitHub Releases page](https://github.com/PHPMailer/PHPMailer/releases) to find the latest stable version. Alternatively, download it via the command line (adjust the version as needed):
   ```bash
   wget https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.8.0.zip
   ```

2. **Extract the Package**  
   After downloading, extract the ZIP archive:
   ```bash
   unzip v6.8.0.zip
   ```
   This will create a directory such as `PHPMailer-6.8.0`.

3. **Integrate PHPMailer into Your Project**  
   Copy or move the extracted folder to your project directory:
   ```bash
   cp -r PHPMailer-6.8.0 ~/my_php_project/phpmailer
   ```

4. **Include PHPMailer in Your Code**  
   Instead of using Composer’s autoloader, manually include the necessary files in your PHP script:
   ```php
   <?php
   require '/path/to/your/project/phpmailer/src/Exception.php';
   require '/path/to/your/project/phpmailer/src/PHPMailer.php';
   require '/path/to/your/project/phpmailer/src/SMTP.php';

   use PHPMailer\PHPMailer\PHPMailer;
   use PHPMailer\PHPMailer\Exception;

   $mail = new PHPMailer(true);
   // Setup your mail configuration as shown above...
   ?>
   ```

---

## Additional Tips

- **SMTP Configuration:** Whether you use Composer or manual installation, configuring your SMTP settings correctly is key for sending emails. Ensure your SMTP host, port, and authentication details are properly specified.  
- **Error Handling:** PHPMailer throws exceptions when errors occur. Wrap your code in a try...catch block to gracefully handle exceptions and output useful debugging information.  
- **Security Best Practices:** Avoid hardcoding sensitive credentials (like SMTP passwords) directly in your code. Instead, consider using environment variables or configuration files with proper access controls.

These methods work seamlessly on Ubuntu 24.04.01 and provide robust solutions for sending emails with PHP. If you’re planning to develop further with PHP and are already using Composer, it’s usually best to rely on Composer’s dependency management for easier updates and maintenance.

---
https://copilot.microsoft.com/shares/ZLdTroreUia9HUzzfyp9T
