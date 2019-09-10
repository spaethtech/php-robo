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
 * Class Sftp
 */
class Sftp extends BaseTask implements TaskInterface
{
    private $host;
    private $port;
    private $user;
    private $pass;

    private $method;
    private $remote;
    private $local;

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
     * @return Sftp
     */
    public function login(string $user, string $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
        return $this;
    }

    public function upload(string $local, string $remote)
    {
        $this->method = "upload";
        $this->remote = $remote;
        $this->local = $local;

        return $this;
    }

    public function download(string $remote, string $local)
    {
        $this->method = "download";
        $this->remote = $remote;
        $this->local = $local;

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

        switch($this->method)
        {
            case "upload":
                $client->upload($this->local, $this->remote);
                break;
            case "download":
                $client->download($this->remote, $this->local);
                break;
        }



        $this->printTaskSuccess("DONE!");


    }

}
