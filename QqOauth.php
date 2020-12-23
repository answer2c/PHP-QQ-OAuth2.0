<?php
	class QqOauth
    {
        private $code;  //授权码
        private $redirect_url; //重定向地址
        private $appid;
        private $app_secret;
        private $access_token;  
        private $refresh_token; //续期access_token的refresh_token
        private $expire_in; //access_token过期时间

        private const REQUEST_BASE_URL = "https://graph.qq.com/oauth2.0";

        /**
         * 构造函数
         * 通过构造函数传递code、appid、app_secret、回调url
         * @param  string $appid 
         * @param  string $app_secret
         * @param  string $url  
         */
        public function __construct($code, $appid, $app_secret, $url)
        {
            $this->code = $code;
            $this->appid = $appid;
            $this->app_secret = $app_secret;
            $this->redirect_url = urlencode($url);
            $this->getAccessToken();
        }

        /**
         * 返回最终对应用户的数据
         * @return mixed
         * @throws Exception
         */
        public function returnData()
        {
            [$client_id, $open_id] = $this->getOpenId();
            return $this->getUserData($this->getUserInfoUrl($client_id, $open_id));
        }


        /**
         * 使用curl扩展发送get请求
         * @param string $url 请求地址
         */
        private function curl_get($url)
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception($error);
            }
            curl_close($ch);
            return $data;
        }


        /**
         * 根据授权码code 获取access_token
         */
        private function getAccessToken()
        {
            $url = $this->getTokenUrl();
            $receive = $this->curl_get($url);
            $data = explode('&', $receive);

            if (count($data) == 3) {
                [$this->access_token, $this->expire_in, $this->refresh_token] = $data;
            } else {
                throw new \Exception('获取access token失败');
            }
        }

        /**
         * 获取openid 以及client_id
         */
        private function getOpenId()
        {
            $url = $this->getOpenIdUrl();
            $receive = $this->curl_get($url);
            //如果接受的数据包含openid 代表返回成功
            if (strpos($receive, "openid")) {
                $dataJson = substr($receive, 9, -3);//对接受到的数据进行处理，只留下其中我们需要的json格式的数据
                $data = json_decode($dataJson, true);//对json数据进行处理
                return [$data['client_id'], $data['open_id']];
            }

            throw new \Exception("获取失败");
        }


        /**
         * 根据openid获取对应用户数据
         * @param string $url 构造好的url
         */
        private function getUserData($url)
        {
            $userDataJson = $this->curl_get($url); //得到json格式的用户数据
            $userData = json_decode($userDataJson, true);
            //判断返回的信息是否包含用户信息
            if (!isset($userData['nickname'])) {
                throw new \Exception("获取信息失败");
            }

            return $userData;
        }

        private function getTokenUrl()
        {
            return self::REQUEST_BASE_URL.'/token?grant_type=authorization_code&client_id=' . $this->appid .
            '&client_secret=' . $this->app_secret . '&code=' . $this->code . '&redirect_uri=' . $this->redirect_url;
        }

        private function getUserInfoUrl($client_id, $open_id)
        {
            return self::REQUEST_BASE_URL. '/user/get_user_info?' . $this->access_token . '&oauth_consumer_key=' . $client_id
                . '&openid=' . $open_id;
        }

        private function getOpenIdUrl()
        {
            return self::REQUEST_BASE_URL . '/me?' . $this->access_token;
        }

}



