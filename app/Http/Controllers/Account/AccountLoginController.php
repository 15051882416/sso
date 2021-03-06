<?php


namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Jwt\JwtController;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AccountLoginController extends Controller {

    /**
     * 用户注册：
     * 用户在注册账号时，首先需要保证账号唯一
     * 然后需要将用户的明文密码进行加密，这里是通过 bcrypt 函数加密
     * 通过该函数加密后，需要通过 Hash::check 方法经行验证（在登录接口里有写）
     * 密码加密后，将密文密码存入数据库中
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {

        $param = $request->only(['account','password']);

        $validator = Validator::make($param,  [
            'account'       => 'required|alpha_num',
            'password'      => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return $this->_apiExit(40002,'',key($errors) . ' 参数验证错误');
        }

        $account = $param['account'];
        $password = $param['password'];

        $userInfo = User::where('account', $account)->first();
        if ($userInfo) {
            return $this->_apiExit(40901);
        }

        // 密码加密
        $dbPassword = bcrypt($password);

        $data = [
            'account' => $account,
            'password' => $dbPassword
        ];

        try {
            User::create($data);
            return $this->_apiExit(200);
        } catch (\Exception $e) {
            Log::error("账号注册失败原因:" . $e->getMessage());
            return $this->_apiExit(50001);
        }

    }

    /**
     * 账号密码登录：
     * 在用户账号注册的时候，需要将用户的密码进行加密存入数据库
     * 一定不能将密码明文存入数据库
     * 然后再用户登录的时候，验证用户输入的账号密码与数据库中的是否相同
     * 相同则返回用户信息和 token
     * 这里我用的密码验证是 php 自带的验证方式 Hash::check
     * 因此再用户注册的时候，存入到数据中的密码必须使用 php 自带的 bcrypt 加密方法
     * 该加密方法和验证方法原理后期研究一下
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request) {

        $param = $request->only(['account','password']);

        $validator = Validator::make($param,  [
            'account'       => 'required|alpha_num',
            'password'      => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return $this->_apiExit(40002,'',key($errors) . ' 参数验证错误');
        }

        $account = $param['account'];
        $password = $param['password'];

        $userInfo = User::where('account', $account)->first();

        if (!$userInfo || !Hash::check($password, $userInfo->password)) {
            return $this->_apiExit(40401);
        }

        $data['userInfo'] = $userInfo;
        $data['token'] = JwtController::encrypt($userInfo);

        return $this->_apiExit(200, $data);
    }


}
