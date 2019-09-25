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
    #region CONSTANTS

    /**
     * The default config file.
     */
    protected const DEFAULT_CONFIG_FILE = "sftp.config.json";

    /**
     * The default JSON encoding options.
     */
    protected const DEFAULT_JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    #endregion

    #region OPTIONS

    /**
     * @var array The current options, initialized with defaults.
     */
    protected $options = [
        "host"          => "",
        "port"          => 22,
        "user"          => "",
        "pass"          => "",

        "base"  => [
            "remote"    => "",
            "local"     => "",
        ],

        "maps"  => [
            "remote"    => [],
            "local"     => [],
        ]
    ];

    #endregion

    #region CONSTRUCTOR / DESTRUCTOR

    /**
     * Base constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    #endregion

    #region SETTERS

    /**
     * Sets any desired options, overriding existing or default options.
     *
     * @param array $options The array of options.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Sets the 'host' to use for this SFTP connection.
     *
     * @param string $host The 'host' to use for this SFTP connection.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setHost(string $host): self
    {
        $this->options["host"] = $host;
        return $this;
    }

    /**
     * Sets the 'port' to use for this SFTP connection.
     *
     * @param int $port The 'port' to use for this SFTP connection.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setPort(int $port): self
    {
        $this->options["port"] = $port;
        return $this;
    }

    /**
     * Sets the 'user' to use for this SFTP connection.
     *
     * @param string $user The 'user' to use for this SFTP connection.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setUser(string $user): self
    {
        $this->options["user"] = $user;
        return $this;
    }

    /**
     * Sets the 'pass' to use for this SFTP connection.
     *
     * @param string $pass The 'pass' to use for this SFTP connection.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setPass(string $pass): self
    {
        $this->options["pass"] = $pass;
        return $this;
    }

    /**
     * Sets an optional 'remote' base path to prefix relative paths.
     *
     * @param string $remote A 'remote' base path to prefix relative paths.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setRemoteBase(string $remote)
    {
        $this->options["base"]["remote"] = $remote;
        return $this;
    }

    /**
     * Sets an optional 'local' base path to prefix relative paths.
     *
     * @param string $local A 'local' base path to prefix relative paths.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setLocalBase(string $local)
    {
        $this->options["base"]["local"] = $local;
        return $this;
    }

    /**
     * Sets any 'remote to local' path mappings.
     *
     * @param array $remoteMaps An array of 'remote to local' path mappings.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     *
     * @deprecated Use the newer map()/maps() methods in the inheriting classes.
     */
    protected function setRemoteMaps(array $remoteMaps)
    {
        $this->options["maps"]["remote"] = $remoteMaps;
        return $this;
    }

    /**
     * Sets any 'local to remote' path mappings.
     *
     * @param array $localMaps An array of 'local to remote' path mappings.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     *
     * @deprecated Use the newer map()/maps() methods in the inheriting classes.
     */
    protected function setLocalMaps(array $localMaps)
    {
        $this->options["maps"]["local"] = $localMaps;
        return $this;
    }

    #endregion

    #region CONFIGURATION

    /**
     * Loads options from the specified configuration file.
     *
     * @param string $path The file path from which SFTP options should be loaded, defaults to "sftp.config.json".
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     *
     * @throws Exceptions\ConfigurationMissingException
     * @throws Exceptions\ConfigurationParsingException
     */
    public function loadConfiguration(string $path = self::DEFAULT_CONFIG_FILE): self
    {
        //if(!($real = realpath($path)))
            //throw new Exceptions\ConfigurationMissingException(
            //    get_class($this)."::loadConfiguration() could not locate the configuration file '$path'!"
            //);

        if($real = realpath($path)) {
            $configuration = json_decode(file_get_contents($real), true);

            if (json_last_error() !== JSON_ERROR_NONE)
                throw new Exceptions\ConfigurationParsingException(
                    get_class($this) . "::loadConfiguration() encountered the following error(s) when parsing '$path': " .
                    json_last_error_msg()
                );

            $this->setOptions($configuration);

            $this->printTaskInfo("Configuration loaded successfully!");
        }

        return $this;
    }

    /**
     * Loads options to the specified configuration file.
     *
     * @param string $path The file path to which SFTP options should be saved, defaults to "sftp.config.json".
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function saveConfiguration(string $path = self::DEFAULT_CONFIG_FILE): self
    {
        file_put_contents($path, json_encode($this->options, self::DEFAULT_JSON_OPTIONS));

        $this->printTaskInfo("Configuration saved successfully!");

        return $this;
    }

    /**
     * A closure-based configuration proxy, used to verify/modify values prior to setting them on this SFTP object.
     *
     * @param callable $configurator The closure to call for verification/modification of the configuration options.
     * @param array $arguments An optional set of arguments to pass to the closure.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function funcConfiguration(callable $configurator, ...$arguments): self
    {
        $configuration = $configurator($this->options, ...$arguments);

        $this->setOptions($configuration);

        return $this;
    }

    #endregion

}
