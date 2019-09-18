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
    //private $sftp = [];

    private function getRelativePath($from, $to)
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach($from as $depth => $dir) {
            // find first non-matching dir
            if($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }

    /**
     * Prompts the developer for SFTP Configuration and saves or updates the results in an .env file.
     *
     * NOTES:
     * - This method will attempt to preserve existing whitespace and quoted values when possible, but will use best
     *   practices when creating new entries.
     * - The values will be stored in plain text in an .env file, so it is IMPORTANT that the file not be included in
     *   the Plugin package or repository commits.
     *
     * @param string $root
     * @param string $path
     * @return array
     */
    public function askSftpConfiguration(string $root = "", string $path = "./sftp.config.json"): array
    {
        $sftp = [];

        if($root === "")
        {
            try
            {
                $root = dirname((new \ReflectionClass($this))->getFileName());
            }
            catch (\Exception $e)
            {
                return $sftp;
            }
        }

        $full = $root.DIRECTORY_SEPARATOR.$path;

        if(file_exists($root) && file_exists($full))
            $sftp = json_decode(file_get_contents($full), true);

        $sftp["host"] = $this->askDefault("SFTP Host", isset($sftp["host"]) ? $sftp["host"] : "");
        $sftp["port"] = $this->askDefault("SFTP Port", isset($sftp["port"]) ? $sftp["port"] : 22);
        $sftp["user"] = $this->askDefault("SFTP User", isset($sftp["user"]) ? $sftp["user"] : "");
        $sftp["pass"] = $this->askDefault("SFTP Pass", isset($sftp["pass"]) ? $sftp["pass"] : "");

        file_put_contents(
            $full,
            json_encode($sftp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        $relative = str_replace("./", "", $this->getRelativePath($root, $full));

        $pattern = str_replace(".", "\.", "#^$relative#m");

        if (!file_exists($root.DIRECTORY_SEPARATOR.".gitignore") ||
            !preg_match($pattern, file_get_contents($root.DIRECTORY_SEPARATOR.".gitignore")))
            file_put_contents($root . DIRECTORY_SEPARATOR . ".gitignore", "$relative\n", FILE_APPEND | LOCK_EX);

        return $sftp;
    }




    /**
     * @param string $host  The hostname of the remote server.
     * @param int $port     The port to use when connecting to the remote server, defaults to 22.
     *
     * @return Get
     */
    //protected function taskSftpGet(string $host = "", int $port = 22, string $user = "", string $pass = "")
    protected function taskSftpGet(array $configuration = [])
    {
        // Always construct your tasks with the task builder.
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->task(Get::class, $configuration);
    }

    /**
     * @param string $host  The hostname of the remote server.
     * @param int $port     The port to use when connecting to the remote server, defaults to 22.
     *
     * @return Put
     */
    //protected function taskSftpPut(string $host = "", int $port = 22, string $user = "", string $pass = "")
    protected function taskSftpPut(array $configuration = [])
    {
        // Always construct your tasks with the task builder.
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->task(Put::class, $configuration);
    }

    // ...
}
