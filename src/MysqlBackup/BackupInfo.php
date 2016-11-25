<?php
namespace MysqlBackup;

use JMS\Serializer\Annotation\Type;


class BackupInfo {
	protected  $subdir;

	// base full backup (only for incremental backups)
	protected  $incrementalBaseBackup;

	/**
	 * @return mixed
	 */
	public function getIncrementalBaseBackup()
	{
		return $this->incrementalBaseBackup;
	}

	/**
	 * @param mixed $incrementalBaseBackup
	 * @return BackupInfo
	 */
	public function setIncrementalBaseBackup($incrementalBaseBackup)
	{
		$this->incrementalBaseBackup = $incrementalBaseBackup;
		return $this;
	}


	/**
	 * @Type("string")
	 */
	protected  $uuid;

	/**
	 * @Type("string")
	 */
	protected  $toolName;

	/**
	 * @Type("string")
	 */
	protected  $toolCommand;

	/**
	 * @Type("string")
	 */
	protected  $toolVersion;

	/**
	 * @Type("string")
	 */
	protected  $ibbackupVersion;

	/**
	 * @Type("string")
	 */
	protected  $serverVersion;

	/**
	 * @Type("string")
	 */
	protected  $startTime;

	/**
	 * @Type("string")
	 */
	protected  $endTime;

	/**
	 * @Type("string")
	 */
	protected  $lockTime;

	/**
	 * @Type("string")
	 */
	protected  $innodbFromLsn;

	/**
	 * @Type("string")
	 */
	protected  $innodbToLsn;

	/**
	 * @Type("string")
	 */
	protected  $partial;

	/**
	 * @Type("string")
	 */
	protected  $incremental;

	/**
	 * @Type("string")
	 */
	protected  $format;

	/**
	 * @Type("string")
	 */
	protected  $compact;

	/**
	 * @Type("string")
	 */
	protected  $compressed;

	/**
	 * @Type("string")
	 */
	protected  $encrypted;

	/**
	 * @return mixed
	 */
	public function getSubdir()
	{
		return $this->subdir;
	}

	/**
	 * @param mixed $subdir
	 * @return BackupInfo
	 */
	public function setSubdir($subdir)
	{
		$this->subdir = $subdir;
		return $this;
	}


	/**
	 * @return mixed
	 */
	public function getUuid()
	{
		return $this->uuid;
	}

	/**
	 * @param mixed $uuid
	 * @return BackupInfo
	 */
	public function setUuid($uuid)
	{
		$this->uuid = $uuid;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getToolName()
	{
		return $this->toolName;
	}

	/**
	 * @param mixed $toolName
	 * @return BackupInfo
	 */
	public function setToolName($toolName)
	{
		$this->toolName = $toolName;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getToolCommand()
	{
		return $this->toolCommand;
	}

	/**
	 * @param mixed $toolCommand
	 * @return BackupInfo
	 */
	public function setToolCommand($toolCommand)
	{
		$this->toolCommand = $toolCommand;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getToolVersion()
	{
		return $this->toolVersion;
	}

	/**
	 * @param mixed $toolVersion
	 * @return BackupInfo
	 */
	public function setToolVersion($toolVersion)
	{
		$this->toolVersion = $toolVersion;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getIbbackupVersion()
	{
		return $this->ibbackupVersion;
	}

	/**
	 * @param mixed $ibbackupVersion
	 * @return BackupInfo
	 */
	public function setIbbackupVersion($ibbackupVersion)
	{
		$this->ibbackupVersion = $ibbackupVersion;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getServerVersion()
	{
		return $this->serverVersion;
	}

	/**
	 * @param mixed $serverVersion
	 * @return BackupInfo
	 */
	public function setServerVersion($serverVersion)
	{
		$this->serverVersion = $serverVersion;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getStartTime()
	{
		return $this->startTime;
	}

	/**
	 * @param mixed $startTime
	 * @return BackupInfo
	 */
	public function setStartTime($startTime)
	{
		$this->startTime = $startTime;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEndTime()
	{
		return $this->endTime;
	}

	/**
	 * @param mixed $endTime
	 * @return BackupInfo
	 */
	public function setEndTime($endTime)
	{
		$this->endTime = $endTime;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getLockTime()
	{
		return $this->lockTime;
	}

	/**
	 * @param mixed $lockTime
	 * @return BackupInfo
	 */
	public function setLockTime($lockTime)
	{
		$this->lockTime = $lockTime;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getInnodbFromLsn()
	{
		return $this->innodbFromLsn;
	}

	/**
	 * @param mixed $innodbFromLsn
	 * @return BackupInfo
	 */
	public function setInnodbFromLsn($innodbFromLsn)
	{
		$this->innodbFromLsn = $innodbFromLsn;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getInnodbToLsn()
	{
		return $this->innodbToLsn;
	}

	/**
	 * @param mixed $innodbToLsn
	 * @return BackupInfo
	 */
	public function setInnodbToLsn($innodbToLsn)
	{
		$this->innodbToLsn = $innodbToLsn;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPartial()
	{
		return $this->partial;
	}

	/**
	 * @param mixed $partial
	 * @return BackupInfo
	 */
	public function setPartial($partial)
	{
		$this->partial = $partial;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getIncremental()
	{
		return $this->incremental;
	}

	/**
	 * @param mixed $incremental
	 * @return BackupInfo
	 */
	public function setIncremental($incremental)
	{
		$this->incremental = $incremental;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * @param mixed $format
	 * @return BackupInfo
	 */
	public function setFormat($format)
	{
		$this->format = $format;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCompact()
	{
		return $this->compact;
	}

	/**
	 * @param mixed $compact
	 * @return BackupInfo
	 */
	public function setCompact($compact)
	{
		$this->compact = $compact;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCompressed()
	{
		return $this->compressed;
	}

	/**
	 * @param mixed $compressed
	 * @return BackupInfo
	 */
	public function setCompressed($compressed)
	{
		$this->compressed = $compressed;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEncrypted()
	{
		return $this->encrypted;
	}

	/**
	 * @param mixed $encrypted
	 * @return BackupInfo
	 */
	public function setEncrypted($encrypted)
	{
		$this->encrypted = $encrypted;
		return $this;
	}
}