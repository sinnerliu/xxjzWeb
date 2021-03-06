<?php
namespace Home\Controller;
use Think\Controller;
class ApiController extends Controller {

    //登录api
    public function login(){
        if(IS_POST){
            $username = I('post.username','','htmlspecialchars');
            $password = I('post.password','','htmlspecialchars');
            $submit   = I('post.submit','','htmlspecialchars');
        }else{
            $username = I('get.username','','htmlspecialchars');
            $password = I('get.password','','htmlspecialchars');
            $submit   = I('get.submit','','htmlspecialchars');
        }

        session(null); //清空session

        if(UserLogin($username,$password)){
            session('submit',$submit);
            ClearAllCache(); //清除缓存
            $arrData = array('uid'=>session('uid'),'uname'=>session('username'));
        }elseif(intval(S('login_times_'.$username)) > C('USER_LOGIN_TIMES')){
            $arrData = array('uid'=>'0','uname'=>'你的账号已被锁定，请联系管理员解锁！');
        }else{
            $arrData = array('uid'=>'0','uname'=>'用户名或密码错误！');
        }
        die(json_encode($arrData));
    }

    //用户信息Api
    public function user() {
        if (IS_POST) {
            $uid = I('post.uid',0,'intval');
        } else {
            $uid = I('get.uid',0,'intval');
        }
        $arrData = array();
        if ($uid == session('uid')){
            $arrData['uid'] = $uid;
            $arrData['username'] = session('username');
            $arrData['email'] = GetUserEmail($uid,true);
        } else {
            $arrData['uid'] = 0;
        }

        die(json_encode($arrData));
    }

    //获取统计Api
    public function statistic() {
        if (IS_POST) {
            $type = I('post.type','all');
        } else {
            $type = I('get.type','all');
        }
        $uid = session('uid');
        $arrData = array();
        if ($uid > 0) {
            if ($type == "retime") {
                ClearAllCache(); //清除缓存
            }
            $arrData['uid'] = $uid;
            $arrData['data'] = AccountStatisticProcess($uid);
        } else {
            $arrData['uid'] = 0;
            $arrData['data'] = "用户未登录，请重新登录！";
        }
        die(json_encode($arrData));
    }

    //分类Api
    public function aclass() {
        if (IS_POST) {
            $type = I('post.type','get');
            $data = json_decode(base64_decode(I('post.data',null)),true);
        } else {
            $type = I('get.type','get');
            $data = json_decode(base64_decode(I('get.data',null)),true);
        }

        $arrData = array();
        $uid = session('uid');
        if ($uid > 0) {
            $arrData['uid'] = $uid;
        } else {
            $arrData['uid'] = 0;
            $arrData['data'] = "用户未登录，请重新登录！";
            die(json_encode($arrData));
        }

        switch ($type) {
            case 'get':
                $arrData['data']['in']  = GetClassData($uid, 1);
                $arrData['data']['out'] = GetClassData($uid, 2);
                $arrData['data']['all'] = GetClassData($uid);
                break;
            
            case 'getin':
                $arrData['data'] = GetClassData($uid, 1);
                break;
            
            case 'getout':
                $arrData['data'] = GetClassData($uid, 2);
                break;

            case 'getall':
                $arrData['data'] = GetClassData($uid);
                break;

            case 'addall':
                $arrData['data'] = FastAddNewClass($data, $uid);
                break;
            
            case 'add':
                $data['ufid'] = $uid;
                $arrData['data'] = AddNewClass($data);
                break;

            case 'edit':
                $arrData['data'] = editClassName($data['classname'], $data['classid'], $data['classtype'], $uid);
                break;

            case 'change':
                $arrData['data'] = ChangeClassType($data['classid'], $uid);
                break;

            case 'del':
                $arrData['data'] = GetClassIdData($data['classid'], $uid);
                break;

            case 'move':
                $arrData['data'] = MoveClassAccount($data['classid1'], $data['classid2'], $uid);
                break;

            default:
                $arrData['data'] = '非法操作！';
                break;
        }
        die(json_encode($arrData));
    }

