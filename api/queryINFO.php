<?php
function get_status($NetworkCardName){
    $fp = popen('top -b -n 1 | grep -E "(Cpu\(s\))|(KiB Mem)"', "r");
    $rs = '';
    while(!feof($fp)){
       $rs .= fread($fp, 1024);  
    }
    pclose($fp);
    //获取cpu
    $sys_info = explode("\n", $rs);
    $cpu_info = explode(",", $sys_info[0]);
    $cpu_usage = trim(trim($cpu_info[0], '%Cpu(s): '), 'us'); //百分比
    //获取内存
    $mem_info = explode(",", $sys_info[1]); //内存占有量 数组
    $mem_total = trim(trim($mem_info[0], 'KiB Mem : '), ' total');
    $mem_used = trim(trim($mem_info[2], 'used'));
    $mem_usage = round(100 * intval($mem_used) / intval($mem_total), 2); //百分比
 
    //获取磁盘占用率
    $fp = popen('df -lh | grep -E "^(/)"', "r");
    $rs = fread($fp, 1024);
    pclose($fp);
    $rs = preg_replace('/\s{2,}/', ' ', $rs);  //把多个空格换成 “_”
    $hd = explode(" ", $rs);
    
    $hd_usage = trim($hd[4], '%'); //磁盘可用空间百分比
    
    //获取网卡速度
    $strs = @file("/proc/net/dev"); 
    preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$NetworkCardName], $info );
    $NetOutSpeed[3] = $info[10][0];
    $NetInputSpeed[3] = $info[2][0];

    echo "{".'"cpu"'.":".$cpu_usage.",".'"mem"'.":".$mem_usage.",".'"hdusage"'.":".$hd_usage.",".'"NetOutSpeed"'.":".$NetOutSpeed[3].",".'"NetInputSpeed"'.":".$NetInputSpeed[3]."}";
}

if ( isset($_POST['infomation']) && $_POST['infomation'] == "true" && $_POST['token'] == hash('sha256', $_COOKIE['PHPSESSID'])){
    get_status($_POST['NetworkCardName']);
	exit();
}
?>