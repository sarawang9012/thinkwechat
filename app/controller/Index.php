<?php
namespace app\controller;

use think\facade\Log;
use app\BaseController;
use app\library\ThinkWechat;
use think\facade\Request;
use think\facade\Session;

class Index extends BaseController
{


//操作步骤定义
    const STEP_REGISTER_USERNAME = 1;
    const STEP_REGISTER_PASSWORD = 2;
    const STEP_LOGIN_USERNAME = 3;
    const STEP_LOGIN_PASSWORD = 4;
    const STEP_AVATAR_UPLOAD = 5;

//游客操作
    const GUEST_ACTION_REGISTER = 1;
    const GUEST_ACTION_LOGIN = 2;
//用户操作
    const USER_ACTION_INFO = 1;
    const USER_ACTION_AVATAR = 2;
    const USER_ACTION_LOGOUT = 3;
//全局操作
    const GLOBAL_ACTION_RESET = 999;
//自定义菜单
    const MENU_MAIN_3 = 'MENU_MAIN_3';
    const MENU_MAIN_1_CHILD_1 = 'MENU_MAIN_1_CHILD_1';
    const MENU_MAIN_2_CHILD_1 = 'MENU_MAIN_2_CHILD_1';
    private $uc;


    public function index()
    {
        Log::write('index _get session id before set ID ' . input('openid'), 'debug');
        Log::write('index _get session id before set ID ' . Session::getId(), 'debug');

        $ip = Request::host();
        Log::write('client ip is ' . $ip, 'debug');

        $wechat = new ThinkWechat(config('app.WECHAT.TOKEN'));
        $this->uc = new uc();

        $data = $wechat->request();

        list($content, $type) = $this->_handle($data);
        return $wechat->response($content, $type);

    }

    private function _handle(array $data)
    {
        if ($data['MsgType'] == 'text') {

            return $this->_handleText($data);
        }
        if ($data['MsgType'] == 'image') {
            return $this->_handleImage($data);
        }
        if ($data['MsgType'] == 'event') {
            return $this->_handleEvent($data);
        }
        return array('你好', 'text');

    }

    /**
     * 是否游客
     * @return bool
     */
    private function _isGuest()
    {

        if (Session::get('login') === null) {
            return true;
        } else {
            return false;
        }
    }

    private function _login()
    {
        Session::set('login', 1);
        //Session::save();
    }

    private function _logout()
    {
        Session::set('login', null);
    }

    /**
     * 当前操作步骤
     * @return int
     */
    private function _currentStep()
    {


        return Session::get('step');
    }

    /**
     * 重置步骤
     */
    private function _resetStep()
    {
        Session::set('step', null);
    }

    /**
     * 设置操作步骤
     * @param $step
     */
    private function _setStep($step)
    {

        Session::set('step', $step);
        //Session::save();
    }

    /**
     * 重置会话
     */
    private function _resetSession()
    {
        Session::clear();
        //Session::set('step', $step);
    }

    /**
     * 游客操作
     */
    private function _guestActions()
    {
        return array(
            '您当前的身份是【游客】',
            '您可以进行以下操作：',
            '1.注册账号',
            '2.登录账号',
            '帮助:任何情况下回复999重置会话状态'
        );
    }

    /**
     * 用户操作
     * @return array
     */
    private function _userActions()
    {
        return array(
            '您当前的身份是【登录用户】',
            '您可以进行以下操作',
            '1.个人信息',
            '2.上传头像',
            '3.退出登录',
            '帮助:任何情况下回复999重置会话状态'
        );
    }

    /**
     * 全局操作
     * @param array $data
     * @return array|bool
     */
    private function _handleGlobalAction(array $data)
    {
        if ($data['Content'] == self::GLOBAL_ACTION_RESET) {
            $this->_resetSession();
            Log::write('_handleGlobalAction after reset session', 'debug');
            return array(join("\n", array_merge(array('重置成功'), $this->_guestActions())), 'text');
        }
        return false;
    }

