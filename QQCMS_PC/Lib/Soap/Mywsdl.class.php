<?php
/**
 +------------------------------------------------------------------------------
 * wsdl服务端
 +------------------------------------------------------------------------------
 * @wsdl服务端接收
 * @Author 犇<admin@huqiao.net>
 * @Copyright (c) www.huqiao.net
 +------------------------------------------------------------------------------
 *定义Mywsdl公开的类
 */
class Mywsdl extends Think{
    private $nombre = '';
    public function __construct($name = 'World') 
	{
		$this->name = $name;
	}
    public function greet($name = '') 
	{
		$name = $name?$name:$this->name;
        return 'Hello '.$name.'.';
	}
    public function serverTimestamp() 
	{
		return time();
	}
	public function queryOrder($xml=''){
	    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<VSPPEXRsp>
			  <trxcod>A0000001</trxcod>
			  <payinst></payinst>
			  <entinst>020440654110063</entinst>
			  <timestamp>20150306155344</timestamp>
			  <mac>0CFD929D26A46514ACB3325689841DD7</mac>
			  <bizseq>2015030637222</bizseq>
			  <rspcode>0000</rspcode>
			  <rspmsg></rspmsg>
			  <amount>33600</amount>
			  <content>####</content>
			</VSPPEXRsp>';
	}
	public function payConfirm($xml=''){
	    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<VSPPEXRsp>
			  <trxcod>A0000002</trxcod>
			  <payinst></payinst>
			  <entinst>020440654110063</entinst>
			  <timestamp>20150306155415</timestamp>
			  <mac>FADCEDA60E3D136DC13088753D4F10C7</mac>
			  <bizseq>2015030637222</bizseq>
			  <rspcode>0000</rspcode>
			  <rspmsg></rspmsg>
			</VSPPEXRsp>';
	}
}
?>