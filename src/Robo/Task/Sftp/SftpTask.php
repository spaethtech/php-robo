<?php
declare(strict_types=1);

namespace UCRM\Robo\Tasks;

use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use UCRM\SFTP\SftpClient;
use UCRM\SFTP\Exceptions\AuthenticationException;
use UCRM\SFTP\Exceptions\InitializationException;
use UCRM\SFTP\Exceptions\LocalStreamException;
use UCRM\SFTP\Exceptions\MissingExtensionException;
use UCRM\SFTP\Exceptions\RemoteConnectionException;
use UCRM\SFTP\Exceptions\RemoteStreamException;

/**
 * Class SftpTasks
 */
class SftpTask extends BaseTask implements TaskInterface
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
     * @return SftpTask
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
