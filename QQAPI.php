<?php
	class QQAPI{
        private $code;  //授权码
        private $redirect_url; //重定向地址
        private $appid;
        private $app_secret;
        private $access_token;  
        private $refresh_token; //续期access_token的refresh_token
        private $expire_in; //access_token过期时间
        private $data;    //最终的用户信息

        /**
         * 构造函数
         * 通过构造函数传递appid、app_secret、处理信息的url
         * @param  string $appid 
         * @param  string $app_secret
         * @param  string $url  
         */
        public function __construct($appid,$app_secret,$url){
                $this->code=$_GET['code'];
                $this->appid=$appid;
                $this->app_secret=$app_secret;
                $this->redirect_url=urlencode($url);



        }


        /**
         * 依次执行，返回最终对应用户的数据
         */
        public function returnData(){
                if(isset($this->code)){
                        $this->get_AccessToken();
                        $this->data=$this->get_openid();
                        return $this->data;
                }else{
                        exit('失败');
                }
        }


        /**
         * 使用curl扩展发送get请求
         * @param string $url 请求地址
         */
        private  function curl_get($url){
                $ch=curl_init($url);
                curl_setopt($ch,CURLOPT_HEADER,0);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                $data=curl_exec($ch);
                curl_close($ch);
                return $data; 
        }


        /**
         * 根据授权码code 获取access_token
         */
        private function get_AccessToken(){
                $url='https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id='.$this->appid.
                '&client_secret='.$this->app_secret.'&code='.$this->code.'&redirect_uri='.$this->redirect_url;
                 $receive=$this->curl_get($url);
                 $data=explode('&',$receive);
                
                 if(count($data)==3){
                        $this->access_token=$data[0];
                        $this->expires_in=$data[1];
                        $this->refresh_token=$data[2];
                 }else{
                         exit('登录失败');
                 }
        }

        /**
         * 根据access_token获取openid
         */
        private function get_openid(){
                if(isset($this->access_token)){
                    $url="https://graph.qq.com/oauth2.0/me?".$this->access_token;
                    $receive=$this->curl_get($url);
                    //如果接受的数据包含openid 代表返回成功
                    if(strpos($receive,"openid")){
                        $data_json=substr($receive,9,-3);//对接受到的数据进行处理，只留下其中我们需要的json格式的数据
                        $data=json_decode($data_json,true);//对json数据进行处理
                        $userData=$this->getUserData($data);                       
                        return $userData;

                        
                    }else{ exit('登录失败');}
                }else{
                        exit('登录失败');
                }



        }


        /**
         * 根据openid获取对应用户数据
         * @param Array $data 包含openid和appid的数据
         * 
         */
        private function getUserData($data){
                $getUserUrl='https://graph.qq.com/user/get_user_info?'.$this->access_token.'&oauth_consumer_key='.
                $data["client_id"].'&openid='.$data["openid"];
                $userData_json=$this->curl_get($getUserUrl); //得到json格式的用户数据
                $userData=json_decode($userData_json,true);
                //判断返回的信息是否包含用户信息
                if(isset($userData['nickname'])){
                        return $userData;
                }else{
                        exit('登录失败');
                }


        }



}