    /**
     * 处理文本信息
     * @param array $data
     * @return array|bool
     */
    private function _handleText(array $data)
    {
        $result = $this->_handleGlobalAction($data);

        //如果返回非false，证明当前操作已经被处理完成
        if ($result !== false) {
            return $result;
        }
        //游客
        if ($this->_isGuest()) {
            //没有选择任何步骤

            if (!$this->_currentStep()) {
                if ($data['Content'] == self::GUEST_ACTION_REGISTER) {
                    $this->_setStep(self::STEP_REGISTER_USERNAME);

                    Log::write('before register current step is  ' . $this->_currentStep(), 'debug');
                    return array(
                        '【Register】请输入您的用户名',
                        'text'
                    );

                }
                if ($data['Content'] == self::GUEST_ACTION_LOGIN) {
                    $this->_setStep(self::STEP_LOGIN_USERNAME);
                    return array(
                        '【登录】请输入您的用户名',
                        'text'
                    );
                }
            }
            //注册->输入用户名
            Log::write('current ID is  ' . Session::getId(), 'debug');
            Log::write('current step is  ' . Session::get('step'), 'debug');

            if ($this->_currentStep() == self::STEP_REGISTER_USERNAME){
                Log::write('input username is ' . $data['Content'], 'debug');
                $username = $data['Content'];
                $result = $this->uc->uc_check_name($username);
                switch ($result) {
                    case -1:
                        $reason = "用户名不合法";
                        break;
                    case -2:
                        $reason = "包含不允许注册的词语";
                        break;
                    case -3:
                        $reason = "用户名已经存在";
                        break;
                }
                if ($result != 1 ){
                    $this->_resetStep();
                    return array(join("\n", array_merge(array('【注册】注册失败', $reason), $this->_guestActions()
                    )),
                        'text');
                } else {
                    $this->_setStep(self::STEP_REGISTER_PASSWORD);
                    Session::set('username', $data['Content']);

                    return array('【注册】请输入密码', 'text');
                }
            }


            //注册->输入密码
            if ($this->_currentStep() == self::STEP_REGISTER_PASSWORD) {
                $this->_resetStep();
                Session::set('password', $data['Content']);
                //call ucenter to register user
                $username_valid = $this->uc->uc_check_name(Session::get('username'));
                Log::write('$username_valid' . $username_valid, 'debug');
                if ($username_valid == 1) {
                    //register
                    Log::write('start register ' . Session::get('username'), 'debug');
                    $email = "reg_" . substr(Session::getId(), 0, 3) . time() . substr(Session::getId(), 7, 4) . "@null.com";
                    //$email = Session::get('username').'@'.Session::get('username').'.com';
                    $register_result = $this->uc->uc_register(Session::get('username'), Session::get('password'),
                        $email);
                    Log::write('register result is ' . $register_result, 'debug');
                    switch ($register_result) {
                        case -1:
                            $reason = "用户名不合法";
                            break;
                        case -2:
                            $reason = "包含不允许注册的词语";
                            break;
                        case -3:
                            $reason = "用户名已经存在";
                            break;
                        case -4:
                            $reason = "Email格式有误";
                            break;
                        case -5:
                            $reason = "Email不允许注册";
                            break;
                        case -6:
                            $reason = "该Email已经被注册";
                            break;
                    }
                    if ($register_result > 0) {
                        Log::write('Ucenter register successful' . Session::get('username'), 'debug');
                        return array(join("\n", array_merge(array('【注册】注册成功'), $this->_guestActions())), 'text');
                    } else {
                        Log::write('Ucenter register failed' . $reason, 'debug');
                        $this->_resetSession();
                        return array(join("\n", array_merge(array('【注册】注册失败', $reason), $this->_guestActions()
                        )),
                            'text');
                    }
                }

            }
                    //登录->输入用户名
                    if ($this->_currentStep() == self::STEP_LOGIN_USERNAME) {
                        //check username exists
                        $result = $this->uc->uc_check_name($data['Content']);
                        Log::write('Ucenter uc_check_name while login' . $result, 'debug');
                        if ($result != -3 ) {
                            $results = array(
                                join("\n", array(
                                    "【登录】用户名错误",
                                    "回复用户名继续操作",
                                    "回复999重新开始会话"
                                )),
                                'text'
                            );
                            return $results;
                        } else {
                            Session::set('username', $data['Content']);
                        }
                        $this->_setStep(self::STEP_LOGIN_PASSWORD);
                        return array('【登录】请输入密码', 'text');
                    }
                    //登录->输入密码
                    if ($this->_currentStep() == self::STEP_LOGIN_PASSWORD) {
                        //check password in ucenter db
                        $result = $this->uc->uc_login(Session::get('username'), $data['Content']);
                        Log::write('Ucenter login successful, username is ' .json_encode($result),
                            'debug');
                        if ($result < 0) {
                            return array(
                                join("\n", array(
                                    "【登录】密码错误",
                                    "回复密码继续操作",
                                    "回复999重新开始会话"
                                )),
                                'text'
                            );
                        } else {
                            Session::set('password', $data['Content']);
                        }

                        $this->_login();
                        $this->_resetStep();
                        return array(join("\n", array_merge(array('【登录】登录成功'), $this->_userActions())), 'text');
                    }
                    return array(join("\n", $this->_guestActions()), 'text');
            } else {
            if (!$this->_currentStep()) {
                if ($data['Content'] == self::USER_ACTION_INFO) {
                    return array(
                        join("\n", array(
                            '用户名:' . Session::get('username'),
                            '密码:' . Session::get('password'),
                            '头像:' . (Session::get('avatar') ? Session::get('avatar') : '未设置')
                        )),
                        'text'
                    );
                }
                if ($data['Content'] == self::USER_ACTION_AVATAR) {
                    $this->_setStep(self::STEP_AVATAR_UPLOAD);
                    return array(
                        '【头像】请上传一张头像',
                        'text'
                    );
                }
                if ($data['Content'] == self::USER_ACTION_LOGOUT) {
                    $this->_logout();
                    $this->_resetStep();
                    return array(join("\n", array_merge(array('退出登录成功'), $this->_guestActions())), 'text');
                }
            }
            if ($this->_currentStep() == self::STEP_AVATAR_UPLOAD) {
                return array(
                    '【头像】操作有误!请上传图片头像',
                    'text'
                );
            }
            return array(join("\n", $this->_userActions()), 'text');
        }
    }

