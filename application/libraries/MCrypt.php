<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class MCrypt
{
    private $iv = 'MjB2TG5EeUZhZFZK'; #Same as in JAVA
    private $key = 'eDltcHBRa05Gb3NSZ09JUlNqTmJ4UT09'; #Same as in JAVA


    function __construct()
    {
    }

    // $this->mcrypt->encrypt(

    // $this->mcrypt->decrypt(

    /**
     * @param string $str
     * @param bool $isBinary whether to encrypt as binary or not. Default is: false
     * @return string Encrypted data
     */
    function encrypt($str, $isBinary = false)
    {
        $iv = $this->iv;
        $str = $isBinary ? $str : utf8_decode($str);

        $td = mcrypt_module_open('rijndael-128', ' ', 'cbc', $iv);

        mcrypt_generic_init($td, $this->key, $iv);
        $encrypted = mcrypt_generic($td, $str);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $isBinary ? $encrypted : bin2hex($encrypted);
    }

    /**
     * @param string $code
     * @param bool $isBinary whether to decrypt as binary or not. Default is: false
     * @return string Decrypted data
     */
    function decrypt($code, $isBinary = false)
    {
        $code = $isBinary ? $code : $this->hex2bin($code);
        $iv = $this->iv;

        $td = mcrypt_module_open('rijndael-128', ' ', 'cbc', $iv);

        mcrypt_generic_init($td, $this->key, $iv);
        $decrypted = mdecrypt_generic($td, $code);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $isBinary ? trim($decrypted) : utf8_encode(trim($decrypted));
    }

    protected function hex2bin($hexdata)
    {
        $bindata = '';

        for ($i = 0; $i < strlen($hexdata); $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }

        return $bindata;
    }
}

/*
$mcrypt = new MCrypt(); 
$encrypted = $mcrypt->encrypt("http://36.255.252.196/testenv/mcryptdemo.php");
 
$decrypted =$mcrypt->decrypt($encrypted);
echo $decrypted;
*/