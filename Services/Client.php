<?php

namespace Bigfoot\Bundle\ImportBundle\Services;

/**
 * FTP class
 *
 * @Author S.huot s.huot@c2is.fr
 * @Author S.Plançon s.plancon@c2is.fr
 */
class Client
{
    protected $domain;
    protected $port;
    protected $username;
    protected $password;
    protected $protocol;
    protected $distantDirectory = null;

    /**
     * Initialize the Client
     *
     * @param $protocol String Protocol
     * @param $domain String IP Address or domain name
     * @param $port Integer Port of the address
     */
    public function init($protocol, $domain, $port = 21)
    {
        $this->protocol = $protocol;
        $this->domain = $domain;
        $this->port   = $port;
    }

    /**
     * Credentials of the client
     *
     * @param $username String Login of the FTP
     * @param $password String Password of the FTP
     */
    public function setAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function setDistantDirectory($distantDirectory)
    {
        $this->distantDirectory = $distantDirectory;
    }

    /**
     * Get the distant file by Curl Method
     *
     * @param $uri String Name of the distant file
     * @param $saveInFile boolean If true save data in file and return the filename, otherwise return the data
     * @return string filename or data
     */
    public function get($uri, $saveInFile = true)
    {

        if ($this->port > 0) {
            $domain = $this->domain.':'.$this->port;
        }
        else {
            $domain = $this->domain;
        }

        if ($this->distantDirectory) {
            $domain .= '/'.$this->distantDirectory;
        }

        $url  = sprintf("%s://%s/%s", $this->protocol, $domain, trim($uri, '/'));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERPWD, sprintf("%s:%s", $this->username, $this->password));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if ($saveInFile) {
            $filename = sprintf("/tmp/%s", uniqid());
            $file = fopen($filename, 'w');
            curl_setopt($curl, CURLOPT_FILE, $file);
        }

        $data = curl_exec($curl);
        curl_close($curl);

        if ($saveInFile) {
            fclose($file);

            return $filename;
        }

        return $data;
    }
}


