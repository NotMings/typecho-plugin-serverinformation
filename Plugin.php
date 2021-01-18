<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 用于查看服务器基本信息的插件
 * 
 * @package ServerInformation
 * @author 不明
 * @version 1.0.0
 * @link https://networkos.club
 */
class ServerInformation_Plugin implements Typecho_Plugin_Interface
{
    public static function activate(){
        $msg = ServerInformation_Plugin::check();
        Helper::addPanel(1, 'ServerInformation/Info.php','服务器基本信息','服务器基本信息','administrator');
        Typecho_Plugin::factory('Widget_Archive')->header = array('ServerInformation_Plugin', 'render');
        return _t($msg);
        //return 'activate';
    }

    public static function deactivate(){
         Helper::removePanel(1, 'ServerInformation/Info.php');
        //return 'deactivated';
    }
    
    public static function config(Typecho_Widget_Helper_Form $form){
        $Network_Card = array(
            '2'  => '第一张网卡',
            '3'  => '第二张网卡',
        );

        $name = new Typecho_Widget_Helper_Form_Element_Select('card_number',$Network_Card, '2', _t('选择网卡'));
        $form->addInput($name->addRule('required', _t('必须选择网卡'), $Network_Card));

    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    public static function render(){}   

    public static function check(){
        $get_OS = php_uname('s');
        if ($get_OS != 'Linux'){
            throw new Typecho_Plugin_Exception('当前系统不为Linux，暂时只支持Linux系统');
        }else{
            return '若流量监控显示错误请选择网卡';
        }
    }
}
