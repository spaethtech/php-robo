<?php /** @noinspection PhpUnused, PhpUndefinedClassInspection */
declare(strict_types=1);
require_once __DIR__."/vendor/autoload.php";

use MVQN\SFTP\Exceptions\AuthenticationException;
use MVQN\SFTP\Exceptions\InitializationException;
use MVQN\SFTP\Exceptions\LocalStreamException;
use MVQN\SFTP\Exceptions\MissingExtensionException;
use MVQN\SFTP\Exceptions\RemoteConnectionException;
use MVQN\SFTP\Exceptions\RemoteStreamException;
use Robo\Tasks;

/**
 * Class RoboFile
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class RoboFile extends Tasks
{
    use MVQN\Robo\Task\Sftp\Tasks;

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

    private const REMOTE_PLUGIN_PATH = "/home/unms/data/ucrm/ucrm/data/plugins";

    /**
     * Prompts the developer for SFTP Configuration and saves or updates the results in an .env file.
     *
     * NOTES:
     * - This method will attempt to preserve existing whitespace and quoted values when possible, but will use best
     *   practices when creating new entries.
     * - The values will be stored in plain text in an .env file, so it is IMPORTANT that the file not be included in
     *   the Plugin package or repository commits.
     *
     * @param string|null $envPath
     */
    public function sftpConfigure(string $envPath = null): void
    {
        $envPath = $envPath ?? __DIR__.DIRECTORY_SEPARATOR.".env";
        $envFile = file_exists($envPath) ? file_get_contents($envPath) : "";

        $this->setEnv("SFTP_HOST", "SFTP Host", $envFile);
        $this->setEnv("SFTP_PORT", "SFTP Port", $envFile);
        $this->setEnv("SFTP_USER", "SFTP User", $envFile);
        $this->setEnv("SFTP_PASS", "SFTP Pass", $envFile, true);

        file_put_contents($envPath, $envFile, LOCK_EX);
    }

    /**
     * @param string $remote
     * @param string $local
     *
     * @throws AuthenticationException
     * @throws InitializationException
     * @throws LocalStreamException
     * @throws MissingExtensionException
     * @throws RemoteConnectionException
     * @throws RemoteStreamException
     */
    public function sftpGet(string $remote, string $local)
    {
        $plugin = "ucrm-plugin-template";

        $remote = strpos($remote, "/") === 0 ? $remote : self::REMOTE_PLUGIN_PATH."/$plugin/$remote";
        $local = strpos($remote, ":\\") !== false ? $local : __DIR__."/src/$local";

        $host = $this->getEnv("SFTP_HOST");
        $port = $this->getEnv("SFTP_PORT");
        $user = $this->getEnv("SFTP_USER");
        $pass = $this->getEnv("SFTP_PASS");

        $this->taskSftpGet()
            //->setHost($host)
            //->setPort(22)
            //->setUser($user)
            //->setPass($pass)
            //->map($remote, $local)
            //->saveConfiguration()
            ->loadConfiguration()
            ->run();
    }

    /**
     * @param string $local
     * @param string $remote
     *
     * @throws AuthenticationException
     * @throws InitializationException
     * @throws LocalStreamException
     * @throws MissingExtensionException
     * @throws RemoteConnectionException
     * @throws RemoteStreamException
     */
    public function sftpPut(string $local, string $remote)
    {
        $plugin = $this->getPluginName();

        $local = strpos($remote, ":\\") !== false ? $local : __DIR__."/src/$local";
        $remote = strpos($remote, "/") === 0 ? $remote : self::REMOTE_PLUGIN_PATH."/$plugin/$remote";

        $host = $this->getEnv("SFTP_HOST");
        $port = $this->getEnv("SFTP_PORT");
        $user = $this->getEnv("SFTP_USER");
        $pass = $this->getEnv("SFTP_PASS");

        $this->taskSftpPut($host, (int)$port)
            ->login($user, $pass)
            ->upload($local, $remote)
            ->run();
    }

    #endregion


}



