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
    protected const DEFAULT_CONFIG_PATH = "sftp.config.json";
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
    public function __construct(string $host = "", int $port = 22, string $user = "", string $pass = "")
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
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

    public function remoteMaps(array $remoteMaps)
    {
        $this->remoteMaps = $remoteMaps;
        return $this;
    }

    public function localMaps(array $localMaps)
    {
        $this->localMaps = $localMaps;
        return $this;
    }

    #endregion

    #region Configuration: Persistent

    /**
     * @param string $path
     * @return $this
     */
    public function loadConfiguration(string $path = self::DEFAULT_CONFIG_PATH): self
    {
        $json = file_get_contents($path);

        $data = json_decode($json, true);

        $this->host         = $data["host"];
        $this->port         = $data["port"];
        $this->user         = $data["user"];
        $this->pass         = $data["pass"];

        $this->remoteBase   = $data["base"]["remote"];
        $this->localBase    = $data["base"]["local"];

        $this->remoteMaps   = $data["maps"]["remote"];
        $this->localMaps    = $data["maps"]["local"];

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function saveConfiguration(string $path = self::DEFAULT_CONFIG_PATH): self
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

        $json = json_encode($data, self::DEFAULT_JSON_OPTIONS);

        file_put_contents($path, $json);

        return $this;
    }

    #endregion







}
