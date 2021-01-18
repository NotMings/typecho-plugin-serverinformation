<?php
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

if (!defined('__TYPECHO_ADMIN__')) {
    define('__TYPECHO_ADMIN__', true);
}

/** 载入配置文件 */
if (!defined('__TYPECHO_ROOT_DIR__') && !@include_once __DIR__ . '/../config.inc.php') {
    file_exists(__DIR__ . '/../install.php') ? header('Location: ../install.php') : print('Missing Config File');
    exit;
}

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');

/** 注册一个初始化插件 */
Typecho_Plugin::factory('/admin/common.php')->begin();

Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_User')->to($user);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;

/** 检测是否是第一次登录 */
$currentMenu = $menu->getCurrentMenu();
list($prefixVersion, $suffixVersion) = explode('/', $options->version);
$params = parse_url($currentMenu[2]);
$adminFile = basename($params['path']);

if (!$user->logged && !Typecho_Cookie::get('__typecho_first_run') && !empty($currentMenu)) {
    
    if ('welcome.php' != $adminFile) {
        $response->redirect(Typecho_Common::url('welcome.php', $options->adminUrl));
    } else {
        Typecho_Cookie::set('__typecho_first_run', 1);
    }
    
} else {

    /** 检测版本是否升级 */
    if ($user->pass('administrator', true) && !empty($currentMenu)) {
        $mustUpgrade = (!defined('Typecho_Common::VERSION') || version_compare(str_replace('/', '.', Typecho_Common::VERSION),
        str_replace('/', '.', $options->version), '>'));

        if ($mustUpgrade && 'upgrade.php' != $adminFile && 'backup.php' != $adminFile) {
            $response->redirect(Typecho_Common::url('upgrade.php', $options->adminUrl));
        } else if (!$mustUpgrade && 'upgrade.php' == $adminFile) {
            $response->redirect($options->adminUrl);
        } else if (!$mustUpgrade && 'welcome.php' == $adminFile && $user->logged) {
            $response->redirect($options->adminUrl);
        }
    }

}

include 'header.php';
include 'menu.php';
?>

<link rel="stylesheet" href="<?php $options->pluginUrl('ServerInformation/layui/css/layui.css')?>">
<div style="padding: 20px">
  <div class="layui-row layui-col-space15">
    <div class="layui-col-md5">
      <div class="layui-card">
        <div class="layui-card-header">状态监控</div>
        <div class="layui-card-body">
            <h4>cpu使用率</h4>
            </br>
            <div class="layui-progress" lay-showpercent="true" lay-filter="cpu">
                <div class="layui-progress-bar layui-bg-red" lay-percent="0%"></div>
            </div>
            </br>
            <h4>内存使用率</h4>
            </br>
            <div class="layui-progress" lay-showpercent="true" lay-filter="mem">
                <div class="layui-progress-bar layui-bg-orange" lay-percent="0%"></div>
            </div>
            </br>
            <h4>磁盘已使用空间</h4>
            </br>
            <div class="layui-progress" lay-showpercent="true" lay-filter="hdusage">
                <div class="layui-progress-bar layui-bg-cyan" lay-percent="0%"></div>
            </div>
            </br>
        </div>
      </div>
      
      <div class="layui-col-md12">
      <div class="layui-card">
        <div class="layui-card-header">服务器基本信息</div>
            <div class="layui-card-body">
                <table class="layui-table">
                  <tbody>
                    <tr>
                        <td>服务器标识</td>
                        <td><?php echo php_uname() ?></td>
                    </tr>
                    <tr>
                        <td>系统类型</td>
                        <td><?php echo php_uname('s') ?></td>
                    </tr>
                    <tr>
                        <td>服务器IP</td>
                        <td><?php echo GetHostByName($_SERVER['SERVER_NAME']) ?></td>
                    </tr>
                    <tr>
                        <td>服务器语言</td>
                        <td><?php echo $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?></td>
                    </tr>
                    <tr>
                        <td>服务器端口</td>
                        <td><?php echo $_SERVER['SERVER_PORT'] ?></td>
                    </tr>
                    <tr>
                        <td>服务器解译引擎</td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?></td>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
    </div>     
    </div>
    <div class="layui-col-md7">
      <div class="layui-card">
        <div class="layui-card-header">实时流量</div>
        <div class="layui-card-body">
            <div id="main" style="width:100%;height:597px;"></div>
        </div>
      </div>
    </div>    
  </div>
