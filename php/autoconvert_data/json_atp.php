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
	const COMPRESSIONDEF    = 6;
	const OPENSSLDEFAULT    = "AES-128-CBC";
	const DEFAULTUSERID     = "";
	const DEFAULTREQUESTID  = "";

	private $chiper         = self::OPENSSLDEFAULT;
	private $compession     = self::COMPRESSIONDEF;
	private $headuserid     = self::DEFAULTUSERID;
	private $headrequestid  = self::DEFAULTREQUESTID;

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
		$head = array();
		$head['id']         = $this->headuserid;
		$head['r_id']       = $this->headrequestid;
		$head['signature']  = hash("sha256", $data);
		$head['time']       = microtime();
		$head['size']       = strlen($s_data);
		$head['cipher']     = $this->chiper;

		$head = json_encode($head);

		if(self::_is_compress($flag))
			$head = self::_compress($head);

		if(self::_is_encrypt($flag))
			$head = self::_encrypt($head, $this->headKey, false);

		$head = base64_encode($head);

		$headlenght = strlen($head);
		$headlenght = sprintf("%04X", $headlenght);

		$flag       = sprintf("%1X", $flag);


		return $headlenght.$flag.$head.$s_data;
	}

	## DECODE DATA ##
	public function  json_atp_decode($data, $keys = null){
		if(!is_string($data) || strlen($data) < 2){
			throw new Exception("Wrong Param in function ".__METHOD__, __LINE__);
			return false;
		}


		//set default timezone
		date_default_timezone_get("GMT+0");

		//Check keys
		if(!self::_checkKeys())
			return false;

		## GETTING SYSTEM INFORMATION( HEAD LENGTH, FLAG) ##
		$system     = substr($data, 0, 5);
		$headlenght = hexdec(substr($system, 0 , 4));
		$flag       = hexdec(substr($system, -1));

		## GETTING HEAD AND DATA PARTS ##
		$head = substr($data, 5, $headlenght);
		$data = substr($data, $headlenght+5);

		$head = base64_decode($head);
		$data = base64_decode($data);



		## DECRYPT DATA AND HEAD IF NEED ##
		if(self::_is_encrypt($flag)){
			$head = self::_decrypt($head, $this->headKey);
		}

		## DECOMPRESS DATA AND HEAD IF NEED ##
		if(self::_is_compress($flag)){
			$head = self::_decompress($head);
		}

		$head = json_decode($head);

		if($keys !== null){
			if(array_key_exists($head->id, $keys))
				$this->dataKey = $keys[$head->id];
			else{
				throw new Exception("No data key fin in array", __LINE__);
				return false;
			}
		}


		if(self::_is_encrypt($flag)){
			$data = self::_decrypt($data, $this->dataKey, $head->cipher);
		}

		if(self::_is_compress($flag)){
			$data = self::_decompress($data);
		}

		## CHECK SIGNATURE ##
		if(self::_checkSig($head->signature, $data))
			return $data;

		return false;
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

	## COMPRESS DATA ##
	private function _compress($data){
		$data = gzcompress($data, $this->compession);
		return $data;
	}

	## ENCRYPT DATA $f_chip sets to false if encrypt needed to head##
	private function _encrypt($data, $key, $f_chip = true){
		if($f_chip)
			$chiper = $this->chiper;
		else
			$chiper = self::OPENSSLDEFAULT;

		$ivlen  = openssl_cipher_iv_length($chiper);
		$iv     = substr(hash('sha256',$key),0,$ivlen);
		$data   = openssl_encrypt($data,$chiper,$key,true,$iv);

		return $data;
	}

	## DECOMPRESS DATA ##
	private function _decompress($data){
		$data = gzuncompress($data);
		return $data;
	}

	## DECRYPT DATA $f_chip sets to false if decrypt needed to head##
	private  function _decrypt($data, $key, $f_chip = false){
		if($f_chip)
			$chiper = $f_chip;
		else
			$chiper = self::OPENSSLDEFAULT;

		$ivlen  = openssl_cipher_iv_length($chiper);
		$iv     = substr(hash('sha256',$key),0,$ivlen);
		$data   = openssl_decrypt($data,$chiper,$key,true,$iv);
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

	## CHECK NEEDLE OF COMPRESSION ##
	private function _is_compress($flag){
		if($flag === self::COMPRESSION || $flag === self::COMPRESS_ENCRYPT)
			return true;
		else
			return false;
	}

	## CHECK NEEDLE OF ENCRYPTING ##
	private function _is_encrypt($flag){
		if($flag === self::ENCRYPTION || $flag === self::COMPRESS_ENCRYPT)
			return true;
		else
			return false;
	}

	## SETS ENCRYPTION/DECRYPTION ALGORITM ##
	public function setAlgoritm($chiper){
		$avail_cipher = openssl_get_cipher_methods();

		if(in_array($chiper,$avail_cipher))
		$this->chiper = $chiper;
	}

	## SETS COMPRESSION LEVEL ##
	public function setCompression($compression){
		$this->compession = intval($compression);
	}

	## CHECK SIGNATURE ##
	private function _checkSig($sig, $data){
		$data = hash("sha256", $data);
		if(strcmp($sig, $data) === 0)
			return true;
		else
			throw new Exception("Wrong signature", __LINE__);
		return false;
	}

	public function setHeadUsrId($uuid){
		$this->headuserid = $uuid;
	}

	public function setHeadReqId($uuid){
		$this->headrequestid = $uuid;
	}

} 