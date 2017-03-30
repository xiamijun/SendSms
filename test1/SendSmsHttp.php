<?php

/**
 * Created by PhpStorm.
 * User: XJ
 * Date: 2017/3/30
 * Time: 12:02
 */
class SendSmsHttp{
    private $_apiurl='http://gd.ums86.com:8899/sms/Api/Send.do';
    public $SpCode;     //企业编号
    public $LoginName;  //用户名称
    public $Password;   //用户密码
    public $MessageContent; //短信内容, 最大700个字符
    public $UserNumber; //手机号码(多个号码用”,”分隔)，最多1000个号码
    public $SerialNumber;   //流水号，20位数字，唯一
    public $ScheduleTime;   //预约发送时间，格式:yyyyMMddhhmmss,如‘20090901010101’，立即发送请填空
    public $ExtendAccessNum;
    public $f;  //提交时检测方式
    public $errorMsg;

    /**
     * 发送短信
     * @return bool
     */
    public function send(){
        $params=array(
            'SpCode'=>$this->SpCode,
            'LoginName'=>$this->LoginName,
            'Password'=>$this->Password,
            'MessageContent'=>$this->MessageContent,
            'UserNumber'=>$this->UserNumber,
            'SerialNumber'=>$this->SerialNumber,
            'ScheduleTime'=>$this->ScheduleTime,
            'ExtendAccessNum'=>$this->ExtendAccessNum,
            'f'=>$this->f,
        );
        $data=http_build_query($params);
        $res=iconv('GB2312','UTF-8//IGNORE',$this->_httpClient($data));
        $resArr=array();
        parse_str($res,$resArr);
        if (!empty($resArr)&&$resArr['result']==0){
            return true;
        }else{
            if (empty($this->errorMsg)){
                $this->errorMsg=isset($resArr['description'])?$resArr['description']:'未知错误';
            }
            return false;
        }
    }

    /**
     * POST方式访问接口
     * @param $data
     * @return bool|mixed
     */
    private function _httpClient($data){
        try{
            $ch=curl_init();
            curl_setopt($ch,CURLOPT_URL,$this->_apiurl);
            curl_setopt($ch,CURLOPT_HEADER,0);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
            $res=curl_exec($ch);
            cubrid_close($ch);
            return $res;
        }catch (Exception $e){
            $this->errorMsg=$e->getMessage();
            return false;
        }
    }
}

$sendSms=new SendSmsHttp();

//手机验证码ajax页面
if ($_POST['act']=='telcode'){
    //验证验证码
    $code=trim($_POST['code']);
    if ($code!=$_SESSION['login_check_num']){
        echo 'codeHad';
    }else{
        //手机号
        $tel=trim($_POST['tel']);

        //生产手机验证码
        $code='';
        for ($i=0;$i<4;$i++){
            $code.=rand(0,9);
        }

        //流水号
        list($usec,$sec)=explode(' ',microtime());
        $time=(float)$usec+(float)$sec;

        //发送验证码
        $sendSms->SpCode='';
        $sendSms->LoginName='';
        $sendSms->Password='';
        $sendSms->MessageContent='您的验证码为'.$code;
        $sendSms->UserNumber=$tel;
        $sendSms->SerialNumber=$time;
        $sendSms->ScheduleTime='';  //空为立即发送
        $sendSms->ExtendAccessNum='';    //检测方式参数
        $res=$sendSms->send();
        //如果发送成功写入数据库
        if ($res){
            $sql="INSERT INTO telcode SET tel='$tel',code='$dode',time='$time'";
//            mysqli_query($conn,$sql);
            echo 'success';
        }else{
            echo 'smsHad';
        }
        return ;
    }
}