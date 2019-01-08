<?php
require_once 'sample_base.php';
//初始化
$bucket = SampleUtil::get_bucket_name();
$oss = SampleUtil::get_oss_client();
SampleUtil::create_bucket();

/**
 *创建模拟文件夹
 *OSS服务是没有文件夹这个概念的，所有元素都是以Object来存储。但给用户提供了创建模拟文件夹的方式
 */
$object = "2015";
$res = $oss->create_object_dir($bucket, $object);
$msg = "创建模拟文件夹 /" . $bucket . "/" . $object;
OSSUtil::print_res($res, $msg);

/**
 *简单上传
 *上传指定的本地文件内容
 */
// $file_path = __FILE__;
$object='2015/test.jpg';
$file_path = 'D:\group\12.jpg';
var_dump($file_path);
$options = array();
$res = $oss->upload_file_by_file($bucket, $object, $file_path, $options);
var_dump($res);
$msg = "上传本地文件 :" . $file_path . " 到 /" . $bucket . "/" . $object;
OSSUtil::print_res($res, $msg);