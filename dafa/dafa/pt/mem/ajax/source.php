<?php
if(!isset($_SESSION)){ session_start();}
header("Expires: Mon, 26 Jul 1970 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
$C_Patch=$_SERVER['DOCUMENT_ROOT'];
include_once($C_Patch."/app/member/include/address.mem.php");
include_once($C_Patch."/app/member/include/com_chk.php");
include_once($C_Patch."/app/member/utils/convert_name.php");
include_once($C_Patch."/app/member/utils/time_util.php");
include_once($C_Patch."/app/member/class/lottery_sf.php");
include_once($C_Patch."/app/member/class/lottery_schedule.php");
include_once($C_Patch."/app/member/class/odds_sf.php");
include_once($C_Patch."/app/member/cache/ltConfig.php");
include_once($C_Patch."/app/member/class/sys_announcement.php");
$msg = sys_announcement::getOneAnnouncement();

$gType = $_GET["game"];
$titleName = getZhPageTitle($gType);

//判断是否关闭彩票
if($gType=="GDSF" && $Lottery_set['gdsf']['close']==1){
    $is_close = "true";
}elseif($gType=="TJSF" && $Lottery_set['tjsf']['close']==1){
    $is_close = "true";
}elseif($gType=="GXSF" && $Lottery_set['gxsf']['close']==1){
    $is_close = "true";
}elseif($gType=="BJPK" && $Lottery_set['pk10']['close']==1){
    $is_close = "true";
}elseif($gType=="BJKN" && $Lottery_set['kl8']['close']==1){
    $is_close = "true";
}elseif($gType=="GD11" && $Lottery_set['gd11']['close']==1){
    $is_close = "true";
}
if(in_array($gType, array("GXSF","GDSF","TJSF","BJPK","BJKN","GD11"))){
    $firstLottery = lottery_schedule::getFirstLottery($titleName);
    $lastLottery = lottery_schedule::getLastLottery($titleName);
    if($firstLottery["kaipan_time"] >= date("H:i:s",time()) || date("H:i:s",time()) >= $lastLottery["kaijiang_time"]){
        $rs = $firstLottery;
        $rsPrev = $lastLottery;
        if(date("H:i:s",time()) >= $lastLottery["kaijiang_time"]){
            $isLateNight = "true";
        }
        if($firstLottery["kaipan_time"] >= date("H:i:s",time())){
            $isEarlyMorning = "true";
        }
        $isOutTime = "true";
    }elseif($firstLottery["kaipan_time"] < date("H:i:s",time()) && date("H:i:s",time()) < $firstLottery["kaijiang_time"]){
        $rs = $firstLottery;
        $rsPrev = $lastLottery;
        $isFirstLottery = "true";
    }else{
        $rs = lottery_schedule::getNewestLottery($titleName);
        $rsPrev = lottery_schedule::getPrevLottery($titleName, $rs["qishu"]-1);
        $isNormalLottery = "true";
    }
}
$odds_result = "null";
if($rs){
    $isLateNight=="true" ? ($time = time() + 86400) : ($time = time());
    if($gType == "GXSF"){//这里获取广西十分彩期数
        $times=date("H:i:s",time());
        $lasttime=$Lottery_set['gxsf']['ktime']." ".$times;
        $thistime=date("Y-m-d H:i:s",time());
        $re_date=retimeDiffs($lasttime,$thistime);
        $lost_days=$re_date[ ' day ' ];//实际相差的天数要减去头尾两天

        if($isLateNight == "true"){
            $qishu = (substr($Lottery_set['gxsf']['knum'],0,7)+$lost_days+1).'01';//当期数是每天的第一期时，天数加一天，并且定位到第一期:01
        }else{
            $qishu = (substr($Lottery_set['gxsf']['knum'],0,7)+$lost_days).$rs['qishu'];
        }
    }elseif($gType == "BJKN" || $gType == "BJPK"){
        if($gType == "BJKN"){
            $type = 'kl8';
        }elseif($gType == "BJPK"){
            $odds1 = odds_sf::getOddsByBall("北京PK拾","选号","ball_1");
            $type = 'pk10';
            $odds_result = '{"FIRST:2:MATCH:1":'.$odds1["h2"].',"FIRST:2:MATCH:2":'.$odds1["h1"].',"FIRST:3:MATCH:1":0,"FIRST:3:MATCH:2":'.$odds1["h4"].',"FIRST:3:MATCH:3":'.$odds1["h3"].',"FIRST:4:MATCH:1":0,"FIRST:4:MATCH:2":'.$odds1["h7"].',"FIRST:4:MATCH:3":'.$odds1["h6"].',"FIRST:4:MATCH:4":'.$odds1["h5"].'}';
        }
        $times=date("H:i:s",time());
        $lasttime=$Lottery_set[$type]['ktime']." ".$times;
        $thistime=date("Y-m-d H:i:s",time());
        $re_date=retimeDiffs($lasttime,$thistime);
        $lost_days=$re_date[ ' day ' ];//实际相差的天数要减去头尾两天
        if($isLateNight == "true"){
            $lost_days += 1;
        }
        $qishu=($lost_days-1)*179+$Lottery_set[$type]['knum']+$rs['qishu'];
    }else{
        $qishu		= date("Ymd",$time).$rs['qishu'];
    }
    $kaipanTime	    = strtotime(date("Y-m-d",$time).' '.$rs['kaipan_time']);
    $fengpanTime	= strtotime(date("Y-m-d",$time).' '.$rs['fenpan_time']);
    $kaijiangTime	= strtotime(date("Y-m-d",$time).' '.$rs['kaijiang_time']);
}else{
    $qishu		= -1;
    $kaipanTime	    = -1;
    $fengpanTime	= -1;
    $kaijiangTime	= -1;
}

if($rsPrev){
    ($isEarlyMorning=="true" || $isFirstLottery=="true") ? ($time = time() - 86400) : ($time = time());
    if($gType == "GXSF"){//这里获取广西十分彩上一期期数
        $times=date("H:i:s",time());
        $lasttime=$Lottery_set['gxsf']['ktime']." ".$times;
        $thistime=date("Y-m-d H:i:s",time());
        $re_date=retimeDiffs($lasttime,$thistime);
        $lost_days=$re_date[ ' day ' ];//实际相差的天数要减去头尾两天

        if($isFirstLottery == "true" || $isEarlyMorning=="true"){
            $qishuPrev = (substr($Lottery_set['gxsf']['knum'],0,7)+$lost_days-1).'50';//当期数是每天的第一期时，天数加一天，并且定位到第一期:01
        }else{
            $qishuPrev = (substr($Lottery_set['gxsf']['knum'],0,7)+$lost_days).$rsPrev['qishu'];
        }
    }elseif($gType == "BJPK" || $gType == "BJKN"){
        $qishuPrev = $qishu - 1;
    }else{
        $qishuPrev		= date("Ymd",$time).$rsPrev['qishu'];
    }
    $kaipanTimePrev	    = strtotime(date("Y-m-d",$time).' '.$rsPrev['kaipan_time']);
    $fengpanTimePrev	= strtotime(date("Y-m-d",$time).' '.$rsPrev['fenpan_time']);
    $kaijiangTimePrev	= strtotime(date("Y-m-d",time()).' '.$rsPrev['kaijiang_time']);
}else{
    $qishuPrev		= -1;
    $kaipanTimePrev	    = -1;
    $fengpanTimePrev	= -1;
    $kaijiangTimePrev	= -1;
}

$prev_game_result = "null";
if(date("Y-m-d H:i:s",$fengpanTime) <= $bj_time_now && $bj_time_now <= date("Y-m-d H:i:s",$kaijiangTime) || $is_close == "true"){
    //开奖界面
    $opening_game = '"opening_game":{"game_id":null,"num":"","type":"'.$gType.'","result":null,"state":0,"open_timestamp":0,"close_timestamp":0},';
    //查询结果
    if($isNormalLottery=="true" || $isFirstLottery=="true"){
        $qishuPrev		= $qishu;
        $kaipanTimePrev	    = $kaipanTime;
        $fengpanTimePrev	= $fengpanTime;
        $kaijiangTimePrev	= $kaijiangTime;
    }

    if($gType == "GDSF" || $gType == "TJSF"){
        if($lResult && !is_null($lResult['ball_1']) && !is_null($lResult['ball_2'])
            && !is_null($lResult['ball_3']) && !is_null($lResult['ball_4']) && !is_null($lResult['ball_5'])
            && !is_null($lResult['ball_6']) && !is_null($lResult['ball_7']) && !is_null($lResult['ball_8'])){

            $prev_game_result = '{"01":'.$lResult['ball_1'].',"02":'.$lResult['ball_2'].',"03":'.$lResult['ball_3'].',"04":'.$lResult['ball_4'].',"05":'.$lResult['ball_5'].',"06":'.$lResult['ball_6'].',"07":'.$lResult['ball_7'].',"08":'.$lResult['ball_8'].'}';
        }
    }elseif($gType == "GXSF"){
        if($lResult && !is_null($lResult['ball_1']) && !is_null($lResult['ball_2'])
            && !is_null($lResult['ball_3']) && !is_null($lResult['ball_4']) && !is_null($lResult['ball_5'])){

            $prev_game_result = '{"01":'.$lResult['ball_1'].',"02":'.$lResult['ball_2'].',"03":'.$lResult['ball_3'].',"04":'.$lResult['ball_4'].',"05":'.$lResult['ball_5'].'}';
        }
    }
}else{//展现正常界面
    $opening_game = '"opening_game":{"game_id":"101","num":"'.$qishu.'","type":"'.$gType.'","state":"1","result":null,"open_timestamp":"'.$kaipanTime.'","close_timestamp":"'.$fengpanTime.'","official_open_timestamp":null,"official_close_timestamp":null,"official_result_timestamp":null,"created_at":"'.$kaipanTime.'"},';
}

if("true"!=$is_just_data){
    echo '
    {
        "server_time":'.time().',
        "timezone_offset":-480,
        "line":"D",
        "debug":{"line":"0.01342s","username":"7.0E-5s","opening_game":"0.00125s","next_game":"0.00044s","prev_game":"0.00045s","odds":"0.02874s","disable_odds":"0.002s","marquee":"0.00051s"},
        "username":"'.$_SESSION["username"].'",
        '.$opening_game.'
        "next_game":{"game_id":null,"num":"","type":"'.$gType.'","result":null,"state":1,"open_timestamp":0,"close_timestamp":0},
        "prev_game":{"game_id":"100","num":"'.$qishuPrev.'","type":"'.$gType.'","state":"1","result":'.$prev_game_result.',"open_timestamp":"'.$kaipanTimePrev.'","close_timestamp":"'.$fengpanTimePrev.'","official_open_timestamp":null,"official_close_timestamp":null,"official_result_timestamp":null,"created_at":"'.$kaipanTimePrev.'"},
        "odds":'.$odds_result.',
        "disable_odds":[],
        "marquee":["'.str_replace("&nbsp;","",$msg).'"],
        "execute_time":0.047250032424927
    }';
    $mysqli->close();
}