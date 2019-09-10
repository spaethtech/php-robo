<?php
declare(strict_types=1);

namespace MVQN\SFTP;

/**
 * Class SftpClient
 *
 * @package UCRM\SFTP
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class SftpClient
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $pass;


    /**
     * @var resource
     */
    private $connection;

    /**
     * @var resource
     */
    private $client;

    /**
     * @var string
     */
    private $remoteBase;

    /**
     * @var string
     */
    private $localBase;



    /**
     * @param string $host The remote host to which the SFTP connection should be made.
     * @param int $port The remote port to which the SFTP connection should be made.
     * @throws Exceptions\MissingExtensionException
     * @throws Exceptions\RemoteConnectionException
     */
    public function __construct(string $host, int $port = 22)
    {
        // IF the SSH extension is NOT installed, THEN throw an Exception!
        if(!extension_loaded("ssh2"))
            throw new Exceptions\MissingExtensionException("The 'ssh2' extension appears to be missing!");

        // Create an SSH connection to the specified host on the specified port.
        $this->connection = @ssh2_connect($host, $port);

        // IF the connection is invalid, THEN throw an Exception!
        if(!$this->connection)
            throw new Exceptions\RemoteConnectionException("Could not connect to remote host '$host on port '$port'!");

        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param string $username The username to use when authenticating to the remote host.
     * @param string $password The password to use when authenticating to the remote host.
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\AuthenticationException
     * @throws Exceptions\InitializationException
     */
    public function login(string $username, string $password): self
    {
        // IF authentication to the remote host fails, THEN throw an Exception!
        if(!@ssh2_auth_password($this->connection, $username, $password))
            throw new Exceptions\AuthenticationException(
                "Unable to authenticate with username '$username' using password '$password'!");

        // Create an SFTP client from the current SSH connection.
        $this->client = @ssh2_sftp($this->connection);

        // IF the client is invalid, THEN throw an Exception!
        if(!$this->client)
            throw new Exceptions\InitializationException("Could not initialize the SFTP subsystem!");

        $this->user = $username;
        $this->pass = $password;

        // Return the current SftpClient!
        return $this;
    }

    /**
     * @param string $remote The path to the remote file.
     * @param string $contents
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\RemoteStreamException
     */
    public function put(string $remote, string $contents): self
    {
        // Prepend the remote root to the remote path, if set.
        $remote = $this->remoteBase ? $this->remoteBase.$remote : $remote;

        // Create the folder structure if it does NOT already exist!
        //if(!file_exists(dirname("ssh2.sftp://{$this->client}$remote")))
        //    mkdir(dirname("ssh2.sftp://{$this->client}$remote"), 0755, true);

        // Create a write stream to the remote file.
        $stream = @fopen("ssh2.sftp://{$this->client}$remote", "w");

        // IF the stream is invalid, THEN throw an Exception!
        if(!$stream)
            throw new Exceptions\RemoteStreamException("Could not open the remote file '$remote'!");

        // IF the contents could not be written to the remote file, THEN throw an Exception!
        if(@fwrite($stream, $contents) === false)
            throw new Exceptions\RemoteStreamException("Could not write to the remote file '$remote'!");

        // Close the stream to the remote file.
        @fclose($stream);

        // Return the current SftpClient!
        return $this;
    }

    /**
     * @param string $local The path to the local file to upload.
     * @param string $remote The path to the remote file.
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\LocalStreamException
     * @throws Exceptions\RemoteStreamException
     */
    public function upload(string $local, string $remote): self
    {
        // Prepend the local root to the local path, if set.
        $local = $this->localBase ? $this->localBase.$local : $local;

        // Get the contents of the local file.
        $contents = @file_get_contents($local);

        // IF the contents of the file is invalid or the file is non-existent, THEN throw an Exception!
        if($contents === false)
            throw new Exceptions\LocalStreamException("Could not read from the local file '$local'!");

        // Write the contents to the remote file and return the current SftpClient!
        return self::put($remote, $contents);
    }

    /**
     * @param string $remote The path to the remote file to download.
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\RemoteStreamException
     */
    public function get(string $remote): string
    {
        // Prepend the remote root to the remote path, if set.
        $remote = $this->remoteBase ? $this->remoteBase.$remote : $remote;

        // Create a read stream to the remote file.
        $stream = @fopen("ssh2.sftp://{$this->client}$remote", "r");

        // IF the stream is invalid, THEN throw an Exception!
        if(!$stream)
            throw new Exceptions\RemoteStreamException("Could not open the remote file '$remote'!");

        // Read the contents from the remote file.
        $contents = fread($stream, filesize("ssh2.sftp://{$this->client}$remote"));

        // IF the contents is invalid, THEN throw an Exception!
        if($contents === false)
            throw new Exceptions\RemoteStreamException("Could not read from the remote file '$remote'!");

        // Close the stream to the remote file.
        @fclose($stream);

        // Return the contents of the remote file.
        return $contents;
    }

    /**
     * @param string $remote The path to the remote file to download.
     * @param string $local The path to the local file.
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\LocalStreamException
     * @throws Exceptions\RemoteStreamException
     */
    public function download(string $remote, string $local = ""): self
    {
        // Get the contents from the remote file.
        $contents = self::get($remote);

        $local = $local !== "" ? $local : $remote;

        // Prepend the local root to the local path, if set.
        $local = $this->localBase ? $this->localBase.$local : $local;

        // Create the folder structure if it does NOT already exist!
        if(!file_exists(dirname($local)))
            mkdir(dirname($local), 0755, true);

        // Put the contents to the local file.
        if(file_put_contents($local, $contents) === false)
            throw new Exceptions\LocalStreamException("Could not write to the local file '$local'!");

        // Return the current SftpClient!
        return $this;
    }

    /**
     * @param string $remote
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\RemoteStreamException
     */
    public function delete(string $remote): self
    {
        if(!unlink("ssh2.sftp://{$this->client}$remote"))
            throw new Exceptions\RemoteStreamException("Could not delete the remote file '$remote'!");

        // Return the current SftpClient!
        return $this;
    }



    /**
     * @param string $remote The base path for which to prepend to all remote paths.
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\RemotePathException
     */
    public function setRemoteBasePath(string $remote): self
    {
        // Create the folder structure if it does NOT already exist!
        if(!file_exists("ssh2.sftp://{$this->client}$remote"))
            @mkdir("ssh2.sftp://{$this->client}$remote", 0755, true);

        // IF the remote path is NOT a valid directory, THEN throw an Exception!
        if(!is_dir("ssh2.sftp://{$this->client}$remote"))
            throw new Exceptions\RemotePathException("The provided remote path '$remote' is not a valid directory!");

        // Set the remote base path.
        $this->remoteBase = @ssh2_sftp_realpath($this->client, $remote) ?: null;

        // Return the current SftpClient!
        return $this;
    }

    /**
     * @param string $local The base path for which to prepend to all local paths.
     * @return SftpClient Returns the current SftpClient instance, for use with method chaining.
     * @throws Exceptions\LocalPathException
     */
    public function setLocalBasePath(string $local): self
    {
        // Create the folder structure if it does NOT already exist!
        if(!file_exists($local))
            @mkdir($local, 0755, true);

        // IF the local path is NOT a valid directory, THEN throw an Exception!
        if(!is_dir($local))
            throw new Exceptions\LocalPathException("The provided local path '$local' is not a valid directory!");

        // Set the local base path.
        $this->localBase = @realpath($local) ?: null;

        // Return the current SftpClient!
        return $this;
    }



}