    //记账数据Api
    public function account()
    {
        if (IS_POST) {
            $type = I('post.type','get');
            $data = json_decode(base64_decode(I('post.data',null)),true);
        } else {
            $type = I('get.type','get');
            $data = json_decode(base64_decode(I('get.data',null)),true);
        }

        $arrData = array();
        $uid = session('uid');
        if ($uid > 0) {
            $arrData['uid'] = $uid;
        } else {
            $arrData['uid'] = 0;
            $arrData['data'] = "用户未登录，请重新登录！";
            die(json_encode($arrData));
        }

        switch ($type) {
            case 'get':
                $arrData['data'] = GetDateAccountData($uid, $data); // 获取记账数据
                break;

            case 'get_year':
                $arrData['data'] = json_decode(getYearData($data['year'], $uid), true); // 获取年度统计
                break;

            case 'get_all_year':
                $arrData['data'] = json_decode(getAllYearData($uid), true); // 获取历年统计
                break;

            case 'get_id':
                $arrData['data'] = NumTimeToStrTime(GetIdData($data['acid']));
                if ($data['jiid'] != $uid) {
                    $arrData['uid'] = 0;
                    $arrData['data'] = '用户验证未通过，请重新登录！';
                }
                break;
            
            case 'add':
                $data['jiid'] = $uid;
                $ret = AddAccountData($data);
                $arrData['data']['ret'] = $ret[0];
                $arrData['data']['msg'] = $ret[1];
                ClearDataCache(); //清除缓存
                break;

            case 'edit':
                if (CheakIdShell($data['acid'], $uid)) {
                    $ret = UpdataAccountData($data);
                    $arrData['data']['ret'] = $ret[0];
                    $arrData['data']['msg'] = $ret[1];
                    ClearDataCache(); //清除缓存
                } else {
                    $arrData['data']['ret'] = false;
                    $arrData['data']['msg'] = '未通过合法性验证！';
                }
                break;

            case 'del':
                if (CheakIdShell($data['acid'], $uid)) {
                    $ret = DelIdData($data['acid']);
                    $arrData['data']['ret'] = $ret[0];
                    $arrData['data']['msg'] = $ret[1];
                    ClearDataCache(); //清除缓存
                } else {
                    $arrData['data']['ret'] = false;
                    $arrData['data']['msg'] = '未通过合法性验证！';
                }
                break;

            case 'find':
                if ($data['jiid'] == $uid) {
                    $ret = NumTimeToStrTime(FindAccountData($data, $data['page']));
                    $arrData['data']['ret'] = true;
                    $arrData['data']['msg'] = $ret;
                } else {
                    $arrData['data']['ret'] = false;
                    $arrData['data']['msg'] = '未通过用户验证！';
                }
                break;

            default:
                break;
        }

        die(json_encode($arrData));
    }

    //表格数据api
    public function chart() {
        if (IS_POST) {
            $type = I('post.type','year');
            $date = I('post.date',time(),'intval');
        } else {
            $type = I('get.type','year');
            $date = I('get.date',time(),'intval');
        }
        $uid = session('uid');
        $year = date('Y', $date);
        $month = date('m', $date);
        $day = date('d', $date);
        switch ($type) {
            case 'year':
                die(getYearData($year, $uid));
                break;

            case 'month':
                die(getMonthData($year, $month, $uid));
                break;
            
            default:
                die('非法操作！');
                break;
        }
    }

