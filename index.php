<?php
/**
  * wechat php test
  *文件的编码格式为UTF-8 不能包含bom文件头
  */

//define your token
define("TOKEN", "weixin");
define("HTTP_HOST", "myapp2017.gear.host");
$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	//校验函数
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
			//输出前请添加此方法清空缓存
			ob_clean();
        	echo $echoStr;
        	exit;
        }
    }
	
	/**
	*相应用户输入
	**/
	public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            $result = "";
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            echo $result;
        }else {
            echo "";
            exit;
        }
    }
	
	//关注事件处理
    private function receiveEvent($object)
    {
        switch ($object->Event)
        {
            case "subscribe":
                $content = "您好，欢迎关注小刘实验室！";
                break;
        }
        //$result = $this->transmitText($object, $content);
        $result = $this->buildHelpXml($object);
        return $result;
    }
	
	//文本处理
    private function receiveText($object)
    {
		$result="";
        $keyword = trim($object->Content);
		
		//$txt = "2016年12月3日 下午2:52:09\n";
		//$txt .= "正在派送途中,请您准备签收(派件人:曹金岭,电话:13914165284)";
		//return $this->transmitText($object, $txt);

		if(substr($keyword,0,6) == '天气')
		{
			$url="https://api.thinkpage.cn/v3/weather/daily.json?key=g5u9oz1sb3zkowq1&location=".urlencode(substr($keyword,7))."&days=3";
			$output = $this->curlGet($url);
			$content = json_decode($output, true);

			if(array_key_exists('status_code',$content))
			{
				$error_msg = "对不起，您的请求被外星人劫持了，请稍后重试！";
				if($content['status_code']=='AP010010'||$content['status_code']=='AP010006')
				{
					$error_msg = "http://v.juhe.cn/wepiao/go?key=c238b5a522cbb6d1d972f2e6145447d9&s=weixin";
				}
				return $this->transmitText($object,$error_msg);
			}
			$result = $this->buildWeatherXml($object, $content['results'][0]);
		}
		else if(substr($keyword,0,6) == '快递')
		{
			$kd = $this->getKTCode(substr($keyword,7,6));
			$url="http://www.kuaidi100.com/query?type=".$kd."&postid=".substr($keyword,14);
			$output = $this->curlGet($url);
			$content = json_decode($output, true);

			if($content[message] != "ok")
			{
				$error_msg = "你输入的单号不存在或者已经过期！";
				if($content['message']=='参数错误')
				{
					$error_msg = "您输入的快递公司不存在，请确认！";
				}
				return $this->transmitText($object,$error_msg);
			}
			return $this->buildKuaiDiXml($object, $content);
			
			/*
			$rtnMsg = substr($keyword,7)."\n";
			foreach($content['data'] as $item)
			{
				$rtnMsg .= $item['time']."\n";
				$rtnMsg .= $item['context']."\n";
				$rtnMsg .= " \n";
			}
			$result = $this->transmitText($object, $rtnMsg);
			*/
		}
		else if(substr($keyword,0,6) == '百科')
		{
			$url="http://baike.baidu.com/api/openapi/BaikeLemmaCardApi?bk_length=600&scope=103&format=json&appid=379020&bk_key=".substr($keyword,7);
			$output = $this->curlGet($url);
			$content = json_decode($output, true);

			if(!array_key_exists('id',$content))
			{
				$error_msg = "您输入的词条不存在或格式错误，请查看帮助！";
				return $this->transmitText($object,$error_msg);
			}
			$result = $this->buildBaiKeXml($object, $content);
		}
		//else if($keyword == 'help' || $keyword == '帮助' || $keyword == '？' || $keyword == '?')
		else
		{
			$result = $this->buildHelpXml($object);
		}

        return $result;
    }
	
	/**
	*文本消息
	**/
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $textTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[text]]></MsgType>
		<Content><![CDATA[%s]]></Content>
		</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

	//快递查询
    private function buildKuaiDiXml($object,$data)
	{
		//error_log(var_dump($data));
        $itemTpl = "<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
		<Url><![CDATA[%s]]></Url>
    	</item>";
		$desc = "";
		foreach($data['data'] as $item)
		{
			$desc .= $item['time']."\n";
			$desc .= $item['context']."\n";
			$desc .= " \n";
		}
        $item_str = sprintf($itemTpl, '快递查询', $desc, 'http://'.HTTP_HOST.'/image/kd.jpg','http://'.HTTP_HOST.'/kuaidi.php?type='.$data['com'].'&postid='.$data['nu']);    
		return $this->transmitNews($object,$item_str,1);
	}
	
	//百科词条
    private function buildBaiKeXml($object,$data)
	{
		//error_log(var_dump($data));
        $itemTpl = "<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
		<Url><![CDATA[%s]]></Url>
    	</item>";
        $item_str = sprintf($itemTpl, $data['title'], str_ireplace("%","",$data['abstract']), $data['image'],$data['wapUrl']);    
		return $this->transmitNews($object,$item_str,1);
	}

	//天气查询
	private function buildWeatherXml($object,$data)
	{
		//error_log(var_dump($data));
        if(!is_array($data['daily']) || count($data['daily'])==0){
            return "";
        }
        $itemTpl = "<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
		<Url><![CDATA[%s]]></Url>
    	</item>";
        $item_str = "";
		$tmp = 0;
        foreach ($data['daily'] as $item)
		{
			if($tmp++ == 0)
			{
				$item_str .= sprintf($itemTpl, $data['location']['name'].' '.substr($item['date'],5).' '.$item['text_day'].' '.$item['high'].'°C~'.$item['low'].'°C', $data['location']['name'].$item['date'], 'http://'.HTTP_HOST.'/icons/3d_180/'.$item['code_day'].'.png','http://'.HTTP_HOST.'/kuaidi.php?type=shunfeng&postid=957234339430');
			}
			else
			{
				$item_str .= sprintf($itemTpl, $data['location']['name'].' '.substr($item['date'],5).' '.$item['text_day'].' '.$item['high'].'°C~'.$item['low'].'°C', $data['location']['name'].$item['date'], 'http://'.HTTP_HOST.'/icons/3d_60/'.$item['code_day'].'.png','');
			}
            
        }
		return $this->transmitNews($object,$item_str,count($data['daily']));
	}
	
	private function buildHelpXml($object)
	{
        $itemTpl = "<item>
        <Title><![CDATA[使用帮助]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
		<Url><![CDATA[%s]]></Url>
    	</item>";
		$desc = "欢迎关注小刘实验室\n";
		$desc .= "您可以使用以下功能，使用方法如下：\n";
		$desc .= "1. 帮助 输入格式：?或help或帮助;\n";
		$desc .= "2. 天气 输入格式：天气 无锡;\n";
		$desc .= "3. 快递 输入格式：快递 顺丰 单号;\n";
		$desc .= "4. 百科 输入格式：百科 歼10;\n";
		$desc .= "祝您使用愉快，欢迎提出合理化建议。\n";
		$desc .= "邮箱：hndz.lxf@qq.com 微信：发条兔子";
		$item_str = sprintf($itemTpl, $desc, 'http://'.HTTP_HOST.'/image/help.jpg','http://mp.weixin.qq.com/s/gOnrd1II-xgodJYRiOLiGg');
		return $this->transmitNews($object,$item_str,1);
	}
	
	/**
	*返回新闻消息
	**/
    private function transmitNews($object, $item_str, $count)
    {
        //error_log($item_str);
        $newsTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>".$item_str."</Articles>
		</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), $count);
        return $result;
    }
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	private function getCityCode($KeyWord)
	{
		$output = file_get_contents('city.json');
		$p = json_decode($output,true);
		//echo $p['城市代码'][0]['市'][0]['市名'];
		//echo $p['城市代码'][0]['市'][0]['编码'];
		foreach($p['城市代码'] as $item)
		{
		    foreach($item['市'] as $item2)
		    {
				if($item2['市名']==$KeyWord)
				{
					return $item2['编码'];
				}
		    }
		}
		return '101010100';
	}
	
	private function getKTCode($KeyWord)
	{
		$output = file_get_contents('kuaidi.json');
		$p = json_decode($output,true);
		if(array_key_exists($KeyWord,$p))
		{
			return $p[$KeyWord];
		}
		return '';
	}
	
	/**
	*get方式获取URL内容
	**/
	private function curlGet($url, $timeout = 30)
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

?>