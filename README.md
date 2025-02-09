
# UpTalkr

**UpTalkr** is a legacy social media platform that has been discontinued. The source code provided in this repository is published solely for historical and educational purposes. **Please note:** The project is no longer maintained and is not intended for production use.

---

## Overview

UpTalkr was a social media platform that allowed users to create and share posts with images, videos, and text. Although the service has been discontinued, the source code is provided for historical reference and educational purposes.

---

## Google Cloud AI Integration

The code leverages Google Cloud's AI services (e.g., Vision API, Video Intelligence API, Cloud Storage) to analyze images and videos in posts. **Before using the project, please:**

- Set up your Google Cloud service account and download the corresponding JSON key file.
- Update all relevant configuration sections and file paths (e.g., where the JSON key file is referenced) with your own Google Cloud credentials.
- **IMPORTANT:** The current implementation hardcodes some credentials in several places. Review all files carefully and replace any hardcoded values with your secure configuration.

---

## Hetzner Storagebox / WebDAV Integration

This project is designed to work with a WebDAV service such as Hetzner Storagebox for storing media files (images and videos). To use this feature:

- Set up your Hetzner Storagebox (or another WebDAV service).
- Update the `config.php` file with your WebDAV credentials and URL, for example:
  ```php
  <?php
  // WebDAV server URL (e.g., your Hetzner Storagebox URL)
  $webdav_server = 'https://your-storagebox-url/';

  // WebDAV username and password
  $webdav_user = 'your_webdav_username';
  $webdav_pass = 'your_webdav_password';
  ?>
  ```
- Review the code sections that handle file uploads and ensure that all file paths and credentials are correctly set.

---

## Prerequisites

- **PHP:** Version 7.x or later
- **Database:** MySQL or MariaDB
- **Composer:** Dependency manager for PHP
- **Web Server:** Apache, Nginx, or another web server

---

## Installation

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/Thearex/UpTalkr.git
   cd UpTalkr
   ```

2. **Install Composer Dependencies:**
   The project uses several Composer packages. Ensure Composer is installed, then run:
   ```bash
   composer install
   ```
   This command installs the following packages:
   - `google/apiclient`
   - `google/cloud-storage`
   - `google/cloud-videointelligence`
   - `phpmailer/phpmailer`

---

## Configuration

Before using the project, you **must** update several configuration files with your own credentials and settings. **Do not hardcode sensitive information into public repositories!**

### Database Configuration

The project uses multiple methods to connect to the database (using both `mysqli` and `PDO`). Update the following files with your own database credentials:

- **updb.php**  
  _Example:_
  ```php
  <?php
  // Database settings – three different connection methods
  $servername = "localhost";
  $username = "your_db_username";
  $password = "your_db_password";
  $dbname = "your_db_name";
  $password_db = "your_db_password";

  $dbsrv = "localhost";
  $dbusr = "your_db_username";
  $pass_db = "your_db_password";
  $dbnam = "your_db_name";

  $dbHost = "localhost";
  $dbUser = "your_db_username";
  $dbPassword = "your_db_password";
  $dbName = "your_db_name";

  // Google authentication
  $googleClientId = 'your_google_client_id';
  $googleClientSecret = 'your_google_client_secret';
  ?>
  ```

- **db-pdo.php**  
  _Example:_
  ```php
  <?php
  $servername = "localhost";
  $username = "your_db_username";
  $password = "your_db_password";
  $dbname = "your_db_name";

  try {
      $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
      die("Connection failed");
  }
  ?>
  ```

- **db.php**  
  _Example:_
  ```php
  <?php
  $servername = "localhost";
  $username = "your_db_username";
  $password = "your_db_password";
  $dbname = "your_db_name";

  $conn = new mysqli($servername, $username, $password, $dbname);

  if ($conn->connect_error) {
      die("Connection failed");
  }
  ?>
  ```

### API and Encryption Configuration

- **config.php**  
  Set your secret encryption key for private messaging and WebDAV settings:
  ```php
  <?php
  return [
      'encryption_key' => 'your_secret_encryption_key',
      'webdav_server'  => 'https://your-storagebox-url/',
      'webdav_user'    => 'your_webdav_username',
      'webdav_pass'    => 'your_webdav_password'
  ];
  ?>
  ```

- **Google Cloud Credentials:**  
  Update the configuration for Google Cloud services in the code where needed. Make sure to secure your JSON key file and update any hardcoded paths or values.

---

## Composer Dependencies

The project’s `composer.json` file specifies the following required packages:
```json
{
    "require": {
        "google/apiclient": "^2.0",
        "google/cloud-storage": "^1.41",
        "google/cloud-videointelligence": "^1.15",
        "phpmailer/phpmailer": "^6.9"
    },
    "autoload": {
        "files": ["vendor/autoload.php"]
    }
}
```
After cloning the repository, run:
```bash
composer install
```
to install these dependencies.

---

## Database Setup

Below are the required database tables (for MySQL/MariaDB). Create these tables in your database using your preferred tool (e.g., phpMyAdmin, MySQL CLI).

### Followers
```sql
CREATE TABLE `Followers` (
  `user_id` int(11) DEFAULT NULL,
  `follower_id` int(11) DEFAULT NULL,
  INDEX(`user_id`),
  INDEX(`follower_id`)
);
```

### admin
```sql
CREATE TABLE `admin` (
  `userid` int(11) NOT NULL,
  `admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`userid`)
);
```

### badges
```sql
CREATE TABLE `badges` (
  `user_id` int(11) NOT NULL,
  `verified` tinyint(1) DEFAULT NULL,
  `staff` int(11) DEFAULT NULL,
  `Axsoter` tinyint(1) NOT NULL DEFAULT '0',
  `Epic` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`user_id`)
);
```

### color
```sql
CREATE TABLE `color` (
  `user_id` int(11) DEFAULT NULL,
  `color` int(11) DEFAULT '0',
  INDEX(`user_id`)
);
```

### email_verification_tokens
```sql
CREATE TABLE `email_verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`)
);
```

### ilmoitukset
```sql
CREATE TABLE `ilmoitukset` (
  `ilmoitus_id` int(11) NOT NULL AUTO_INCREMENT,
  `ilmoitus` text DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `paivamaara` date DEFAULT NULL,
  PRIMARY KEY (`ilmoitus_id`)
);
```

### ilmoitus_like
```sql
CREATE TABLE `ilmoitus_like` (
  `ilmoitus_like_id` int(11) NOT NULL AUTO_INCREMENT,
  `tykkaaja_id` int(11) DEFAULT NULL,
  `ilmoitus_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `postauksen_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ilmoitus_like_id`)
);
```

### kommentit
```sql
CREATE TABLE `kommentit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kommentti` varchar(255) DEFAULT NULL,
  `postauksen_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`postauksen_id`),
  INDEX (`user_id`)
);
```

