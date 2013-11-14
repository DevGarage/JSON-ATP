<?php
/**
 * DevGar
 * Ukraine, Odessa
 * User: Bubelich Nikolay
 * email: thesimj@gmail.com
 * GitHub: https://github.com/DevGarage/JSON-ATP.git
 * Date: 14.11.13
 *
 * VERSION 2.14
 */

class JsonAtp {
    const ATP_PROTOCOL      = 2;
    const FLAG_CLEAR_TEXT   = 0x0;
    const FLAG_COMPRESSION  = 0x1;
    const FLAG_ENCRYPTION   = 0x2;

    const DEFAULT_CIPHER            = 'aes-128-cbc';
    const DEFAULT_COMPRESSION_LEVEL = 6;
    const DEFAULT_FLAG              = 0x3;

    protected $cipher               = self::DEFAULT_CIPHER;
    protected $compression_level    = self::DEFAULT_COMPRESSION_LEVEL;
    protected $head_length          = 0;
    protected $data_length          = 0;
    protected $flag                 = self::DEFAULT_FLAG;

    protected $head                 = array();
    protected $data_signature       = null;

    private $data_key               = null;
    private $head_key               = null;

    /** @var string Used for identity clients  */
    private $token                  = null;

    public function __construct($head_key = null, $token = null, $data_key = null, $flag = self::DEFAULT_FLAG){
        self::setKey($head_key,$data_key);
        self::setFlag($flag);
        self::setToken($token);
    }

    public function decode($data){
        ## Check data ##
        if(is_string($data) == false)
            return false;

        if(strlen($data) < 2)
            return false;

        ## Check encrypt key ##
        if(self::useEncryption() && ($this->data_key == null || $this->head_key == null))
            return false;

        ## Get header ##
        ## Header length ##
        $this->head_length  = hexdec(substr($data,0,4));
        $this->flag         = hexdec(substr($data,4,1));

        $this->head = substr($data,5,$this->head_length);

        $this->head = self::decodeHead($this->head);

        if($this->head === false)
            return false;

        ## Get data ##
        $data = substr($data,$this->head_length + 5);

        if($data === false)
            return false;

        return self::decodeData($data);
    }

    public function encode($data){

        ## Check keys ##
        if(!is_string($this->head_key)|| !is_string($this->data_key))
            return false;

        ## Check data ##
        if(is_string($data) == false)
            return false;

        if(strlen($data) < 2)
            return false;

        ## Check encrypt key ##
        if(self::useEncryption() && ($this->data_key == null || $this->head_key == null))
            return false;

        ## Encode data ##
        $data = self::encodeData($data);

        ## Encode head ##
        $phead = self::encodeHead();

        $result = $phead . $data;

        ## DEFAULT RETURN FALSE ##
        return $result;
    }

    private function decodeHead($head){
        ## Base64 decode ##
        $head = base64_decode($head);

        if($head === false)
            return false;

        ## Decrypt if enabled ##
        $head = self::decrypt($head,$this->head_key,self::DEFAULT_CIPHER);
        if($head === false)
            return false;

        ## Uncompressed if enabled ##
        $head = self::uncompress($head);
        if($head === false)
            return false;

        ## Json decode to array ##
        $head = json_decode($head,true);

        if($head === false || $head === null)
            return false;

        ## FIND SOME INFO ##
        ## GET CIPHER IF EXIST ##
        if(isset($head['cipher']))
            self::setCipher($head['cipher']);

        ## Token ##
        if(isset($head['token']))
            self::setToken($head['token']);

        ## Protocol version match ##
        if(!isset($head['protocol']) && intval($head['protocol']) != self::ATP_PROTOCOL)
            return false;

        ## Set data signature ##
        $this->data_signature = $head['signature'];

        return $head;
    }

    private function decodeData($data){
        ## Base64 decode ##
        $data = base64_decode($data);
        if($data === false)
            return false;

        ## Decrypt if enabled ##
        $data = self::decrypt($data,$this->data_key,$this->cipher);
        if($data === false)
            return false;

        ## Uncompresse if enabled ##
        $data = self::uncompress($data);
        if($data === false)
            return false;

        ## Test signatrue ##
        $signature = base64_encode(hex2bin(hash('sha256',$this->head_key . $data . $this->data_key)));
        if(strcmp($signature,$this->data_signature) != 0)
            return false;

        return $data;
    }

