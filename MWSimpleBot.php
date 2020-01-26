<?php
    
    /*******************************************************************
     * 
     * MWSimpleBot [ALPHA]
     * MediaWiki Bot class
     * 
     * author     komed3
     * version    0.003
     * date       2020/01/26
     * 
     *******************************************************************/
    
    class MWSimpleBot {
        
        // @var string $endPoint url to api.php
        private $endPoint;
        
        // @var string $botUsername
        public $botUsername;
        
        // @var string $botPassword
        protected $botPassword;
        
        // @var array $loadedModules
        private $loadedModules = [];
        
        // @param string $endPoint
        // @param string $botUsername
        // @param string $botPassword
        function __construct(
            string $endPoint,
            string $botUsername,
            string $botPassword
        ) {
            
            $this->status( 'MWSimpleBot started' );
            
            $this->endPoint = $endPoint;
            
            $this->botUsername = $botUsername;
            
            $this->botPassword = $botPassword;
            
            $this->checkAccess();
            
        }
        
        // @param string $msg status message
        // @return bool true
        private function status(
            string $msg
        ) {
            
            $datetime = new DateTime();
            
            print '[' . $datetime->format( 'H:i:s.v' ) . '] ' . $msg . PHP_EOL;
            
            return true;
            
        }
        
        // @return bool
        private function checkAccess() {
            
            $headers = @get_headers( $this->endPoint );
            
            if( !$headers || !strpos( $headers[0], '200' ) ) {
                
                $this->status( 'ERROR' );
                $this->status( $this->endPoint . ' not responding' );
                $this->status( 'abort script' );
                
                exit;
                
            } else {
                
                return true;
                
            }
            
        }
        
        // @param string $tokenType [createaccount, csrf, login, patrol, rollback, userrights, watch]
        // @return string token
        private function getToken(
            string $tokenType = 'csrf'
        ) {
            
            $this->status( 'get ' . $tokenType . ' token' );
            
            $params = [
                'action' => 'query',
                'meta' =>   'tokens',
                'format' => 'json',
                'type' =>   $tokenType
            ];
            
            $url = $this->endPoint . '?' . http_build_query( $params );
            
            $ch = curl_init( $url );
            
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_COOKIEJAR, 'cookie.txt' );
            curl_setopt( $ch, CURLOPT_COOKIEFILE, 'cookie.txt' );
            
            $output = curl_exec( $ch );
            
            curl_close( $ch );
            
            $result = json_decode( $output, true );
            
            return $result['query']['tokens'][ $tokenType . 'token'];
            
        }
        
        // @param array $params
        // @return mixed output
        private function doRequest(
            array $params
        ) {
            
            $this->status( 'try request' . ( isset( $params['action'] ) ? ' action=' . $params['action'] : '' ) );
            
            $params['format'] = 'json';
            
            $ch = curl_init();
            
            curl_setopt( $ch, CURLOPT_URL, $this->endPoint );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_COOKIEJAR, 'cookie.txt' );
            curl_setopt( $ch, CURLOPT_COOKIEFILE, 'cookie.txt' );
            
            $output = curl_exec( $ch );
            
            curl_close( $ch );
            
            return json_decode( $output, true );
            
        }
        
        // @param array $result
        // @param string $successMsg
        private function requestStatus(
            array $result,
            string $successMsg = ''
        ) {
            
            if( isset( $result['error'] ) ) {
                
                $this->status( 'error: ' . $result['error']['code'] );
                $this->status( 'error info: ' . $result['error']['info'] );
                
            } else if( isset( $result['warnings'] ) ) {
                
                $module = array_key_first( $result['warnings'] );
                
                $this->status( 'warning occurred in module ' . $module );
                $this->status( 'warning info: ' . $result['warnings'][ $module ]['*'] );
                
            } else {
                
                $this->status( 'success' );
                
                if( strlen( $successMsg ) > 0 ) {
                    
                    $this->status( $successMsg );
                    
                }
                
            }
            
        }
        
        // @return bool
        // abort script on error
        public function login() {
            
            $this->status( 'try login' );
            
            $result = $this->doRequest( [
                'action' => 'query',
                'meta' =>   'userinfo'
            ] );
            
            if( isset( $result['query']['userinfo']['id'] ) ) {
                
                $this->status( 'success' );
                $this->status( 'logged in as ' . $result['query']['userinfo']['name'] );
                
                return true;
                
            }
            
            $result = $this->doRequest( [
                'action' =>     'login',
                'lgname' =>     $this->botUsername,
                'lgpassword' => $this->botPassword,
                'lgtoken' =>    $this->getToken( 'login' )
            ] );
            
            if( isset( $result['login']['result'] ) && $result['login']['result'] == 'Success' ) {
                
                $this->status( 'success' );
                $this->status( 'logged in as ' . $result['login']['lgusername'] );
                
                return true;
                
            } else {
                
                $this->status( 'ERROR' );
                $this->status( 'login failed' );
                $this->status( 'abort script' );
                
                exit;
                
            }
            
        }
        
        // @return array
        public function logout() {
            
            $this->status( 'logout' );
            
            $result = $this->doRequest( [
                'action' =>   'logout'/*,
                'token' =>    $this->getToken()*/
            ] );
            
            $this->requestStatus( $result, 'user was logged out' );
            
            return $result;
            
        }
        
        // @return array with loaded modules
        // @param ... string $modules
        public function loadModule(
            ... $modules
        ) {
            
            foreach( $modules as $module ) {
                
                if( in_array(
                    $module,
                    $this->loadedModules
                ) ) {
                    
                    $this->status( 'skip already loaded module ' . $module );
                    
                    continue;
                    
                }
                
                $this->status( 'try loading module ' . $module );
                
                if( !file_exists( __DIR__ . '/modules/' . $module . '.php' ) ) {
                    
                    $this->status( 'error: module ' . $module . ' could not found' );
                    
                    continue;
                    
                } else {
                    
                    require_once( __DIR__ . '/modules/' . $module . '.php' );
                    
                    $this->status( 'module ' . $module . ' loaded' );
                    $this->status( 'try starting module ' . $module );
                    
                    if( new $module() ) {
                        
                        $this->loadedModules[] = $module;
                        
                        $this->status( 'module ' . $module . ' started successfully' );
                        
                    } else {
                        
                        $this->status( 'error: module ' . $module . ' could not started' );
                        
                    }
                    
                }
                
            }
            
            return $this->loadedModules;
            
        }
        
        // @param array $params
        // @return mixed $requireToken
        // @return array
        public function request(
            array $params,
            $requireToken = false
        ) {
            
            if( $requireToken != false ) {
                
                $params['token'] = $this->getToken(
                    is_string( $requireToken ) ? $requireToken : 'csrf'
                );
                
            }
            
            $result = $this->doRequest( $params );
            
            $this->requestStatus( $result );
            
            return $result;
            
        }
        
    }
    
?>
