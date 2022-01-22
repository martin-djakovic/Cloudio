
# Cloudio

### TABLE OF CONTENTS

1.  Project description
2.  Stack specifications
3.  Project file structure
4.  Database structure
&nbsp;

&nbsp;

### Project description

Cloudio is a cloud storage platform that allows users to store their files in a folder on the server and conveniently access them anywhere,
on any device.

Every user is allocated a pre-determined amount of storage space which they can use to store almost any file type in their personal folder
in the cloud. The user would then be able to download those files, or delete them to free up more storage space. In the future, there are
plans to add more features to make Cloudio more convenient, such as renaming files, previewing image and text files, searching through your
folder to find documents more easily, creating folders for better organization... Cloudio is aiming to be user-friendly, but still
sophisticated enough to be useful. Overall, this cloud storage platform is a great alternative to carrying around a fragile, easy to lose
USB stick everywhere you go, as with this program you can access your files just by remembering a password and a username. You can also
easily sync your work across devices. Ever heard of plugging in a USB into your phone to revise on your upcoming presentation? I think not,
but with Cloudio it is not just possible, but also very easy and accessible.

This project was started as a hobby and there are no plans to expand it to commercial use. It is just being developed
by a beginner web-developer for fun and better learning the arts of programming.
&nbsp;

&nbsp;

### Stack specifications

Cloudio uses the LAMP stack to run in a docker container.

LINUX: `Debian GNU/Linux 9`

APACHE: `2.4.38`

MYSQL: `5.7.22`

PHP: `7.3`
&nbsp;

&nbsp;

### Project file structure
```
Cloudio:
    img/:
        error_icon.svg
        logo.svg

    user_folders/:
        .htaccess

    functions.php
    index.php
    Mysql.php
    mysql_connect.php
    mysql_credentials.php [SCRIPT:
        const DB_USER="";
        const DB_PASSWORD="";
    ]
    process_signup_request.php
    website.php
    style.css
    php.ini
    README.md
```
&nbsp;

### Database structure
```
cloudio:
    user_accounts:
        username VARCHAR(16)
        spaceused_b BIGINT(20) DEFAULT=0
        password VARCHAR(64)

    user_files:
        owner VARCHAR(16)
        name VARCHAR(225)
        size VARCHAR(6)
```
MySQL query to replicate database:
```
CREATE DATABASE cloudio;
USE cloudio;
CREATE TABLE user_accounts(
     username VARCHAR(16),
     spaceused_b BIGINT NOT NULL DEFAULT 0,
     password VARCHAR(64));
CREATE TABLE user_files(
    owner VARCHAR(16) NOT NULL,
    name VARCHAR(225) NOT NULL,
    size VARCHAR(6) NOT NULL);
```
