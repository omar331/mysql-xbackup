# Simple PHP innobackupex wrapper 

Manage your Mysql backups holding as many full and incremental backups you want.

Every time this script runs it manages to perform a full or incremental backup accordingly
your configuration file. Furthermore it prunes your backup set holding just the newest ones.


### Features



### System Requirements

* **Percona XtraBackup** performs online non-blocking, tightly compressed, highly secure backups on transactional systems so that applications remain fully available during planned maintenance windows.
 See installation instructions at [https://www.percona.com/software/mysql-database/percona-xtrabackup]
 
* **Composer** PHP dependency manager [https://getcomposer.org/]  

* **PHP 5.3+** 


### Installation

Once you meet the system requirementes, run

```
composer require omar331/mysql-backup
```


### Usage

Copy the file ```config.php.SAMPLE``` as ```config.php```, edit the file and setup your environment.

And the run

```
sudo php backup-run.php
``` 

