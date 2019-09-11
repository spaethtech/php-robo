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
    protected $host;
    protected $port;
    protected $user;
    protected $pass;

    /**
     * SftpTasks constructor.
     *
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host, int $port = 22)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param string $user
     * @param string $pass
     *
     * @return Base
     */
    public function login(string $user, string $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
        return $this;
    }

}