</div> 

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>

<script src="<?php $options->pluginUrl('ServerInformation/layui/layui.js')?>"></script>
<script src="<?php $options->pluginUrl('ServerInformation/js/echarts.min.js')?>"></script>
<script src="<?php $options->pluginUrl('ServerInformation/js/jssha256.js')?>"></script>
<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
<script type="text/javascript">
layui.use('element', function(){
    var $ = layui.jquery
    ,element = layui.element; //Tab的切换功能，切换事件监听等，需要依赖element模块   
    //网卡流量
    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('main'));
    var colors = ['#5793f3', '#d14a61', '#675bba'];
    myChart.setOption({
        color: colors,

        tooltip: {
            trigger: 'none',
            axisPointer: {
                type: 'cross'
            }
        },
        legend: {
            data:['入站流量', '出站流量']
        },
        grid: {
            top: 70,
            bottom: 50
        },
        xAxis: [
            {
                name: '时间',
                type: 'category',
                axisTick: {
                    alignWithLabel: true
                },
                axisLine: {
                    onZero: false,
                    lineStyle: {
                    color: colors[1]
                    }
                },
                axisPointer: {
                    label: {
                        formatter: function (params) {
                            return '速度  ' + params.value
                                + (params.seriesData.length ? '：' + params.seriesData[0].data : '');
                        }
                    }
                },
                data: []
            },
            {
                name: '时间',
                type: 'category',
                axisTick: {
                    alignWithLabel: true
                },
                axisLine: {
                    onZero: false,
                    lineStyle: {
                        color: colors[0]
                    }
                },
                axisPointer: {
                    label: {
                        formatter: function (params) {
                            return '速度  ' + params.value
                                + (params.seriesData.length ? '：' + params.seriesData[0].data : '');
                        }
                    }
                },
                data: []
            }
        ],
        yAxis: [
            {
                name: '流量(Kb/s)',
                type: 'value',
                nameGap: 22,
                min: 0.4
            }
        ],
        series: [
            {
                name:'入站流量',
                type:'line',
                xAxisIndex: 1,
                smooth: true,
                data: []
            },
            {
                name:'出站流量',
                type:'line',
                smooth: true,
                data: []
            }
        ]
    });
    
    function getCookie(name){
    var arr,reg = new RegExp("(^|)" + name + "=([^;]*)(;|$)");
        if (arr = document.cookie.match(reg)) {
            return unescape(arr[2]);
        } else {
            return null;
        }
    } 

    var InputSpeed = 0;
    var OutSpeed = 0;
    var inspeed = new Array();
    var outspeed = new Array();
    var time = new Array();
    var count = 0;
    var token = SHA256_hash(getCookie('PHPSESSID'));
    function post_inf(){
        $.post('<?php $options->pluginUrl('ServerInformation/api/queryINFO.php')?>',{infomation: 'true', NetworkCardName: '<?php echo Helper::options()->plugin('ServerInformation')->card_number?>', token: token} , function(data) {
            var today = new Date();
            var h = today.getHours();
            var m = today.getMinutes();
            var s = today.getSeconds();
            var today = [h,m,s].join(":");
            
            element.progress('cpu', data.cpu+'%');
            element.progress('mem', data.mem+'%');
            element.progress('hdusage', data.hdusage+'%');
            var netspeed_in = Math.round(((data.NetInputSpeed-InputSpeed)/1024)*100)/100;InputSpeed=data.NetInputSpeed;
            var netspeed_out = Math.round(((data.NetOutSpeed-OutSpeed)/1024)*100)/100;OutSpeed=data.NetOutSpeed;
            
            if (count > 0){
                inspeed.push(netspeed_in);
                outspeed.push(netspeed_out);
                time.push(today);
            }else{
                count += 1;
            }
            
            if (inspeed.length >= 20){
                inspeed.shift();   
            }
            if (outspeed.length >= 20){
                outspeed.shift();   
            }
            if (time.length >= 20){
                time.shift();   
            }
            myChart.setOption({
                xAxis: [
                {
                    data: time
                },
                {
                    data: time
                }
                ],
                series: [
                {
                    name:'入站流量',
                    data: inspeed
                },
                {
                    name:'出站流量',   
                    data: outspeed
                }
                ]
            });
            setTimeout (post_inf,500);
        }, "json");
    };
    post_inf();
    });
</script>
<?php
include 'footer.php';
?>
