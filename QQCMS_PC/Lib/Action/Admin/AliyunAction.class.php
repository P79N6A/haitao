<?php
/**
 * aliyun
 * @author knight (171708252@qq.com)
 * @date    2015-09-16 10:33:55
 * @version 1.0
 */
if(defined('APP_NAME')!='QQCMS' && !defined("QQCMS"))  exit("Access Denied");
class AliyunAction extends Action {

  protected $bucket,$oss;
  function _initialize(){
    import ('@.ORG.Sampleutil');
    //初始化
    $this->bucket = Sampleutil::get_bucket_name();
    $this->oss = Sampleutil::get_oss_client();
    Sampleutil::create_bucket();
  }
  
  /**
   *创建模拟文件夹
   *OSS服务是没有文件夹这个概念的，所有元素都是以Object来存储。但给用户提供了创建模拟文件夹的方式
   */
  public function createObjectDir(){
      $object = "2015";
      $res = $this->oss->create_object_dir($this->bucket, $object);
      $msg = "创建模拟文件夹 /" . $this->bucket . "/" . $object;
      OSSUtil::print_res($res, $msg);
  }

  /**
 *简单上传
 *上传指定的本地文件内容
 */
  public function upload_file(){
      $this->createObjectDir();
      $object='2015/test.jpg';
      $file_path = $_FILES['files']['tmp_name'];
      // var_dump(__FILE__);exit;
      $dir_path=dirname(dirname(dirname(__FILE__))).'/Oss/group/';
      // $file_path = $dir_path.'12.jpg';
      // var_dump(dirname(dirname(dirname(__FILE__))));exit;
      $options = array();
      $res = $this->oss->upload_file_by_file($this->bucket, $object, $file_path, $options);
      var_dump($res);
      $msg = "上传本地文件 :" . $file_path . " 到 /" . $this->bucket . "/" . $this->bucket;
      OSSUtil::print_res($res, $msg);
  }


  /**
   * 文件上传demo
   */
  public function index(){
    $this->display();
  }

  /**
   * 标准测试上传表单
   */
  public function getfile(){
    $info = $this->upload();
    if ($info) {
      $this->success('success添加文件信息成功');
    }else{
      $this->error('error添加文件信息失败');
    }
  }

  /**
   * 标准js测试上传
   */
  public function getjsfile(){
    $info = $this->upload();
    if ($info) {
      echo json_encode($info);
    }else{
      echo json_encode(array('error'=>'文件添加失败'));
    }
  }

  /**
   * 提供调用的公共上传
   * @return array 文件信息
   */
/*  public function upload(){
    $setting=C('UPLOAD_SITEIMG_OSS');
    $Upload = new \Think\Upload($setting);
    $info = $Upload->upload($_FILES);
    if (!$info) {
      return false;
    }else{
      return $info['file'];
    }
  }*/


// $info 具体信息
//   ["file"] => array(9) {
//     ["name"] => string(23) "t0154de56066574d856.jpg"
//     ["type"] => string(10) "image/jpeg"
//     ["size"] => int(37218)
//     ["key"] => string(4) "file"
//     ["ext"] => string(3) "jpg"
//     ["md5"] => string(32) "2ea392a4519e3ea7792b4628c5ba50e7"
//     ["sha1"] => string(40) "bae95b1faada8be51caaa3fcf52c8b5632faadb5"
//     ["savename"] => string(17) "53c4a7b96c193.jpg"
//     ["savepath"] => string(18) "aliyun/2014-07-15/"
//   }


}