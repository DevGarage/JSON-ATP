<?php
/**
 * DevGar
 * Ukraine, Odessa
 * Created by PhpStorm.
 * User: Bubelich Nikolay
 * email: thesimj@gmail.com
 * GitHub: https://github.com/DevGarage/JSON-ATP.git
 * Date: 06.11.13
 * Time: 9:06
 */

class JsonAtpServer {
    const ATP_PROTOCOL      = 1;
    const FLAG_COMPRESSION  = 1;
    const FLAG_ENCRYPTION   = 2;

    const DEFAULT_CIPHER            = 'aes-128-cbc';
    const DEFAULT_COMPRESSION_LEVEL = 6;
    const DEFAULT_FLAG              = 3;

    protected $cipher               = self::DEFAULT_CIPHER;
    protected $comression_level     = self::DEFAULT_COMPRESSION_LEVEL;
    protected $head_length          = 0;
    protected $data_length          = 0;
    protected $flag                 = self::DEFAULT_FLAG;

    protected $head                 = null;
    protected $data_signature       = null;

    private $data_key               = null;
    private $head_key               = null;
    private $id                     = 0;

    public function __construct($head_key = null){
        $this->head_key = $head_key;
    }

    public function encode($data){

        ## Check data ##
        if(is_string($data) == false)
            return false;

        if(strlen($data) < 2)
            return false;

        ## Prepare head ##
        $this->head = array();

        ## Get data signature ##
        $this->data_signature   = hash('sha256',$data);

        ## PREPARE DATA ##
        ## Compress data ##
        $data = self::compression($data);

        if($data === false)
            return false;

        ## Encryption data ##
        $data = self::encryption($data,$this->data_key);

        if($data === false)
            return false;

        ## Base64 ##
        $data = base64_encode($data);

        ## get data len ##
        $this->data_length = strlen($data);

        ## DATA READY ##
        $phead = self::prepareHead();

        var_dump($this->head);

        ## DEFAULT RETURN FALSE ##
        return $data;
    }

    private function prepareHead(){
        ## CREATE HEAD ##
        ## head request id ##
        $this->head['request'] = uniqid();

        ## head time stamp ##
        $this->head['time'] = time();

        ## head signature for data ##
        $this->head['signature'] = $this->data_signature;

        ## head data length ##
        $this->head['length'] = $this->data_length;

        ## Convert to json ##

        $jhead = json_encode($this->head);

        var_dump($jhead);

        ## Compress head ##

    }

    private function encryption($data,$key){

        if(self::useEncryption()){
            $ivlen  = openssl_cipher_iv_length($this->cipher);
            $iv     = substr(hash('sha256',$key),0,$ivlen);

            $data   = openssl_encrypt($data,$this->cipher,$key,true,$iv);
        }

        return $data;
    }

    private function compression($data){

        if(self::useCompression()){
            $data = gzencode($data,$this->comression_level);
        }

        return $data;
    }

    public function useCompression(){
        return ($this->flag & self::FLAG_COMPRESSION) > 0 ? true : false;
    }

    public function useEncryption(){
        return ($this->flag & self::FLAG_ENCRYPTION) > 0 ? true : false;
    }

    /**
     * Set Head and Data encryption key
     *
     * @param string $headKey
     * @param string $dataKey
     */
    public function setKey($headKey,$dataKey){
        $this->head_key = $headKey;
        $this->data_key = $dataKey;
    }

    /**
     * @param string $cipher
     */
    public function setCipher($cipher)
    {
        ## get cipher in openssl ##
        $avail_cipher = openssl_get_cipher_methods();

        if(array_key_exists($cipher,$avail_cipher))
            $this->cipher = $cipher;
    }

    /**
     * @param int $level
     */
    public function setComressionLevel($level)
    {
        $this->comression_level = intval($level);
    }


}