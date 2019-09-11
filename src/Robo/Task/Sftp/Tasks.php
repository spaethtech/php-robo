<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace MVQN\Robo\Task\Sftp;

/**
 * Trait tasks
 *
 * @package MVQN\Robo\Task\Put
 * @athor Ryan Spaeth <rspaeth@mvqn.net>
 */
trait Tasks
{

    /**
     * @param string $host  The hostname of the remote server.
     * @param int $port     The port to use when connecting to the remote server, defaults to 22.
     *
     * @return Put
     */
    protected function taskSftpGet(string $host, int $port)
    {
        // Always construct your tasks with the task builder.
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->task(Get::class, $host, $port);
    }

    /**
     * @param string $host  The hostname of the remote server.
     * @param int $port     The port to use when connecting to the remote server, defaults to 22.
     *
     * @return Put
     */
    protected function taskSftpPut(string $host, int $port)
    {
        // Always construct your tasks with the task builder.
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->task(Put::class, $host, $port);
    }

    // ...
}
