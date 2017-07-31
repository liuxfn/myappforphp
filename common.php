<?php
class CommonFunction
{
	public function curlPost($url, $data, $timeout = 30)
	{
		$ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
		$ch = curl_init();
		$opt = array(
			CURLOPT_URL     => $url,
			CURLOPT_POST    => 1,
			CURLOPT_HEADER  => 0,
			CURLOPT_POSTFIELDS      => (array)$data,
			CURLOPT_RETURNTRANSFER  => 1,
			CURLOPT_TIMEOUT         => $timeout,
		);
		if ($ssl)
		{
			$opt[CURLOPT_SSL_VERIFYHOST] = 1;
			$opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
		}
		curl_setopt_array($ch, $opt);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	public function curlGet($url, $timeout = 30)
	{
		$ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
		$ch = curl_init();
		$opt = array(
			CURLOPT_URL     => $url,
			CURLOPT_HEADER  => 0,
			CURLOPT_RETURNTRANSFER  => 1,
			CURLOPT_TIMEOUT         => $timeout,
		);
		if ($ssl)
		{
			$opt[CURLOPT_SSL_VERIFYHOST] = 1;
			$opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
		}
		curl_setopt_array($ch, $opt);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}
//$data = curlPost('https://api.thinkpage.cn/v3/weather/daily.json', array('key'=>'g5u9oz1sb3zkowq1','location'=>'无锡','language'=>'zh-Hans','start'=>'0','days'=>'1'));
//$data = curlGet('https://api.thinkpage.cn/v3/weather/daily.json?key=g5u9oz1sb3zkowq1&location=beijing&days=1',30);

?>