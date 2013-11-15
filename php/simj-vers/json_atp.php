<?php
/**
 * DevGar
 * Ukraine, Odessa
 * Author: Bubelich Nikolay
 * Email: thesimj@gmail.com
 * GitHub: https://github.com/DevGarage/JSON-ATP
 * Date: 15.11.13
 * VERSION 3.16
 *
 * =========================================
 * Apache License, Version 2.0, January 2004
 * http://www.apache.org/licenses/
 */

class JsonAtp {
    const ATP_PROTOCOL      = 3;

    const FLAG_CLEAR_TEXT   = 0x0;
    const FLAG_COMPRESSION  = 0x1;
    const FLAG_ENCRYPTION   = 0x2;

    const DEFAULT_CIPHER            = 'aes-128-cbc';
    const DEFAULT_COMPRESSION_LEVEL = 6;
    const DEFAULT_FLAG              = 0x3;

    /** int, Current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)*/
    const HEAD_FIELD_TIME       = '_t';

    /** string, Client (server) token identity */
    const HEAD_FIELD_TOKEN      = '_i';

    /** string, Cipher type, default AES-128-CBC */
    const HEAD_FIELD_CIPHER     = '_c';

    /** int, Protocol version */
    const HEAD_FIELD_PROTOCOL   = '_p';

    /** string, Signature (sha256) for head and data (if available) */
    const HEAD_FIELD_SIGNATURE  = '_s';

    /** int, Length of Data (if available) */
    const HEAD_FIELD_LENGTH     = '_l';

    /** HASH CONST **/
    const HASH_LENGTH           = 64;
    const HASH_ALGORITHM        = 'sha256';

    protected $cipher               = self::DEFAULT_CIPHER;
    protected $compression_level    = self::DEFAULT_COMPRESSION_LEVEL;
    protected $head_length          = 0;
    protected $data_length          = 0;
    protected $flag                 = self::DEFAULT_FLAG;

    protected $head                 = array();

    protected $data_signature       = null;
    protected $head_signature       = null;
    protected $full_signature       = null;

    private $data_key               = null;
    private $head_key               = null;

    /** @var string Used for identity clients  */
    private $token                  = null;

    public function __construct($token = null, $head_key = null, $data_key = null, $flag = self::DEFAULT_FLAG){
        self::setToken($token);
        self::setFlag($flag);
        self::setKey($head_key,$data_key);
    }

    public function decode($data, $tokenOnly = false){

        ## Data check ##
        if(is_string($data) == false || strlen($data) <= 2)
            return false;

        ## HEADER ##
        ## Get full signature ##
        $this->full_signature = substr($data,0,self::HASH_LENGTH);

        ## Header length ##
        $this->head_length  = hexdec(substr($data,self::HASH_LENGTH + 0,4));
        $this->flag         = hexdec(substr($data,self::HASH_LENGTH + 4,1));

        $this->head = substr($data,self::HASH_LENGTH + 5,$this->head_length);

        $this->head = self::decodeHead($this->head);

        if($this->head === false)
            return false;

        if($tokenOnly)
            return self::getToken();

        ## Get data ##
        $data = substr($data,$this->head_length + self::HASH_LENGTH + 5);

        if($data === false)
            return false;

        $data = self::decodeData($data);

        if($data == false)
            return false;

        ## Get full signature for all message ##
        $hk = ($this->head_key == null) ? '' : $this->head_key;
        $dk = ($this->data_key == null) ? '' : $this->data_key;

        ## Full signature = SHA256 ( HeadKey + HeadSignature + DataKey + DataSignature )
        $signature = self::hash($hk . $this->head_signature . $dk . $this->data_signature );

         if(strcmp($signature,$this->full_signature) == 0)
             return $data;
        else
            return false;
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

        ## Set head signature ##
        $this->head_signature = self::hash($head);

        ## Json decode to array ##
        $head = json_decode($head,true);

        if($head === false || $head === null)
            return false;

        ## FIND SOME INFO ##
        ## GET CIPHER IF EXIST ##
        if(isset($head[self::HEAD_FIELD_CIPHER]))
            self::setCipher($head[self::HEAD_FIELD_CIPHER]);

        ## Token ##
        if(isset($head[self::HEAD_FIELD_TOKEN]))
            self::setToken($head[self::HEAD_FIELD_TOKEN]);

        ## Protocol version match ##
        if(!isset($head[self::HEAD_FIELD_PROTOCOL]) && intval($head[self::HEAD_FIELD_PROTOCOL]) != self::ATP_PROTOCOL)
            return false;

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
        $this->data_signature = self::hash($data);

        return $data;
    }

