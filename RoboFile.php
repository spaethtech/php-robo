<?php /** @noinspection PhpUnused, PhpUndefinedClassInspection */
declare(strict_types=1);
require_once __DIR__."/vendor/autoload.php";

use rspaeth\Robo\Task\Sftp\Exceptions\ConfigurationMissingException;
use rspaeth\Robo\Task\Sftp\Exceptions\ConfigurationParsingException;
use rspaeth\Robo\Task\Sftp\Exceptions\OptionMissingException;
use rspaeth\SFTP\Exceptions\AuthenticationException;
use rspaeth\SFTP\Exceptions\InitializationException;
use rspaeth\SFTP\Exceptions\LocalStreamException;
use rspaeth\SFTP\Exceptions\MissingExtensionException;
use rspaeth\SFTP\Exceptions\RemoteConnectionException;
use rspaeth\SFTP\Exceptions\RemoteStreamException;
use Robo\Tasks;

/**
 * Class RoboFile
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class RoboFile extends Tasks
{


    #region Environment

    /**
     * Checks for existing entries in the "src/.env" file and then either updates or creates the them as needed.
     *
     * @param string $key       The ENV key.
     * @param string $label     The label to use in the Robo Task Prompt when asking for the value.
     * @param string $contents  The contents of the ENV file on which to interact.
     * @param bool $quotes      An optional flag denoting forceful use of quotes around a value during creation.
     */
    private function setEnv(string $key, string $label, string &$contents, bool $quotes = false): void
    {
        $found = preg_match('/^('.$key.')[ \t]*=[ \t]*(.*)$/m', $contents, $matches);
        $match = $found ? $matches[2] : "";
        $plain = $found ? str_replace("\"", "", $match) : "";

        $value = str_replace("\"", "", $this->askDefault($label, $plain ?? ""));

        if($found && $plain !== $value)
        {
            $contents = str_replace(
                $matches[0],
                str_replace(
                    $matches[2] === "\"\"" ? "\"\"" : str_replace("\"", "", $matches[2]),
                    $matches[2] === "\"\"" ? "\"$value\"" : $value,
                    $matches[0]
                ),
                $contents
            );
        }

        if(!$found)
            $contents .= "$key=".($quotes ? "\"" : "")."$value".($quotes ? "\"" : "")."\n";
    }

    /**
     * @param string $key
     * @return string|null
     */
    private function getEnv(string $key): ?string
    {
        $envPath = __DIR__.DIRECTORY_SEPARATOR.".env";
        $envFile = file_exists($envPath) ? file_get_contents($envPath) : "";

        if(preg_match('/^('.$key.')[ \t]*=[ \t]*(.*)$/m', $envFile, $matches))
            return str_replace("\"", "", $matches[2]);

        return null;
    }

    #endregion

    #region SFTP

    use rspaeth\Robo\Task\Sftp\Tasks;

    private const REMOTE_PLUGIN_PATH = "/home/unms/data/ucrm/ucrm/data/plugins";

    /**
     * Prompts the developer for SFTP Configuration, saves or updates the results in a "sftp.config.json" file and then
     * adds the relative path to the ".gitignore" file.
     */
    public function sftpConfigure(): void
    {
        $this->askSftpConfiguration(__DIR__, "sftp.config.json");
    }


    /**
     * @param string $remote
     * @param string $local
     *
     * @throws AuthenticationException
     * @throws ConfigurationMissingException
     * @throws ConfigurationParsingException
     * @throws InitializationException
     * @throws LocalStreamException
     * @throws MissingExtensionException
     * @throws RemoteConnectionException
     * @throws RemoteStreamException
     * @throws OptionMissingException
     */
    public function sftpGet(string $remote, string $local)
    {
        /*
        $basename = basename(__DIR__);
        $plugin = $basename !== "robo-tasks" ? $basename : "ucrm-plugin-template";

        $remote = strpos($remote, "/") === 0 ? $remote : self::REMOTE_PLUGIN_PATH."/$plugin/$remote";
        $local = strpos($remote, ":\\") !== false ? $local : __DIR__.DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR."$local";
        */

        $this->taskSftpGet()

            ->loadConfiguration()

            ->funcConfiguration(
                function(array $current) use ($remote, $local)
                {
                    if ($current["host"] === "" || $current["port"] === "" ||
                        $current["user"] === "" || $current["pass"] === "")
                        $current = $this->askSftpConfiguration(__DIR__, "sftp.config.json");

                    return $current;
                }
            )

            ->map($remote, $local)

            ->run();
    }

    /**
     * @param string $local
     * @param string $remote
     *
     * @throws AuthenticationException
     * @throws ConfigurationMissingException
     * @throws ConfigurationParsingException
     * @throws InitializationException
     * @throws LocalStreamException
     * @throws MissingExtensionException
     * @throws OptionMissingException
     * @throws RemoteConnectionException
     * @throws RemoteStreamException
     */
    public function sftpPut(string $local, string $remote)
    {
        $basename = basename(__DIR__);
        $plugin = $basename !== "robo-tasks" ? $basename : "ucrm-plugin-template";

        $remote = strpos($remote, "/") === 0 ? $remote : self::REMOTE_PLUGIN_PATH."/$plugin/$remote";
        $local = strpos($remote, ":\\") !== false ? $local : __DIR__.DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR."$local";

        $this->taskSftpPut()

            ->loadConfiguration()

            ->funcConfiguration(
                function(array $current) use ($remote, $local)
                {
                    if ($current["host"] === "" || $current["port"] === "" ||
                        $current["user"] === "" || $current["pass"] === "")
                        $current = $this->askSftpConfiguration(__DIR__, "sftp.config.json");

                    return $current;
                }
            )

            ->map($local, $remote)

            ->run();
    }

    #endregion

    use rspaeth\Robo\Task\Packer\Tasks;

    public function packerBundle(string $folder = "", string $output = "", string $ignore = "")
    {
        $folder = $folder ?: __DIR__;
        $output = $output ?: basename($folder);
        $ignore = $ignore ?: __DIR__.DIRECTORY_SEPARATOR.".zipignore";

        $this->taskPackerBundle()
            ->setFolder($folder)
            ->setOutput($output)
            ->setIgnore($ignore)

            ->run();

    }


}



