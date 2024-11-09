# Deployment Documentation 

## Overview

This document provides a step-by-step guide for deploying a PHP application that uses:
- **PHP version**: 8.2
- **MySQL version**: 8.2
- **Redis** for caching
- **SMTP** for email notifications

## Prerequisites

Before deploying the application, ensure the following software is installed on your server:

- **PHP 8.2**: Ensure PHP 8.2 is installed with required extensions ex (mysql, redis,..).
- **MySQL 8.2**: Install MySQL version 8.2 for database management.
- **Redis**: Install Redis for caching and session storage.
- **Web Server**: Apache or Nginx configured to serve PHP applications.


## Configure your Project
After clonong project from git you need to do the following:

### 1. To install composer, run:

 composer install

### 2. Change Database Config in env file :

 go to config/env.php

    'DB_HOST' => 'localhost:3306',
    'DB_USER' => 'root',
    'DB_PASS' => '',   
### 3. Change Redis Config in env file :

 go to config/env.php

    'REDIS_HOST'=>'',
    'REDIS_PORT'=>'',
    'REDIS_PASSWORD'=>''
### 4. Change Smtp Config in env file :

 go to config/env.php

    'MAIL_HOST' => '',
    'MAIL_USERNAME' => '',
    'MAIL_PASSWORD' => '',
    'MAIL_PORT' => 465, 
    'MAIL_ENCRYPTION' => 'ssl', 

### 5. You can change Rate Limit and throttling rules in env file :

 go to config/env.php

      'RATE_LIMIT' => 5,
    'TIME_FRAME_IN_SECONDS' => 60,
    'throttle' => [
        'create' => [
            'count' => 10,
            'time_frame' => 30
        ],
        'update' => [
            'count' => 5,
            'time_frame' => 60
        ],
        'delete' => [
            'count' => 3,
            'time_frame' => 60
        ]
    ],


### 6. You can Can build your datbase structure into your database by run  :
      
          
     php database/CreateTables.php

### 7. You can Can seed your data to  your database by run  :
      
          
     php database/seed.php

 ### 8. You can download postman collection to test the backend
 ### 9. Change your app url into base_url variable which exist in Environement
