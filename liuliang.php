<?php
/**
 * 充值流量接口
 * Author: zhangyuan
 * Date: 06/27/16
 */
class Liuliang
{	
	const APPKEY = '1466996986838';//分配的appkey
	const APPSECRET = 'eb035aa3a04ae659a26b34099338a7a4';
	const PARTNERID = '10239';
	const DOMAIN = 'http://partnerapi.dev.zt.raiyi.com/';
	
	//private $mobile='18510489303';
	//private $isp_product_id='10017';
	//private $order_id=date('YmdHis').strval(rand(10000,99999));

	//$res=single_charge($mobile,$isp_product_id,$order_id);
	//var_dump($res);
	
	/**
	 * @param string $timestamp 时间戳
	 * @param array $params    排序参数
	 * @return 生成签名
	 */
	public function sign_param($timestamp,$params)
	{
		
		ksort($params);

		$sign_str = '';
		foreach($params as $key=>$value){
			$sign_str .= $key.'='.$value;
		}

		$sign_str = self::APPKEY.'authTimespan='.$timestamp.$sign_str.self::APPSECRET;	
		$sign_res = md5($sign_str);	
		return $sign_res;
	}
	
	/**
	 * @param string $encry_str 手机号
	 * @return 生成RSA加密手机号
	 */
	public function rsa ($encry_str)
	{
		$raw_pub_key_json = $this->get_public_key();
		$data = json_decode($raw_pub_key_json,true);
		$raw_pub_key = $data['public_key'];

		$pub_key = $this->pubkeyFormat($raw_pub_key);

		$encry_res = '';
		openssl_public_encrypt($encry_str,$encry_res,$pub_key);

		$encry_base64 = base64_encode($encry_res);

		return $encry_base64;
	}

	public function pubkeyFormat($rawPubkey){
		$pubkey = chunk_split($rawPubkey,64,"\n");

		return "-----BEGIN PUBLIC KEY-----\n$pubkey-----END PUBLIC KEY-----";
	}

	/**
		公钥获取接口
	*/
	public function get_public_key(){
		$url = self::DOMAIN.'/v1/public/'.self::PARTNERID.'/common/getPublicKey';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$ret = curl_exec($ch);

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$rspCode = '';
		$pulic_key = '';
		if ($http_code == 200 && $ret) {
			$data = json_decode($ret,true);
			$rspCode = $data['code'];
			if($rspCode == '0000'){
				$pulic_key = $data['data']['publicKey'];
			}else{
				$pulic_key = '';
			}
		}

		$result = json_encode(array('isp_status_code'=>$rspCode,'public_key'=>$pulic_key));

		return $result;
	}

	/**
		流量充值接口
	 * @param string $mobile  手机号	
	 * @param string $isp_product_id  产品id	
	 * @param string $order_id  订单号 (唯一)	
	*/
	public function single_charge($mobile, $isp_product_id,$order_id)
	{
		$url = self::DOMAIN.'/v1/private/'.self::PARTNERID.'/order/buyFlow?';

		$timestamp = date('YmdHis',time());

		$ras_mobile = $this->rsa($mobile);
		$params = array(
			'mobile' => $ras_mobile,
			'productId' => $isp_product_id,
			'partnerOrderNo'=>$order_id,
			//'notifyUrl'=>$ul_callback
		);
		$json_params = $this->sign_param($timestamp,$params);

		$url_params = array(
			'authTimespan'=>urlencode($timestamp),
			'authSign'=>urlencode($json_params),
			'authAppkey'=>urlencode(self::APPKEY),
			'productId'=>urlencode($isp_product_id),
			'partnerOrderNo'=>urlencode($order_id),
			'mobile'=>$ras_mobile,
			//'notifyUrl'=>$ul_callback
		);
		$url_query = http_build_query($url_params);
		$url_charge = $url.$url_query;

		echo $url_charge."<hr>";

		//Send request
		$ch = curl_init($url_charge);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$ret = curl_exec($ch);      
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		//return $ret;
		//exit;
		$rspCode = '';
		$resultMsg = '';
		$isp_order_id = '';
		$ok = false;
		if ($http_code == 200 && $ret) {
			$data = json_decode($ret,true);
			$rspCode = $data['code'];
			if($rspCode == '0000'){
				$ok = true;
				$isp_order_id = $data['data']['orderNo'];
			}else{
				$ok = false;
				$isp_order_id = '';
			}
		}
		if($ok){
			$rspCode = 'E10000';
		}else{
			$rspCode = '17-'.$rspCode;
		}

		//$result = json_encode(array('order_id'=>$order_id,'isp_status_code'=>$rspCode,'orderNo'=>$isp_order_id,'desc'=>$resultMsg));
		$result = json_encode(array('orderNo'=>$isp_order_id));
		return array($ok, $result);
	}
}

	$mobile='18510489303';
	$isp_product_id='10017';
	$order_id=date('YmdHis').strval(rand(10000,99999));
	$liuliang = new Liuliang();
	$res = $liuliang->single_charge($mobile,$isp_product_id,$order_id);
	var_dump($res);
	
	