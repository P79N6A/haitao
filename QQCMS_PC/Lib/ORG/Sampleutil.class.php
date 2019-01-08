<?php
require_once APP_PATH.'Lib/Oss/sdk.class.php';
require_once APP_PATH.'Lib/Oss/util/oss_util.class.php';
class Sampleutil extends Think {
	/**/
    const endpoint = OSS_ENDPOINT;
    const accessKeyId = OSS_ACCESS_ID;
    const accesKeySecret = OSS_ACCESS_KEY;
    const bucket = OSS_TEST_BUCKET;
    public static function get_oss_client() {
        $oss = new ALIOSS(self::accessKeyId, self::accesKeySecret, self::endpoint);
        return $oss;
    }

    public static function my_echo($msg) {
        $new_line = " \n";
        /*echo $msg . $new_line;*/
    }

    public static function get_bucket_name() {
        return self::bucket;
    }

    public static function create_bucket() {
        $oss = self::get_oss_client();
        $bucket = self::get_bucket_name();
        $acl = ALIOSS::OSS_ACL_TYPE_PUBLIC_READ;
        $res = $oss->create_bucket($bucket, $acl);
        /*$msg = "创建bucket " . $bucket;
        OSSUtil::print_res($res, $msg);*/
    }

    public static function get_bucket_endpoint() {
        return self::endpoint;
    }
}