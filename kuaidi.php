<?php 
include 'common.php';
$commonFun = new CommonFunction();
$type = $_POST['type'];
$postid = $_POST['postid'];
if($type == "" && $postid == "")
{
	$type = $_GET['type'];
	$postid = $_GET['postid'];
}

$content=array();
if($type != "" && $postid != "")
{
	$url="http://www.kuaidi100.com/query?type=".$type."&postid=".$postid;
	$output = $commonFun->curlGet($url);
	$content = json_decode($output, true);
}
?>
<!doctype html>
<html>
	<head>
		<title>快递查询</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
		<link rel="stylesheet" href="style/weui.min.css">
		<link rel="stylesheet" href="style/jquery-weui.min.css">
		<script>
			function actionSubmit()
			{
				if($('#postid').val() == "")
				{
					$('#postid').attr("style", "border: 1px double red;");
					return;
				}
				$('#postid').removeAttr("style");
				$('#kuaidi').submit();
			}
		</script>
	</head>
	<body>

	<form id='kuaidi' method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" >
	<div style="background-color: #FAFAFA;">
    <div class="weui_cell">
        <div class="weui_cell_hd">
            <label for="" class="weui_label">快递公司</label>
        </div>
        <div class="weui_cell_bd weui_cell_primary">
            <select class="weui_select" name="type">
                <option value="shunfeng" <?php echo $type=='shunfeng'?'selected="selected"':'' ?>>顺丰速运</option>
                <option value="shentong" <?php echo $type=='shentong'?'selected="selected"':'' ?>>申通快递</option>
                <option value="ems" <?php echo $type=='ems'?'selected="selected"':'' ?>>EMS</option>
                <option value="yuantong" <?php echo $type=='yuantong'?'selected="selected"':'' ?>>圆通速递</option>
                <option value="zhongtong" <?php echo $type=='zhongtong'?'selected="selected"':'' ?>>中通快递</option>
                <option value="yunda" <?php echo $type=='yunda'?'selected="selected"':'' ?>>韵达速递</option>
                <option value="tiantian" <?php echo $type=='tiantian'?'selected="selected"':'' ?>>天天快递</option>
                <option value="huitongkuaidi" <?php echo $type=='huitongkuaidi'?'selected="selected"':'' ?>>汇通快递</option>  
                <option value="quanfengkuaidi" <?php echo $type=='quanfengkuaidi'?'selected="selected"':'' ?>>全峰快递</option>
                <option value="debangwuliu" <?php echo $type=='debangwuliu'?'selected="selected"':'' ?>>德邦物流</option>
                <option value="zhaijisong" <?php echo $type=='zhaijisong'?'selected="selected"':'' ?>>宅急送</option>
            </select>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_hd">
            <label class="weui_label">快递单号</label>
        </div>
        <div class="weui_cell_bd weui_cell_primary">
            <input id="postid" name="postid" class="weui_input" type="tel" placeholder="请输入快递单号" value="<?php echo $postid; ?>">
        </div>
    </div>
    <div class="weui_cell"> <a href="javascript:actionSubmit();" class="weui_btn weui_btn_primary">查询</a></div></div>
	</form>
    <?php 
		if(count($content)!=0)
		{
			if($content[message] != "ok")
			{
				echo '<div class="weui_cell">';
				echo "你输入的单号不存在或者已经过期！";
				echo '</div>';
			}

			$tmp = 1;
			foreach($content['data'] as $item)
			{
				if($tmp++ != 1)
				{
					echo '<div class="weui_cell">';
				}
				else
				{
					echo '<div class="weui_cell" style="color:red;font-weight:bold;">';
				}
				
				echo $item['time']."<br>";
				echo $item['context'];
				echo '</div>';
			}
		}
    ?>

    <!-- body 最后 -->
	<script src="js/jquery-1.12.4.min.js"></script>
	<script src="js/jquery-weui.min.js"></script>
	<script>
	</script>
	</body>
</html>