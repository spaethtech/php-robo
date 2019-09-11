<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace MVQN\Robo\Task\Sftp;

/**
 * Trait loadTasks
 *
 * @package MVQN\Robo\Task\Sftp
 * @athor Ryan Spaeth <rspaeth@mvqn.net>
 */
trait loadTasks
{

    /**
     * @param string $host  The hostname of the remote server.
     * @param int $port     The port to use when connecting to the remote server, defaults to 22.
     *
     * @return Sftp
     */
    protected function taskSftp(string $host, int $port)
    {
        // Always construct your tasks with the task builder.
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->task(Sftp::class, $host, $port);
    }

    // ...
}
