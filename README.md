# Simple PHP innobackupex wrapper 

Manage your Mysql backups holding as many full and incremental backups you want.

Every time this script runs it manages to perform a full or incremental backup accordingly
your configuration file. Furthermore it prunes your backup set holding just the newest ones.


### Features

* Hot non-blocking backup for MySql, MariaDB and Percona Server
* Full and Incremental backups
* Detailed log 
* Sanitization - the system prunes older backups, leaving just the amount defined on the configuration file.


### System Requirements

* **Percona XtraBackup** performs online non-blocking, tightly compressed, highly secure backups on transactional systems so that applications remain fully available during planned maintenance windows.
 See installation instructions at [https://www.percona.com/software/mysql-database/percona-xtrabackup]
 
* **Composer** PHP dependency manager [https://getcomposer.org/]  

* **PHP 5.3+** 


### Installation

Once you meet the system requirementes, run

* Download and extract the latest version of **my-back-phpex** in Github.

* Run 

```
composer install
```

Copy the file ```config.php.SAMPLE``` as ```config.php```, edit the file and setup your environment.


### Usage

Run

```
sudo php backup-run.php
``` 