    /**
     * 处理图片消息
     * @param array $data
     * @return array
     */
    private
    function _handleImage(array $data)
    {
        if ($this->_currentStep() != self::STEP_AVATAR_UPLOAD) {
            $messages = array('操作有误');
            if ($this->_isGuest()) {
                $messages = array_merge($messages, $this->_guestActions());
            } else {
                $messages = array_merge($messages, $this->_userActions());
            }
            return array(join("\n", $messages), 'text');
        }
        session('avatar', $data['PicUrl']);
        $this->_resetStep();
        return array(join("\n", array_merge(array('【头像】上传成功'), $this->_userActions())), 'text');
    }

    /**
     * 处理事件
     * @param array $data
     * @return array
     */
    private
    function _handleEvent(array $data)
    {
        if ($data['Event'] == 'subscribe') {
            return array(join("\n", array_merge(array('欢迎关注！'), $this->_guestActions())), 'text');
        }
        if ($data['Event'] == 'CLICK') {
            return $this->_handleMenuClick($data['EventKey']);
        }
        return array('', 'text');
    }

    /**
     * 处理自定义菜单点击
     * @param $key
     * @return array
     */
    private
    function _handleMenuClick($key)
    {
        switch ($key) {
            case self::MENU_MAIN_3:
                return array('您点击了主菜单3', 'text');
            case self::MENU_MAIN_1_CHILD_1:
                return array('您点击了 主菜单1->子菜单1', 'text');
            case self::MENU_MAIN_2_CHILD_1:
                return array('您点击了 主菜单2->子菜单1', 'text');
            default:
                return array('', 'text');
        }
    }

    /**
     * 创建自定义菜单
     */
    public
    function menu()
    {
        require __DIR__ . '/../../vendor/autoload.php';
        $data = array(
            'button' => array(
                array(
                    'type' => 'click',
                    'name' => '主菜单1',
                    'sub_button' => array(
                        array(
                            'type' => 'click',
                            'name' => '子菜单1',
                            'key' => self::MENU_MAIN_1_CHILD_1
                        ),
                        array(
                            'type' => 'view',
                            'name' => '百度一下',
                            'url' => 'https://www.baidu.com'
                        )
                    )
                ),
                array(
                    'type' => 'click',
                    'name' => '主菜单2',
                    'sub_button' => array(
                        array(
                            'type' => 'click',
                            'name' => '子菜单1',
                            'key' => self::MENU_MAIN_2_CHILD_1
                        ),
                        array(
                            'type' => 'view',
                            'name' => 'QQ',
                            'url' => 'http://www.qq.com'
                        )
                    )
                ),
                array(
                    'type' => 'click',
                    'name' => '主菜单3',
                    'key' => self::MENU_MAIN_3
                )
            )
        );

        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->_getAccessToken();

        $resp = Requests::post($url, array(), json_encode($data, JSON_UNESCAPED_UNICODE));
        if ($resp->status_code != 200) {
            return null;
        }
        echo $resp->body;
    }

    /**
     * 读取AccessToken
     */
    private
    function _getAccessToken()
    {
        $cacheKey = config('app.WECHAT.APPID') . 'accessToken';
        $data = session($cacheKey);
        if (!empty($data)) {
            return $data;
        }
        require __DIR__ . '/../../vendor/autoload.php';
        $url = 'https://api.weixin.qq.com/cgi-bin/token?';
        $params = array(
            'grant_type' => 'client_credential',
            'appid' => config('app.WECHAT.APPID'),
            'secret' => config('app.WECHAT.SECRET')
        );

        $resp = Request::get($url . http_build_query($params));
        if ($resp->status_code != 200) {
            return null;
        }
        $data = json_decode($resp->body, true);
        if (isset($data['errcode']) && $data['errcode'] != 0) {
            throw new \Exception($data['errmsg'], $data['errcode']);
        }
        session($cacheKey, $data['access_token'], 7000);
        return $data['access_token'];
    }
}