    public function test()
    {
        $uid = session('uid');
        //C('USER_LOGIN_TIMES', 15);
        dump(json_decode(getMonthData(2016, 1, $uid)));
        // $data['gettype'] = 'day';
        // $data['year'] = 2016;
        // $data['month'] = 08;
        // $data['day'] = 14;
        // $data['p'] = 0;
        // dump(GetDateAccountData($uid, $data));
        // dump(NumTimeToStrTime("1470240000"));
    }

/***************************************************************************************************/
    public function index(){
        //dump(get_client_ip());
        if(IS_POST) {
            if($_POST['login_submit']){
                $username = I('post.login_username','','htmlspecialchars');
                $password = I('post.login_password','','htmlspecialchars');
                if(UserLogin($username, $password)){
                    session('submit',$_POST['login_submit']);
                    session('webAppUser',$username); //webApp参数
                    session('webAppPass',$password); //webApp参数
                    ClearAllCache(); //清除缓存
                    echo '登陆成功';
                    $this -> redirect('Home/Index/index');
                }else if(intval(S('login_times_'.$username)) > C('USER_LOGIN_TIMES')){
                    ShowAlert('你的账号已被锁定，请联系管理员解锁！',U('Home/Login/index'));
                    $this -> display('Public/base');
                }else{
                    ShowAlert('用户名或密码错误！',U('Home/Login/index'));
                    $this -> display('Public/base');
                    // $this -> error('用户名或密码错误！');
                }
            }elseif($_POST['forget_submit']){
        	    //验证Email的正确性
        	    $email = I('post.forget_email','','htmlspecialchars');
            	if ( empty($email)|| !preg_match("/^[-a-zA-Z0-9_.]+@([0-9A-Za-z][0-9A-Za-z-]+\.)+[A-Za-z]{2,5}$/",$email)) {
                    LoginMassage("邮箱格式不正确！","danger");
                    $this -> display();
                    exit;
                } 
                //去除空格
                $email=str_replace(" ","",$email);
                
                $DbUser = M("user") -> where("email='$email'") -> find();
                
                if(is_array($DbUser)){
                    $username = $DbUser['username'];
                    $user_pass = $DbUser['password'];
                    $from = $DbUser['email'];
                    $x = md5($username.'+'.$user_pass);
                    $String = base64_encode($username.".".$x);
                    $StrHtml = U('Home/Login/forget','p='.$String,'',true);
                    //发送邮件
                    $address = $from;
                    $subject = "找回密码 - 小歆记账APP";
                    $body    = "<br>".$username."：<br />请点击下面的链接，按流程进行密码重设。<br><a href=\"".$StrHtml."\">确认密码找回</a><p><pre>".$StrHtml."</pre></br>";   
                    $file    = null;
                    if (!SendMail($address,$subject,$body,$file)) {
                        LoginMassage("服务器出错，请稍后再试！","danger");
                    }else{ 
                        LoginMassage("找回密码的链接已发送至您的邮箱，请查收！");
                    }
                }else{
                    LoginMassage("该邮箱未注册过账号！","danger");
                }
                $this -> display();
            }else{
                LoginMassage("非法操作！","danger");
                $this -> display();
            }
        }elseif(UserShell(session('username'),session('user_shell'))){
            $this -> redirect(Home/Index/index);
        }else{
            //向webApp发送用户名
            if(session('webAppUser')){
                // $str = "'".session('webAppUser')."'";
                // echo '<body href="javascript:void(0);" onload="WebApp_Logout('.$str.')"></body>';
                $str = "'"."web_logout"."','".session('webAppUser')."',''";
                echo '<body href="javascript:void(0);" onload="WebApp_Login('.$str.')"></body>';
                session('webAppUser',null);
            }
            $this -> display();
        }
    }
    
    
    public function forget(){
        //用base64_decode解开$_GET['p']的值
        $array = explode('.',base64_decode($_GET['p']));
        // * $array[0] 为用户名
        // * $array[1] 为我们生成的字符串
        $username = trim($array['0']);
        $StrUser = "username='$username'";
        $DbUser = M("user");  //实例化jizhang_user
        $password = $DbUser -> where($StrUser)->getField('password');
        //产生配置码 
        $checkCode = md5($array['0'].'+'.$password);
        //进行配置验证
        if( $array['1'] === $checkCode ){
            if($_POST["forget_submit"]){   
                $username = trim($array['0']);
                $password = trim($_POST["forget_password"]);
                $StrUser = "username='$username'";
                //$row = $DbUser->where($StrUser)->find();
                if($password <> ""){
                    $umima=md5($password);
                    $DbUser-> where($StrUser)->setField('password',$umima);
                    // $this -> success('OK，修改成功！马上为你跳转登录页面...', U('/Home/Login/index'), 2);
                    ShowAlert('OK，修改成功！',U('/Home/Login/index'));
                    $this -> display('Public/base');
                }else{
                    $this -> error('密码格式错误！');
                }
            }else{
                //执行重置程序，一般给出三个输入框。
                $this -> assign('username',$username);
                $this -> display();
            }
        }else{
            $this -> error('非法操作！', U('/Home/Login/index'));
        }
    }
    
    public function regist(){
        $this -> display();
    }
    
    public function logout(){
        //header('Content-type:text/html;charset=utf-8');
        $UserName = session('username');
        ClearAllCache(); //清除缓存
        session(null);
        if($UserName){
            session('webAppUser',$UserName);
        }
        $this -> redirect('Home/Login/index');
    }
    
}