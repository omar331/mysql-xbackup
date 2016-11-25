<?php
namespace MysqlBackup;

use JMS\Serializer\Exception\RuntimeException;
use \JMS\Serializer\SerializerBuilder;

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'JMS\Serializer\Annotation', __DIR__.'/../../vendor/jms/serializer/src'
);

class BackupManager
{
    // general backup configurarion
    protected $config;

    // filename where backup information is stored
    protected $backupInfoFile = 'xtrabackup_info';

    protected $serializer;


    public function __construct(Array $config = [])
    {
        if ( ( ! array_key_exists('innobackupex_command', $config) ) or ( !file_exists( $config['innobackupex_command']) ) ) {
            throw new RuntimeException('"innobackupex_command" is not specified or non existent.');
        }

        $backupDataDir = $config['backup_data_dir'];
        if (!is_dir($backupDataDir)) {
            throw new RuntimeException(sprintf('Data directory %s doesn\'t exists', $backupDataDir));
        }

        $this->serializer = SerializerBuilder::create()->build();

        $this->config = $config;
    }


    /**
     * Run backup procedure accordingly config file
     */
    public function run()
    {
        echo "Deciding backup level to be perfomed...\n";

        $lastFullBackup = $this->getLatestFullBackup();

        // No full backup yet? Performs the first one
        if ( ! $lastFullBackup ) {
            echo " ---> Performing a FULL backup \n";
            $this->performFullBackup();

            return;
        }

        // ... otherwise performe a new incremental backup, unless
        // it reached the maximum
        $incrementalBackups = $this->getIncremetalBackupsOfBaseFullBackup( $lastFullBackup->getSubdir() );

        if ( sizeof($incrementalBackups) < $this->config['incremental_per_full'] ) {
            echo " ---> Performing an INCREMENTAL backup \n";

            $this->performIncrementalBackup( $lastFullBackup->getSubdir() );

            return;
        } else {
            echo " ---> Performing a FULL backup \n";
            $this->performFullBackup();
        }

        // Prune backup dir
        $this->prune();
    }


    /**
     * Perform a full backup
     */
    public function performFullBackup() {
        $backupDataDir = $this->config['backup_data_dir'];
        $command = sprintf("%s %s 2>/dev/null 2>&1", $this->config['innobackupex_command'], $backupDataDir);
        exec( $command,  $output, $result );
    }



    /**
     * Perform a incremental backup based on a certain fullbackup
     * @param null $baseFullBackupSubdir
     */
    public function performIncrementalBackup( $baseFullBackupSubdir  ) {
        $backupDataDir = $this->config['backup_data_dir'];
        $command = sprintf("%s --incremental %s --incremental-basedir=%s%s%s  2>/dev/null 2>&1", $this->config['innobackupex_command'], $backupDataDir, $this->config['backup_data_dir'], DIRECTORY_SEPARATOR, $baseFullBackupSubdir );
        exec( $command,  $output, $result );
    }



    /**
     * Removes old backup entries
     */
    public function prune() {
        echo "Pruning backup directory \n";

    }




    /**
     * @return string
     */
    public function getBackupInfoFile()
    {
        return $this->backupInfoFile;
    }

    /**
     * @param string $backupInfoFile
     */
    public function setBackupInfoFile($backupInfoFile)
    {
        $this->backupInfoFile = $backupInfoFile;
    }




    /**
     * Gets a list of all incremental backups of a certain full backup
     *
     * @param $baseFullBackupSubdir
     *
     * @return array
     *
     * @internal param $baseFullBackup
     *
     */
    public function getIncremetalBackupsOfBaseFullBackup( $baseFullBackupSubdir ) {
        $list = [];

        /** @var BackupInfo $backupInfo */
        foreach( $this->getBackupList() as $backupInfo ) {
            if ( $backupInfo->getIncremental() != 'Y' ) continue;

            if ( $backupInfo->getIncrementalBaseBackup() != $baseFullBackupSubdir ) continue;

            $list[] = $backupInfo;
        }

        return $list;
    }


    /**
     * Get lastest fullbackup, if any
     *
     * @return null|BackupInfo
     */
    protected function getLatestFullBackup() {
        $backups = $this->getFullBackupList();

        if ( sizeof($backups) == 0 ) return;

        return $backups[0];
    }


    /**
     * Get all full backups within backup directory
     * @return array
     */
    protected function getFullBackupList()
    {
        $files = [];

        $backupsInfos = $this->getBackupList();

        /** @var BackupInfo $backupInfo */
        foreach ($backupsInfos as $backupInfo) {
            if ( $backupInfo->getIncremental() == 'N' ) {
                $files[] = $backupInfo;
            }
        }

        return $files;
    }


    /**
     * Extract backup information
     *
     * @param $backupSubDir subdirectory where backup is stored
     * @return \MysqlBackup\BackupInfo backup information
     *
     * @throws RuntimeException
     */
    protected function extractBackupInfo($backupSubDir)
    {
        $infos = [];

        $backupPath = $this->getBackupFullPath( $backupSubDir );

        $fileInfoPath = sprintf('%s%s%s', $backupPath, DIRECTORY_SEPARATOR, $this->getBackupInfoFile() );

        $handle = fopen($fileInfoPath, "r");
        if ( ! $handle ) {
            throw new RuntimeException('Failed to open backup information file %s', $fileInfoPath);
        }

        while (($line = fgets($handle)) !== false) {
            if ( preg_match('/^([^\s]+) = (.*)$/', $line, $r )  ) {
                $infos[ trim($r[1]) ] = trim($r[2]);
            }
        }

        /** @var \MysqlBackup\BackupInfo $info */
        $info = $this->serializer->deserialize( json_encode($infos), 'MysqlBackup\BackupInfo', 'json');

        $info->setSubdir($backupSubDir);

        // if it's incremental, get full backup base
        if ( $info->getIncremental() == 'Y' ) {
            $info->setIncrementalBaseBackup( $this->extractIncrementalBaseBackup($info->getToolCommand()) );
        }

        return $info;
    }


    /**
     * Get full path for a certain backup
     * @param $backupSubDir
     * @return string
     */
    protected function getBackupFullPath($backupSubDir) {
        return sprintf('%s%s%s', $this->config['backup_data_dir'], DIRECTORY_SEPARATOR, $backupSubDir);
    }


    /**
     * Get the list of all backup within main backup directory,
     * sorted from lastest to oldest
     *
     * @return array
     */
    protected function getBackupList()
    {
        $backupDataDir = $this->config['backup_data_dir'];

        $files = [];

        if ($dh = opendir($backupDataDir)) {
            while (($file = readdir($dh)) !== false) {
                if ( preg_match('/^\./', $file) ) continue;

                $files[] = $this->extractBackupInfo( $file );
            }
            closedir($dh);
        }

        rsort($files);

        return $files;
    }


    /**
     * Extract base full backup from tool command line
     * @param $toolCommand
     *
     * @return null|string
     */
    public function extractIncrementalBaseBackup( $toolCommand )
    {
        // try to get the fullpath
        if (!preg_match('/--incremental-basedir=(.*)/', $toolCommand, $r)) return null;
        $baseDir = $r[1];

        // try to get only the filename
        if ( ! preg_match('#([^\/\\\\]*)$#', $baseDir, $r ) ) return null;

        return $r[1];
    }

}



