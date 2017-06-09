<?php
/*
 * This file is part of the package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Songshenzong\Log\Bridge;

use BasicLogger;
use Songshenzong\Log\DataCollector\DataCollector;
use Propel;
use PropelConfiguration;
use PropelPDO;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * A Propel logger which acts as a data collector
 *
 * http://propelorm.org/
 *
 * Will log queries and display them using the SQLQueries widget.
 * You can provide a LoggerInterface object to forward non-query related message to.
 *
 * Example:
 * <code>
 * $debugbar->addCollector(new PropelCollector($debugbar['messages']));
 * PropelCollector::enablePropelProfiling();
 * </code>
 */
class PropelCollector extends DataCollector implements BasicLogger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $statements = [];

    /**
     * @var int
     */
    protected $accumulatedTime = 0;

    /**
     * @var int
     */
    protected $peakMemory = 0;

    /**
     * Sets the needed configuration option in propel to enable query logging
     *
     * @param PropelConfiguration $config Apply profiling on a specific config
     */
    public static function enablePropelProfiling(PropelConfiguration $config = null)
    {
        if ($config === null) {
            $config = Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT);
        }
        $config->setParameter('debugpdo.logging.details.method.enabled', true);
        $config->setParameter('debugpdo.logging.details.time.enabled', true);
        $config->setParameter('debugpdo.logging.details.mem.enabled', true);
        $allMethods = [
            'PropelPDO::__construct',       // logs connection opening
            'PropelPDO::__destruct',        // logs connection close
            'PropelPDO::exec',              // logs a query
            'PropelPDO::query',             // logs a query
            'PropelPDO::beginTransaction',  // logs a transaction begin
            'PropelPDO::commit',            // logs a transaction commit
            'PropelPDO::rollBack',          // logs a transaction rollBack (watch out for the capital 'B')
            'DebugPDOStatement::execute',   // logs a query from a prepared statement
        ];
        $config->setParameter('debugpdo.logging.methods', $allMethods, false);
    }

    /**
     * @param LoggerInterface $logger A logger to forward non-query log lines to
     * @param PropelPDO       $conn   Bound this collector to a connection only
     */
    public function __construct(LoggerInterface $logger = null, PropelPDO $conn = null)
    {
        if ($conn) {
            $conn->setLogger($this);
        } else {
            Propel::setLogger($this);
        }
        $this->logger             = $logger;
        $this->logQueriesToLogger = false;
    }

    /**
     * @param bool $enable
     *
     * @return $this
     */
    public function setLogQueriesToLogger($enable = true)
    {
        $this->logQueriesToLogger = $enable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLogQueriesToLogger()
    {
        return $this->logQueriesToLogger;
    }

    /**
     * @param $m
     */
    public function emergency($m)
    {
        $this->log($m, Propel::LOG_EMERG);
    }

    /**
     * @param $m
     */
    public function alert($m)
    {
        $this->log($m, Propel::LOG_ALERT);
    }

    /**
     * @param $m
     */
    public function crit($m)
    {
        $this->log($m, Propel::LOG_CRIT);
    }

    /**
     * @param $m
     */
    public function err($m)
    {
        $this->log($m, Propel::LOG_ERR);
    }

    /**
     * @param $m
     */
    public function warning($m)
    {
        $this->log($m, Propel::LOG_WARNING);
    }

    /**
     * @param $m
     */
    public function notice($m)
    {
        $this->log($m, Propel::LOG_NOTICE);
    }

    /**
     * @param $m
     */
    public function info($m)
    {
        $this->log($m, Propel::LOG_INFO);
    }

    /**
     * @param $m
     */
    public function debug($m)
    {
        $this->log($m, Propel::LOG_DEBUG);
    }

    /**
     * @param      $message
     * @param null $severity
     */
    public function log($message, $severity = null)
    {
        if (strpos($message, 'DebugPDOStatement::execute') !== false) {
            list($sql, $duration_str) = $this->parseAndLogSqlQuery($message);
            if (!$this->logQueriesToLogger) {
                return;
            }
            $message = "$sql ($duration_str)";
        }

        if ($this->logger !== null) {
            $this->logger->log($this->convertLogLevel($severity), $message);
        }
    }

    /**
     * Converts Propel log levels to PSR log levels
     *
     * @param int $level
     *
     * @return string
     */
    protected function convertLogLevel($level)
    {
        $map = [
            Propel::LOG_EMERG   => LogLevel::EMERGENCY,
            Propel::LOG_ALERT   => LogLevel::ALERT,
            Propel::LOG_CRIT    => LogLevel::CRITICAL,
            Propel::LOG_ERR     => LogLevel::ERROR,
            Propel::LOG_WARNING => LogLevel::WARNING,
            Propel::LOG_NOTICE  => LogLevel::NOTICE,
            Propel::LOG_DEBUG   => LogLevel::DEBUG
        ];
        return $map[$level];
    }

    /**
     * Parse a log line to extract query information
     *
     * @param string $message
     *
     * @return array
     */
    protected function parseAndLogSqlQuery($message)
    {
        $parts = explode('|', $message, 4);
        $sql   = trim($parts[3]);

        $duration = 0;
        if (preg_match('/([0-9]+\.[0-9]+)/', $parts[1], $matches)) {
            $duration = (float) $matches[1];
        }

        $memory = 0;
        if (preg_match('/([0-9]+\.[0-9]+) ([A-Z]{1,2})/', $parts[2], $matches)) {
            $memory = (float) $matches[1];
            if ($matches[2] == 'KB') {
                $memory *= 1024;
            } else if ($matches[2] == 'MB') {
                $memory *= 1024 * 1024;
            }
        }

        $this->statements[]    = [
            'sql'          => $sql,
            'is_success'   => true,
            'duration'     => $duration,
            'duration_str' => $this->formatDuration($duration),
            'memory'       => $memory,
            'memory_str'   => $this->formatBytes($memory)
        ];
        $this->accumulatedTime += $duration;
        $this->peakMemory      = max($this->peakMemory, $memory);
        return [$sql, $this->formatDuration($duration)];
    }

    /**
     * {@inheritDoc}
     */
    /**
     * @return array
     */
    public function collect()
    {
        return [
            'nb_statements'            => count($this->statements),
            'nb_failed_statements'     => 0,
            'accumulated_duration'     => $this->accumulatedTime,
            'accumulated_duration_str' => $this->formatDuration($this->accumulatedTime),
            'peak_memory_usage'        => $this->peakMemory,
            'peak_memory_usage_str'    => $this->formatBytes($this->peakMemory),
            'statements'               => $this->statements
        ];
    }

    /**
     * {@inheritDoc}
     */
    /**
     * @return string
     */
    public function getName()
    {
        return 'propel';
    }
}