    public function encode($data, $extra = null){

        ## Data check ##
        if(is_string($data) == false || strlen($data) <= 2)
            return false;

        ## Add extra fields to head ##
        self::addExtra($extra);

        ## Encode data ##
        $data = self::encodeData($data);

        ## Encode head ##
        $phead = self::encodeHead();

        ## Prepare head len ##
        $head_len   = sprintf("%04X",$this->head_length);

        ## Prepare flag ##
        $flag       = sprintf("%1X",$this->flag);

        ## Get full signature for all message ##
        $hk = ($this->head_key == null) ? '' : $this->head_key;
        $dk = ($this->data_key == null) ? '' : $this->data_key;

        ## Full signature = SHA256 ( HeadKey + HeadSignature + DataKey + DataSignature )
        $this->full_signature = self::hash($hk . $this->head_signature . $dk . $this->data_signature );

        ## Result -> Signature + HeadLen + Flag + Head + Data
        $result = $this->full_signature . $head_len . $flag . $phead . $data;

        ## DEFAULT RETURN FALSE ##
        return $result;
    }

    private function encodeData($data){

        ## Get data signature ##
        $this->data_signature   = self::hash($data);

        ## PREPARE DATA ##
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
        $this->head[self::HEAD_FIELD_PROTOCOL]  = self::ATP_PROTOCOL;

        ## Token, use to identify client ##
        if(!is_null($this->token))
        $this->head[self::HEAD_FIELD_TOKEN]     = $this->token;

        ## head timestamp ##
        $this->head[self::HEAD_FIELD_TIME]      = time();

        ## head data length ##
        $this->head[self::HEAD_FIELD_LENGTH]    = $this->data_length;

        ## set cipher ##
        if($this->cipher !== self::DEFAULT_CIPHER)
        $this->head[self::HEAD_FIELD_CIPHER]    = $this->cipher;

        ## Convert to json ##
        $jhead = json_encode($this->head);

        ## Get signature
        $this->head_signature = self::hash($jhead);

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

        ## Return head ##
        return $jhead;
    }

    private function encrypt($data,$key,$cipher = self::DEFAULT_CIPHER){

        if(self::useEncryption()){
            $ivlen  = openssl_cipher_iv_length($this->cipher);
            $iv     = substr(hash('sha256',$key),0,$ivlen);

            return @openssl_encrypt($data,$cipher,$key,true,$iv);
        }
        else
            return $data;
    }

    private function decrypt($data,$key,$cipher = self::DEFAULT_CIPHER){
        if(self::useEncryption()){
            $ivlen  = openssl_cipher_iv_length($cipher);
            $iv     = substr(hash('sha256',$key),0,$ivlen);

            return @openssl_decrypt($data,$cipher,$key,true,$iv);
        }
        else
            return $data;
    }

    private function compress($data){
        if(self::useCompression())
            return @gzcompress($data,$this->compression_level);
        else
            return $data;
    }

    private function uncompress($data){
        if(self::useCompression())
            return @gzuncompress($data);
        else
            return $data;
    }

    public function useCompression(){
        return ($this->flag & self::FLAG_COMPRESSION) > 0 ? true : false;
    }

    public function useEncryption(){
        return ($this->flag & self::FLAG_ENCRYPTION) > 0 ? true : false;
    }

    public function getExtra(){
        $_ARR = false;

        if(is_array($this->head)){
            foreach($this->head as $key => $val)
                if(substr_compare($key,'_',0,1,true) != 0)
                    $_ARR[$key] = $val;

        }

        return $_ARR;
    }

    public function addExtra($extra){
        if(is_array($extra)){

            foreach($extra as $key => $val)
                if(isset($this->head[$key]) == false)
                    $this->head[$key] = $val;

            return true;

        }else
            return false;
    }

    public function getToken(){
        return $this->token;
    }

    public function setToken($token){
        $this->token = strval($token);
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

        ## If head key == null, no encryption at all ##
        if(is_null($this->head_key))
            $this->flag &= (self::FLAG_ENCRYPTION ^ 0xFF);
    }

    public function setDataKey($key){
        if(is_string($key))
            $this->data_key = $key;
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

    private function hash($data){
        if(is_string($data) && strlen($data)>1){
//            return base64_encode(hex2bin(hash(self::HASH_ALGORITHM, $data)));
            return hash(self::HASH_ALGORITHM, $data);
        }

        return false;
    }


}