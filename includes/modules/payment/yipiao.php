<?php

/**
 * ECSHOP 易票联插件
 * ============================================================================
 * * 版权所有 2014- Angelo Lin，并保留所有权利。
 * 网站地址: http://#；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: Angelo Lin $
 * $Id: yipiao.php 17217 2014-03-13 06:29:08Z Angelo Lin $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/payment/yipiao.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/**
 * 模块信息
 */
if (isset($set_modules) && $set_modules == true)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'yipiao_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod'] = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online'] = '1';

    /* 作者 */
    $modules[$i]['author']  = 'ANGELO LIN';

    /* 网址 */
    $modules[$i]['website'] = 'http://#';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('name' => 'yp_account', 'type' => 'text', 'value' => ''),
        array('name' => 'yp_key', 'type' => 'text', 'value' => ''),
    );

    return;

}

class yipiao
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
	var $code='yipiao';
	
    function yipiao()
    {
    }

    function __construct()
    {
        $this->yipiao();
    }

   /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
   function get_code($order, $payment)
   {
       $partner		       = trim($payment['yp_account']);                 //人民币账号 不可空
       $key                = trim($payment['yp_key']);
       $return_url         = return_url2(basename(__FILE__, '.php'));			//接收即时通知
       $notify_url         = return_url2(basename(__FILE__, '.php'));			 //接收异步通知
       $version            = '3.0';
       $order_id           = $order['order_sn'];                                    //商户订单号 不可空
       $order_amount       = $order['order_amount'];                        //商户订单金额 不可空
       $order_time         = local_date('YmdHis', $order['add_time']);            //商户订单提交时间 不可空 14位
       
       $currency_typ	   = 'RMB';												//货币种类（暂只支持RMB-人民币）   币种:0:RMB
       $order_create_ip    = $_SERVER["REMOTE_ADDR"];							//创建订单的客户端IP（消费者电脑公网IP，用于防钓鱼支付）
       $sign_type 		   = 'SHA256';										//签名算法（暂时只支持MD5）
       $pay_id 			   = "";											//直连银行参数,$pay_id = "zhaohang";  //直连招商银行参数值
       $memo 			   = $order['log_id'];											 //订单备注

        $root = $_SERVER['DOCUMENT_ROOT'];
		require_once ($root."/includes/modules/payment/yipiao/classes/EpaylinksSubmit.class.php");
		
		/* 商户号 */
		!empty($partner)? '' : $partner = "130";  //130测试商户号只能在219.136.207.190 测试服务器上使用
		
		/* 商户密钥KEY */
		!empty($key)?'':$key = "857e6g8y51b5k365f7v954s50u24h14w"; //这是130测试商户的密钥，仅限于用作接入219.136.207.190测试服务器调试使用
		
		//订单备注，该信息使用64位编码提交服务器，并将在支付完成后随支付结果原样返回
		$base64_memo = base64_encode($memo);
		
		/* 支付请求对象 */
		$epaySubmit = new EpaylinksSubmit();
		$epaySubmit->setKey($key);
		//$epaySubmit->setGateUrl("https://www.epaylinks.cn/paycenter/v2.0/getoi.do");  //生产服务器
		$epaySubmit->setGateUrl("http://219.136.207.190:80/paycenter/v2.0/getoi.do");   //测试服务器
		
		//设置支付参数 
		$epaySubmit->setParameter("partner", $partner);		           //商户号
		$epaySubmit->setParameter("out_trade_no", $order_id);	   		//商家订单号
		$epaySubmit->setParameter("total_fee", $order_amount);			   //商品金额,以元为单位
		$epaySubmit->setParameter("return_url", $return_url);		   //交易完成后页面即时通知跳转的URL
		$epaySubmit->setParameter("notify_url", $notify_url);		   //接收后台通知的URL
		$epaySubmit->setParameter("currency_type", $currency_type);	   //货币种类
		$epaySubmit->setParameter("order_create_ip",$order_create_ip); //创建订单的客户端IP（消费者电脑公网IP，用于防钓鱼支付）
		$epaySubmit->setParameter("version", $version);				   //接口版本
		$epaySubmit->setParameter("sign_type", $sign_type);			   //签名算法（暂时只支持SHA256）
		
		//业务可选参数
		$epaySubmit->setParameter("pay_id", $pay_id);	        	   //直连银行参数，例子是直接转跳到招商银行时的参数
		$epaySubmit->setParameter("base64_memo", $base64_memo);		   //订单备注的BASE64编码
		
		//请求的URL
		$requestUrl = $epaySubmit->getRequestURL();
		
		$form  = '<div style="text-align:center"><form name="kqPay" style="text-align:center;" method="post" action="'.$requestUrl.'" target="_blank">';
        $form .= "<input type='submit' name='submit' value='" . $GLOBALS['_LANG']['pay_button'] . "' />";
        $form .= "</form></div></br>";
		
		return $form;
    }

    /**
     * 响应
     */
    function respond()
    {
    	$payment             = get_payment($this->code);
        $partner     		 = $payment['yp_account'];                 //人民币账号 不可空
        $key                 = $payment['yp_key'];
        
        /* 商户号 */
		!empty($partner)? '' : $partner = "130";  //130测试商户号只能在219.136.207.190 测试服务器上使用
		
		/* 商户密钥KEY */
		!empty($key)?'':$key = "857e6g8y51b5k365f7v954s50u24h14w"; //这是130测试商户的密钥，仅限于用作接入219.136.207.190测试服务器调试使用
             
		$root = $_SERVER["DOCUMENT_ROOT"];
		require_once ($root."/stra/Pay/yipiao/classes/EpaylinksNotify.class.php");
		
		$notify = new EpaylinksNotify();
		$notify->setKey($key);
		//验证签名
		if($notify->verifySign()) {
			$base64_memo = $notify->getParameter("base64_memo");
			$partner = $notify->getParameter("partner");
			$out_trade_no = $notify->getParameter("out_trade_no");
			$pay_no = $notify->getParameter("pay_no");
			$amount = $notify->getParameter("amount");
			$pay_result = $notify->getParameter("pay_result");
			$sett_date = $notify->getParameter("sett_date");
			$version = $notify->getParameter("version");
			$sign_type = $notify->getParameter("sign_type");
			$sign = $notify->getParameter("sign");
			$memo = base64_decode($base64_memo);
			
			if( "1" == $pay_result ) {
				order_paid($base64_memo);
				return true;
			
			} else {
				return false;
			}
			
		} else {
			return false;
		}
		//获取调试
		//echo $notify->getDebugMsg() ;
    }
}

?>