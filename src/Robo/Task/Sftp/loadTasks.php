<?php
declare(strict_types=1);

namespace UCMR\Robo\Task\Plugin;

use Robo\Collection\CollectionBuilder;
use UCRM\Robo\Tasks\SftpTask;

trait loadTasks
{
    /**
     * @param string $host  The hostname of the remote server.
     * @param int $port     The port to use when connecting to the remote server, defaults to 22.
     *
     * @return CollectionBuilder
     */
    protected function taskSftp(string $host, int $port)
    {
        // Always construct your tasks with the task builder.
        return $this->task(SftpTask::class, $host, $port);
    }

    // ...
}