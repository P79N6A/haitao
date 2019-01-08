<?php
/**
 * aliyun
 * @author knight (171708252@qq.com)
 * @date    2015-09-16 10:33:55
 * @version 1.0
 */
class Aliyun extends Think {

  protected $bucket,$oss,$endpoint,$name,$tmp_name,$dir_name,$object_name;
  function __construct($dir_name='',$object_name='',$tmp_name){
    import ('@.ORG.Sampleutil');
    //初始化
    $this->dir_name=$dir_name;
    $this->object_name=$object_name;
    $this->tmp_name=$tmp_name;
    $this->bucket = Sampleutil::get_bucket_name();
    $this->endpoint = Sampleutil::get_bucket_endpoint();
    $this->oss = Sampleutil::get_oss_client();
    Sampleutil::create_bucket();
  }
  
  /**
   *创建模拟文件夹
   *OSS服务是没有文件夹这个概念的，所有元素都是以Object来存储。但给用户提供了创建模拟文件夹的方式
   */
  public function createObjectDir(){
      $res = $this->oss->create_object_dir($this->bucket, $this->dir_name);
      /*$msg = "创建模拟文件夹 /" . $this->bucket . "/" . $object;
      OSSUtil::print_res($res, $msg);*/
  }

  /**
 *简单上传
 *上传指定的本地文件内容
 */
  public function upload_file(){
      $this->createObjectDir();
      $options = array();
      $res = $this->oss->upload_file_by_file($this->bucket, $this->object_name, $this->tmp_name, $options);
      return $res;
      /*var_dump($res);
      $msg = "上传本地文件 :" . $file_path . " 到 /" . $this->bucket . "/" . $this->bucket;
      OSSUtil::print_res($res, $msg);*/
  }
}