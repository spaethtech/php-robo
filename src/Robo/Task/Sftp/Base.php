<?php
declare(strict_types=1);

namespace MVQN\Robo\Task\Sftp;

use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use MVQN\SFTP\SftpClient;
use MVQN\SFTP\Exceptions\AuthenticationException;
use MVQN\SFTP\Exceptions\InitializationException;
use MVQN\SFTP\Exceptions\LocalStreamException;
use MVQN\SFTP\Exceptions\MissingExtensionException;
use MVQN\SFTP\Exceptions\RemoteConnectionException;
use MVQN\SFTP\Exceptions\RemoteStreamException;

/**
 * Exposes some SFTP functionality.
 *
 * ``` php
 * <?php
 * $this->taskSftp(<host>, <port>)
 *     ->login(<user>, <pass>)                  // Authenticates with the remote server.
 *     ->download(<remote>, <local>)            // Downloads the "remote" path to the "local" path.
 *     ->run();
 * ?>
 *
 * $this->taskSftp(<host>, <port>)
 *     ->login(<user>, <pass>)                  // Authenticates with the remote server.
 *     ->upload(<local>, <remote>)              // Downloads the "remote" path to the "local" path.
 *     ->run();
 * ?>
 * ```
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class Base extends BaseTask
{
    protected const DEFAULT_CONFIG_FILE = "sftp.config.json";
    protected const DEFAULT_JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    protected $host;
    protected $port;
    protected $user;
    protected $pass;

    protected $remoteBase = "";
    protected $localBase = "";

    protected $remoteMaps = [];
    protected $localMaps = [];


    /**
     * SftpTasks constructor.
     *
     * @param string    $host   The host to use when connecting over SFTP.
     * @param int       $port   The port to use when connecting over SFTP.
     * @param string    $user   The user to use when connecting over SFTP.
     * @param string    $pass   The pass to use when connecting over SFTP.
     */
    /*
    public function __construct(string $host = "", int $port = 22, string $user = "", string $pass = "")
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }
    */

    /**
     * Base constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->fromConfiguration($data);
    }

    #region Configuration: Manual

    /**
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setUser(string $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $pass
     * @return $this
     */
    public function setPass(string $pass): self
    {
        $this->pass = $pass;
        return $this;
    }

    /**
     * @param string $remote
     * @return $this
     */
    public function remoteBase(string $remote)
    {
        $this->remoteBase = $remote;
        return $this;
    }

    /**
     * @param string $local
     * @return $this
     */
    public function localBase(string $local)
    {
        $this->localBase = $local;
        return $this;
    }

    protected function remoteMaps(array $remoteMaps)
    {
        $this->remoteMaps = $remoteMaps;
        return $this;
    }

    protected function localMaps(array $localMaps)
    {
        $this->localMaps = $localMaps;
        return $this;
    }

    #endregion

    #region Configuration: Persistent

    /*
    public function setConfiguration(array $configuration): self
    {
        foreach($configuration as $key => $value)
            if(property_exists($this, $key))
                $this->$key = $value;

        return $this;
    }
    */



    protected function fromConfiguration(array $data)
    {
        $this->host         = $data["host"] ?? "";
        $this->port         = $data["port"] ?? 22;
        $this->user         = $data["user"] ?? "";
        $this->pass         = $data["pass"] ?? "";

        $this->remoteBase   = $data["base"]["remote"] ?? "";
        $this->localBase    = $data["base"]["local"] ?? "";

        $this->remoteMaps   = $data["maps"]["remote"] ?? "";
        $this->localMaps    = $data["maps"]["local"] ?? "";
    }


    /**
     * @param callable $configurator
     * @param array $arguments
     * @return $this
     */
    public function funcConfiguration(callable $configurator, ...$arguments): self
    {
        $current = json_decode((string)$this, true);
        $configuration = $configurator($current, ...$arguments);

        $this->fromConfiguration($configuration);

        return $this;
        //return new $this($configuration);
    }


    public function __toString()
    {
        $data = [
            "host"          => $this->host,
            "port"          => $this->port,
            "user"          => $this->user,
            "pass"          => $this->pass,

            "base"  => [
                "remote"    => $this->remoteBase,
                "local"     => $this->localBase,
            ],

            "maps"  => [
                "remote"    => $this->remoteMaps,
                "local"     => $this->localMaps,
            ]
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $path
     * @return $this
     * @throws Exceptions\ConfigurationMissingException
     * @throws Exceptions\ConfigurationParsingException
     */
    public function loadConfiguration(string $path = self::DEFAULT_CONFIG_FILE): self
    {
        if(!($real = realpath($path)))
            throw new Exceptions\ConfigurationMissingException(
                get_class($this)."::loadConfiguration() could not locate the configuration file '$path'!"
            );

        $data = json_decode(file_get_contents($real), true);

        if(json_last_error() !== JSON_ERROR_NONE)
            throw new Exceptions\ConfigurationParsingException(
                get_class($this)."::loadConfiguration() encountered the following error(s) when parsing '$path': ".
                json_last_error_msg()
            );

        $this->fromConfiguration($data);

        /*
        $this->host         = $data["host"];
        $this->port         = $data["port"];
        $this->user         = $data["user"];
        $this->pass         = $data["pass"];

        $this->remoteBase   = $data["base"]["remote"];
        $this->localBase    = $data["base"]["local"];

        $this->remoteMaps   = $data["maps"]["remote"];
        $this->localMaps    = $data["maps"]["local"];
        */

        $this->printTaskInfo("Configuration loaded successfully!");

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function saveConfiguration(string $path = self::DEFAULT_CONFIG_FILE): self
    {
        $data = $this->toConfiguration();

        file_put_contents($path, json_encode($data, self::DEFAULT_JSON_OPTIONS));

        $this->printTaskInfo("Configuration saved successfully!");

        return $this;
    }


    #endregion







}
