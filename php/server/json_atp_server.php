<?php
/**
 * DevGar
 * Ukraine, Odessa
 * User: Bubelich Nikolay
 * email: thesimj@gmail.com
 * GitHub: https://github.com/DevGarage/JSON-ATP.git
 * Date: 06.11.13
 * Time: 9:06
 */

class JsonAtpServer {
    const ATP_PROTOCOL      = 1;
    const FLAG_COMPRESSION  = 0x1;
    const FLAG_ENCRYPTION   = 0x2;

    const DEFAULT_CIPHER            = 'aes-128-cbc';
    const DEFAULT_COMPRESSION_LEVEL = 6;
    const DEFAULT_FLAG              = 0x3;

    protected $cipher               = self::DEFAULT_CIPHER;
    protected $compression_level     = self::DEFAULT_COMPRESSION_LEVEL;
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

        ## Check encryption key ##
        if(self::useEncryption() && ($this->data_key == null || $this->head_key == null))
            return false;

        ## Encode data ##
        $data = self::encodeData($data);

        ## Encode head ##
        $phead = self::encodeHead();

        var_dump(array('head' => $phead));
        var_dump(array('data' => $data));

        $result = $phead . $data;

        var_dump(array('result' => $result));

        ## DEFAULT RETURN FALSE ##
        return $result;
    }

    private function encodeData($data){
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

        ## Get data len ##
        $this->data_length = strlen($data);

        ## Base64 ##
        $data = base64_encode($data);

        return $data;
    }

    private function encodeHead(){
        ## CREATE HEAD ##

        ## protocol version ##
        $this->head['protocol'] = self::ATP_PROTOCOL;

        ## head request id ##
        $this->head['request'] = uniqid();

        ## head time stamp ##
        $this->head['time'] = time();

        ## head signature for data ##
        $this->head['signature'] = $this->data_signature;

        ## head data length ##
        $this->head['length'] = $this->data_length;


        var_dump('-- HEAD --');

        ## Convert to json ##
        $jhead = json_encode($this->head);
        var_dump($jhead);

        ## Perform compression if enabled ##
        $jhead = self::compression($jhead);
        if($jhead === false)
            return false;

        var_dump($jhead);

        ## Perform encryption if enabled ##
        $jhead = self::encryption($jhead,$this->head_key);
        if($jhead === false)
            return false;
        var_dump($jhead);

        ## Convert to Base64 ##
        $jhead = base64_encode($jhead);
        var_dump($jhead);

        ## Get len ##
        $this->head_length = strlen($jhead);

        ## Prepare head len ##
        $head_len   = sprintf("%04X",$this->head_length);

        ## Prepare flag ##
        $flag       = sprintf("%1X",$this->flag);

        $this->head['__head_len']   = $head_len;
        $this->head['__head_flag']  = $flag;

        ## Return all head ##

        return $head_len . $flag . $jhead;
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
            $data = gzencode($data,$this->compression_level);
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
    public function setCompressionLevel($level)
    {
        $this->compression_level = intval($level);
    }


}