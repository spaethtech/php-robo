<?php
declare(strict_types=1);

namespace MVQN\Robo\Task\Sftp;

use Robo\Contract\TaskInterface;
use Robo\Result;

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
class Put extends Base implements TaskInterface
{
    private $mappings = [];


    public function map(string $local, string $remote)
    {
        $this->mappings[$this->localBase.$local] = $this->remoteBase.$remote;
        return $this;
    }

    /**
     * @return Result|void
     * @throws AuthenticationException
     * @throws InitializationException
     * @throws MissingExtensionException
     * @throws RemoteConnectionException
     * @throws LocalStreamException
     * @throws RemoteStreamException
     */
    public function run()
    {
        $this->printTaskInfo("Connecting to SFTP...");
        $client = new SftpClient($this->host, $this->port);
        $client->login($this->user, $this->pass);

        foreach($this->mappings as $remote => $local)
            $client->upload($local, $remote);

        $this->printTaskSuccess("DONE!");
    }

}
