<?php
declare(strict_types=1);

namespace src\Task\Sftp;

use Robo\Contract\TaskInterface;
use Robo\Result;
use SpaethTech\SFTP\Exceptions\AuthenticationException;
use SpaethTech\SFTP\Exceptions\InitializationException;
use SpaethTech\SFTP\Exceptions\LocalStreamException;
use SpaethTech\SFTP\Exceptions\MissingExtensionException;
use SpaethTech\SFTP\Exceptions\RemoteConnectionException;
use SpaethTech\SFTP\Exceptions\RemoteStreamException;
use SpaethTech\SFTP\SftpClient;
use SpaethTech\Support\Paths;
use src\Task\Sftp\Exceptions\OptionMissingException;

/**
 * Exposes some SFTP functionality.
 *
 * ``` php
 * <?php
 * $this->taskSftpGet([ <options> ])            // Pass any desired configuration options here
 *
 *     ->setOptions([ <options> ])              // OR include multiple options using the setter
 *
 *                                              // OR include them using the individual setters:
 *     ->setHost(<host>)                        // The SFTP server's hostname
 *     ->setPort(<port>)                        // The SFTP server's port, defaults to 22
 *     ->setUser(<user>)                        // The SFTP server's username
 *     ->setPass(<pass>)                        // The SFTP server's password
 *     ->setRemoteBase(path)                    // An optional base path for which to prefix remote relative paths
 *     ->setLocalBase(path)                     // An optional base path for which to prefix local relative paths
 *
 *     ->loadConfiguration(<path>)              // OR load them from a configuration file (i.e. "sftp.config.json")
 *     ->funcConfiguration(callable, <args>)    // THEN verify/modify the configuration options, as needed
 *     ->saveConfiguration(<path>)              // AND optionally save them to a configuration file
 *
 *     ->map(remote, local)                     // AND add remote to local mappings individually,
 *     ->maps(maps)                             // OR replacing all mappings at once.
 *
 *     ->run();                                 // FINALLY execute a GET request for ALL mappings, using the options.
 * ?>
 * ```
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @final
 */
final class Put extends Base implements TaskInterface
{

    /**
     * @param string $remote
     * @param string $local
     * @return $this
     */
    public function map(string $local, string $remote)
    {
        $this->options["maps"]["local"][$this->options["base"]["local"].$local] = $this->options["base"]["remote"].$remote;
        return $this;
    }

    /**
     * @param array $maps
     * @return $this
     */
    public function maps(array $maps)
    {
        $this->options["maps"]["local"] = $maps;
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
     * @throws OptionMissingException
     */
    public function run()
    {
        if(empty($host = $this->options["host"]))
            throw new OptionMissingException("The option 'host' must be set before calling ".__CLASS__."::run()!");
        if(empty($port = $this->options["port"]))
            throw new OptionMissingException("The option 'port' must be set before calling ".__CLASS__."::run()!");
        if(empty($user = $this->options["user"]))
            throw new OptionMissingException("The option 'user' must be set before calling ".__CLASS__."::run()!");
        if(empty($pass = $this->options["pass"]))
            throw new OptionMissingException("The option 'pass' must be set before calling ".__CLASS__."::run()!");

        $this->printTaskInfo("Connecting to SFTP...");

        $client = new SftpClient($host, $port);
        $client->login($user, $pass);

        if(empty($this->options["maps"]) || empty($this->options["maps"]["local"]))
            throw new OptionMissingException("At least on local mapping must be set before calling ".__CLASS__."::run()!");

        foreach($this->options["maps"]["local"] as $local => $remote)
        {
            $client->upload($local, $remote);

            $localPath = Paths::canonicalize($local, "\\");
            $remotePath = Paths::canonicalize($remote, "/");

            $this->printTaskSuccess("> UPLOAD");
            $this->printTaskSuccess("  [L] $localPath");
            $this->printTaskSuccess("  [R] $remotePath");
        }

        //$this->printTaskSuccess("...DONE!");
    }

}
