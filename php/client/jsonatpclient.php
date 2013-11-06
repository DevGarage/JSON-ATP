<?php
/**
 * DevGar
 * Ukraine, Odessa
 * Created by PhpStorm.
 * User: Victor Murzak
 * email: mv@bel.net.ua
 * GitHub: https://github.com/DevGarage/JSON-ATP.git
 * Date: 06.11.13
 * Time: 9:11
 */

class JsonAtpClient {

	## HEAD FLAGS ##
	const NONE              = 0;
	const COMPRESSION       = 1;
	const ENCRYPTION        = 2;
	const COMPRESS_ENCRYPT  = 3;

	## PARAMS ##
	const COMPRESSIONDEF   = 6;
	const OPENSSLDEFAULT = "AES-128-CBC";

	private $chiper = self::OPENSSLDEFAULT;
	private $compession = self::COMPRESSIONDEF;

	## KEYS ##
	private $headKey = null;
	private $dataKey = null;


	## ENCODE DATA ##
	public function json_atp_encode($data, $flag = self::COMPRESS_ENCRYPT){
		//Cheking income param if not string convert to string
		if(!is_string($data))
			$data = json_encode($data);

		//set default timezone
		date_default_timezone_get("GMT+0");

		$s_data = $data;

		if(!self::_checkKeys())
			return false;


		//flags check
		if(self::_is_compress($flag))
			$s_data = self::_compress($s_data);

		if(self::_is_encrypt($flag))
				$s_data = self::_encrypt($s_data, $this->dataKey);

		$s_data = base64_encode($s_data);

		// head prepare
		$head = [
			"id"         => "",
			"r_id"       => uniqid(),
			"signature"  => hash("sha256", $data),
			"time"       => microtime(),
			"size"       => strlen($s_data)
		];

		$head = json_encode($head);

		if(self::_is_compress($flag))
			$head = self::_compress($head);

		if(self::_is_encrypt($flag))
			$head = self::_encrypt($head, $this->headKey);

		$head = base64_encode($head);

		$r = [
			"head" => $head,
			"data" => $s_data
		];

		return base64_encode(json_encode($r));
	}

	## SETS HEAD KEY ##
	public function setHeadKey($key){
		if(is_string($key) && strlen($key) > 0){
			$this->headKey = $key;
			return true;
		}
		else{
			throw new Exception("Bad head key", __LINE__);
			return false;
		}
	}

	## SETS DATA KEY
	public function setDataKey($key){
		if(is_string($key) && strlen($key) > 0){
			$this->dataKey = $key;
			return true;
		}
		else{
			throw new Exception("Bad data key", __LINE__);
			return false;
		}

	}

	## DECODE DATA ##
	public static  function  json_atp_decode($data){

	}

	private function _compress($data){
		$data = gzcompress($data, $this->compession);
		return $data;
	}

	## ENCRYPT DATA ##
	private function _encrypt($data, $key){
		$ivlen  = openssl_cipher_iv_length($this->chiper);
		$iv     = substr(hash('sha256',$key),0,$ivlen);
		$data   = openssl_encrypt($data,$this->chiper,$key,true,$iv);
		return $data;
	}

	## DECRYPT DATA ##
	private  function _decrypt($data, $key){
		$ivlen  = openssl_cipher_iv_length($this->chiper);
		$iv     = substr(hash('sha256',$key),0,$ivlen);
		$data   = openssl_decrypt($data,$this->chiper,$key,true,$iv);
		return $data;
	}

	## CHECK KEYS ##
	private function _checkKeys(){
		if($this->headKey === null || $this->dataKey === null){
			throw new Exception("Not initialize keys", __LINE__);
			return false;
		}
		return true;
	}

	private function _is_compress($flag){
		if($flag === self::COMPRESSION || $flag === self::COMPRESS_ENCRYPT)
			return true;
		else
			return false;
	}
	private function _is_encrypt($flag){
		if($flag === self::ENCRYPTION || $flag === self::COMPRESS_ENCRYPT)
			return true;
		else
			return false;
	}

	public function setAlgoritm($chiper){
		$avail_cipher = openssl_get_cipher_methods();
		if(array_key_exists($chiper,$avail_cipher))
			$this->chiper = $chiper;
	}

	public function setCompression($compression){
		$this->compession = intval($compression);
	}

} 