### likes
```sql
CREATE TABLE `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`post_id`),
  INDEX (`user_id`)
);
```

### megapisteet
```sql
CREATE TABLE `megapisteet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `piste` int(11) NOT NULL,
  PRIMARY KEY (`id`)
);
```

### post
```sql
CREATE TABLE `post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `video_ids` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`user_id`)
);
```

### messages
```sql
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `iv` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`sender_id`),
  INDEX (`receiver_id`)
);
```

### moderators
```sql
CREATE TABLE `moderators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `moderator` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`userid`)
);
```

### password_resets
```sql
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(200) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

### postaukset
```sql
CREATE TABLE `postaukset` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `file` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT '0',
  PRIMARY KEY (`post_id`),
  INDEX (`user_id`)
);
```

### privacy_policy_accepted
```sql
CREATE TABLE `privacy_policy_accepted` (
  `user_id` int(11) NOT NULL,
  `accepted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
);
```

### profiili
```sql
CREATE TABLE `profiili` (
  `userid` int(11) NOT NULL,
  `profiilikuva` varchar(255) DEFAULT NULL,
  `banneri` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userid`)
);
```

### report
```sql
CREATE TABLE `report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reported_item_type` ENUM('user','post') NOT NULL,
  `reported_item_id` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `additional_info` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

### rewards_claimed
```sql
CREATE TABLE `rewards_claimed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `reward_name` varchar(255) NOT NULL,
  `claimed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`userid`)
);
```

### settings
```sql
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `about_me` text DEFAULT NULL,
  `user_id` int(11) UNIQUE DEFAULT NULL,
  `asetus1` tinyint(1) DEFAULT '0',
  `asetus2` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
);
```

### supportid
```sql
CREATE TABLE `supportid` (
  `userid` int(11) NOT NULL,
  `supportid` varchar(16) NOT NULL,
  PRIMARY KEY (`userid`, `supportid`)
);
```

### token
```sql
CREATE TABLE `token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL UNIQUE,
  `vanhenee` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`user_id`)
);
```

### user_sessions
```sql
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_token` varchar(255) NOT NULL,
  `session_data` text NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

### usernameaika
```sql
CREATE TABLE `usernameaika` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `vaihtoaika` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

### users
```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `luotu` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `google_userid` varchar(255) DEFAULT NULL,
  `receive_messages` tinyint(1) DEFAULT '1',
  `blocked_users` text DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

---

## Google Cloud AI Integration Recap

The code uses Google Cloud's AI services for analyzing images and videos in posts:
- **Vision API** for image labeling and safe search detection.
- **Video Intelligence API** for analyzing video content.
- **Cloud Storage** is used to temporarily store video files before analysis.

Please review all code sections that handle these services, and update the file paths and credentials with your own secure settings.

---

## Troubleshooting

- **Read your PHP error messages** and check your error logs.  
- Ensure that all configuration files (e.g., database credentials, API keys, Google Cloud and WebDAV settings) are set up correctly.  
- If a feature isn’t working, verify that you have replaced all placeholder values with your own secure configuration.
- good luck.
---

## Files to Review and Configure

Before deploying or modifying the project, review and update the following files with your own settings and credentials:

- `updb.php`
- `db-pdo.php`
- `db.php`
- `config.php`
- `navbar.php`
- `check-cookie-token.php`
- `composer.json` (and then run `composer install`)
- Any other files that reference API keys or sensitive information

---

## Licensing

This project is licensed under the following licence:
- **BSD 2-Clause License**  

See the [LICENSE](LICENSE) file for full licensing details.

---

## Notes

- **Configuration Files:** Ensure that all configuration files containing database credentials and API keys (e.g., `updb.php`, `config.php`, etc.) are kept secure and are not exposed publicly.
- **Project Status:** UpTalkr is a legacy project that is no longer maintained. The code is provided solely for historical reference and educational purposes.
- **Google Cloud Integration:** The project uses Google Cloud AI services for image and video analysis. Please update these credentials to your own secure settings.
- **WebDAV Integration:** The project is built to work with Hetzner Storagebox (or any WebDAV service) for media storage. Update your WebDAV settings in `config.php` as described above.