    private function encodeData($data){

        ## Get data signature ##
        $this->data_signature   = base64_encode(hex2bin(hash('sha256', $this->head_key . $data . $this->data_key)));

        ## PREPARE DATA ##
        ## Compress data ##
        $data = self::compress($data);

        if($data === false)
            return false;

        ## Encryption data ##
        $data = self::encrypt($data,$this->data_key,$this->cipher);

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

        ## Token, use to identify client ##
        if($this->token !== null)
            $this->head['token']    = $this->token;

        ## head time stamp ##
        $this->head['time'] = time();

        ## head signature for data ##
        $this->head['signature'] = $this->data_signature;

        ## head data length ##
        $this->head['length'] = $this->data_length;

        ## set cipher ##
        if($this->cipher !== self::DEFAULT_CIPHER)
            $this->head['cipher'] = $this->cipher;

        ## Convert to json ##
        $jhead = json_encode($this->head);

        ## Perform compress if enabled ##
        $jhead = self::compress($jhead);
        if($jhead === false)
            return false;

        ## Perform encrypt if enabled ##
        $jhead = self::encrypt($jhead,$this->head_key);
        if($jhead === false)
            return false;

        ## Convert to Base64 ##
        $jhead = base64_encode($jhead);

        ## Get len ##
        $this->head_length = strlen($jhead);

        ## Prepare head len ##
        $head_len   = sprintf("%04X",$this->head_length);

        ## Prepare flag ##
        $flag       = sprintf("%1X",$this->flag);

        ## Return head ##
        return $head_len . $flag . $jhead;
    }

    private function encrypt($data,$key,$cipher = self::DEFAULT_CIPHER){

        if(self::useEncryption()){
            $ivlen  = openssl_cipher_iv_length($this->cipher);
            $iv     = substr(hash('sha256',$key),0,$ivlen);

            return openssl_encrypt($data,$cipher,$key,true,$iv);
        }
        else
            return $data;
    }

    private function decrypt($data,$key,$cipher = self::DEFAULT_CIPHER){
        if(self::useEncryption()){
            $ivlen  = openssl_cipher_iv_length($cipher);
            $iv     = substr(hash('sha256',$key),0,$ivlen);
            return openssl_decrypt($data,$cipher,$key,true,$iv);
        }
        else
            return $data;
    }

    private function compress($data){
        if(self::useCompression())
            return gzcompress($data,$this->compression_level);
        else
            return $data;
    }

    private function uncompress($data){
        if(self::useCompression())
            return gzuncompress($data);
        else
            return $data;
    }

    public function useCompression(){
        return ($this->flag & self::FLAG_COMPRESSION) > 0 ? true : false;
    }

    public function useEncryption(){
        return ($this->flag & self::FLAG_ENCRYPTION) > 0 ? true : false;
    }


    public function extraHead($extra){
        if(is_array($extra)){
            foreach($extra as $key=>$val)
                if(isset($this->head[$key]) == false)
                    $this->head[$key] = $val;
        }
    }

    public function parseHead($data){
        if(is_string($data)){
            ## Check data ##
            if(is_string($data) == false)
                return false;

            if(strlen($data) < 2)
                return false;

            ## Check encrypt key ##
            if(self::useEncryption() && ($this->head_key == null))
                return false;

            ## Get header ##
            ## Header length ##
            $this->head_length  = hexdec(substr($data,0,4));
            $this->flag         = hexdec(substr($data,4,1));

            $this->head = substr($data,5,$this->head_length);

            $this->head = self::decodeHead($this->head);

            if($this->head === false)
                return false;
        }

        return true;
    }

    public function getToken(){
        return $this->token;
    }

    public function setToken($token){
        $this->token = $token;
    }

    /**
     * Return Header information
     *
     * @return array Head or null
     */
    public function getHeader(){
        return $this->head;
    }

    /**
     * Set Head and Data encrypt key
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

        if(in_array($cipher,$avail_cipher))
            $this->cipher = $cipher;
    }

    /**
     * @param int $level
     */
    public function setCompressionLevel($level)
    {
        $this->compression_level = intval($level);
    }

    public function setFlag($flag){
        $this->flag = intval($flag);
    }


}