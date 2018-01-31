<?php
namespace MysqlBackup;

use JMS\Serializer\Exception\RuntimeException;
use \JMS\Serializer\SerializerBuilder;

use MysqlBackup\BackupInfoNotFoundException;

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'JMS\Serializer\Annotation', __DIR__.'/../../vendor/jms/serializer/src'
);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


class BackupManager
{
    // general backup configurarion
    protected $config;

    // filename where backup information is stored
    protected $backupInfoFile = 'xtrabackup_info';

    // file containing checkbox information
    protected $backupCheckpointFile = "xtrabackup_checkpoints";

    protected $serializer;

    /** @var Logger  */
    protected $logger;

    /** @var Filesystem  */
    protected $filesystem;


    /** @var  Finder */
    protected $finder;


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

        // create a log channel
        $this->logger = new Logger('name');
        $this->logger->pushHandler(new StreamHandler( $config['logfile'], Logger::INFO));

        $this->filesystem = new Filesystem();
        $this->finder = new Finder();
    }


    /**
     * Run backup procedure accordingly config file
     */
    public function run()
    {
        $this->logger->info("Starting backup. Deciding backup level to be performed...");

        $lastFullBackup = $this->getLatestFullBackup();

        // No full backup yet? Performs the first one
        if ( ! $lastFullBackup ) {
            $this->logger->info("Performing a FULL backup ");
            $this->performFullBackup();

            return;
        }

        // ... otherwise performe a new incremental backup, unless
        // it reached the maximum
        $incrementalBackups = $this->getIncremetalBackupsOfBaseFullBackup( $lastFullBackup->getSubdir() );

        if ( sizeof($incrementalBackups) < $this->config['incremental_per_full'] ) {
            $this->logger->info("Performing an INCREMENTAL backup");

            $lastIncrementalBackup = end($incrementalBackups);


            $this->performIncrementalBackup( $lastIncrementalBackup->getSubdir() );
        } else {
            $this->logger->info("Performing a FULL backup");
            $this->performFullBackup();
        }

        // Prune backup dir
        $this->logger->info("Pruning older backups");
        $this->prune();

        $this->logger->info('Backup has been finished');
    }


    public function restore()
    {
        $this->logger->info("Restoring backup");

        $backupDataDir = $this->config['backup_data_dir'];

        /* Get the latest full backup */
        $latestFullBackup = $this->getLatestFullBackup();

        /* ... and its incremental ones */
        $incrementalBackups =  $this->getIncremetalBackupsOfBaseFullBackup( $latestFullBackup->getSubdir() );

        $fullBackupDir = sprintf('%s/%s', $backupDataDir, $latestFullBackup->getSubdir() );

        /*
         * Prepares the base full backup to be restored
         */
        $this->logger->info("Preparing full backup " . $latestFullBackup->getSubdir() );

        $command = sprintf("%s --apply-log --redo-only %s 2>/dev/null 2>&1",
                                        $this->config['innobackupex_command'],
                                        $fullBackupDir
                            );
        exec( $command,  $output, $result );

        /*
         * Prepares the incremental ones
         */
        $n = 0;
        foreach( $incrementalBackups as $incBkp ) {
            $n++;

            $this->logger->info("Preparing incremetal backup " . $incBkp->getSubdir() );

            $redoOnlyOpt = ( $n < sizeof($incrementalBackups)) ? "--redo-only" : "";

            $incrementalDir = sprintf('%s/%s', $backupDataDir, $incBkp->getSubdir() );

            $command = sprintf("%s --apply-log  %s %s --incremental-dir=%s 2>/dev/null 2>&1",
                $this->config['innobackupex_command'],
                $redoOnlyOpt,
                $fullBackupDir,
                $incrementalDir
            );

            exec( $command,  $output, $result );
        }

        $this->logger->info("Copying restored backup to data dir ");

        $command = sprintf("%s --copy-back  %s 2>/dev/null 2>&1",
            $this->config['innobackupex_command'],
            $fullBackupDir
        );
        exec( $command,  $output, $result );

        $this->logger->info('Restore has been finished');
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
     * Removes older backup entries
     */
    public function prune() {
        $count = 0;

        /** @var BackupInfo $backupInfo */
        foreach( $this->getFullBackupList() as $backupInfo ) {
            $count++;
            if ( $count <= $this->config['keep_full_backup'] ) continue;

            $this->removeBackup($backupInfo);
        }
    }

    /**
     * Removes a backup and its incrementals (if any)
     * @param BackupInfo $info
     */
    protected function removeBackup( BackupInfo $info ) {
        // if it's a full backup, get its incremental ones
        if ( $info->getIncremental() == 'N' ) {
            $incrementals = $this->getIncremetalBackupsOfBaseFullBackup( $info->getSubdir() );

            /** @var BackupInfo $incremental */
            foreach( $incrementals as $incrementalInfo ) {
                $this->removeBackup( $incrementalInfo, $this->backupInfoToArray($info) );
            }
        }

        // remove backup itself
        $this->logger->info( sprintf('Removing backup %s', $info->getSubdir() ),  $this->backupInfoToArray($info) );
        $this->removeBackupSubdir( $info->getSubdir() );
    }

    protected function removeBackupSubdir( $subdir ) {
        $fullBackupPath = $this->getBackupFullPath( $subdir );

//        $this->rmdir( $fullBackupPath );
    }


    /**
     * Removes a directory recursively
     * @param $dir full path to directory
     *
     */
    protected function rmdir( $dir ) {
        system("rm -rf ".escapeshellarg($dir));
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
     * @return string
     */
    public function getBackupCheckpointFile()
    {
        return $this->backupCheckpointFile;
    }

    /**
     * @param string $backupCheckpointFile
     * @return BackupManager
     */
    public function setBackupCheckpointFile($backupCheckpointFile)
    {
        $this->backupCheckpointFile = $backupCheckpointFile;
        return $this;
    }







    /**
     * Gets a list of all incremental backups of a certain full backup
     *
     * @param $baseFullBackupSubdir
     *
     * @return array<BackupInfo>
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

        /* Extracts backup info */
        $fileInfoPath = sprintf('%s%s%s', $backupPath, DIRECTORY_SEPARATOR, $this->getBackupInfoFile() );

        if ( ! file_exists($fileInfoPath) ) {
            throw new BackupInfoNotFoundException( sprintf("Couldn't open %s to get backup information", $fileInfoPath) );
        }

        $handle = fopen($fileInfoPath, "r");
        if ( ! $handle ) {
            throw new RuntimeException( sprintf('Failed to open backup information file %s', $fileInfoPath) );
        }

        while (($line = fgets($handle)) !== false) {
            if ( preg_match('/^([^\s]+) = (.*)$/', $line, $r )  ) {
                $infos[ trim($r[1]) ] = trim($r[2]);
            }
        }
        fclose($handle);


        /* Extracts backup checkpoint info */
        $fileCheckpointPath = sprintf('%s%s%s', $backupPath, DIRECTORY_SEPARATOR, $this->getBackupCheckpointFile() );

        if ( ! file_exists($fileCheckpointPath) ) {
            throw new BackupInfoNotFoundException( sprintf("Couldn't open %s to get backup checkpoint information", $fileCheckpointPath) );
        }

        $handle = fopen($fileCheckpointPath, "r");
        if ( ! $handle ) {
            throw new RuntimeException( sprintf('Failed to open backup checkpoint information file %s', $fileCheckpointPath) );
        }

        while (($line = fgets($handle)) !== false) {
            if ( preg_match('/^([^\s]+) = (.*)$/', $line, $r )  ) {
                $infos[ trim($r[1]) ] = trim($r[2]);
            }
        }
        fclose($handle);

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
     * @return BackupInfo[]
     */
    protected function getBackupList()
    {
        $backupDataDir = $this->config['backup_data_dir'];


        $files = [];

        $dh = opendir($backupDataDir);

        while (($file = readdir($dh)) ) {

            if ( preg_match('/^\./', $file) ) continue;

            try {
                $backupInfo = $this->extractBackupInfo( $file );
                $files[] = $backupInfo;
            } catch ( BackupInfoNotFoundException $e ) {
                $this->logger->warn( sprintf("Failed to retrieve backup info. File %s  Message: %s ", $file, $e->getMessage() ) );
            }

        }
        closedir($dh);

        usort($files,
            function($a, $b) {
                $ad = $a->getStartTime();
                $bd = $b->getStartTime();

                if ( $ad == $bd ) return 0;

                return ($ad<$bd)?-1:1;
            }
        );


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


    /**
     * Convert one backup info object into array
     *
     * @param BackupInfo $backupInfo
     * @return mixed
     */
    public function backupInfoToArray( BackupInfo $backupInfo ) {
        return json_decode($this->serializer->serialize($backupInfo,'json'), true);
    }




    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

}



