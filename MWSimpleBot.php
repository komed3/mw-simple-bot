<?php
    
    /*******************************************************************
     * 
     * MWSimpleBot [ALPHA]
     * MediaWiki Bot class
     * 
     * author     komed3
     * version    0.005
     * date       2020/01/26
     * 
     *******************************************************************/
    
    // @var string $endPoint url to api.php
    $endPoint;
    
    class MWSimpleBot {
        
        // @var string $botUsername
        private $botUsername;
        
        // @var string $botPassword
        private $botPassword;
        
        // @var array $loadedModules
        protected $loadedModules = [];
        
        // @param string $endPoint
        // @param string $botUsername
        // @param string $botPassword
        function __construct(
            string $endPoint = '',
            string $botUsername = '',
            string $botPassword = ''
        ) {
            
            $this->status( 'MWSimpleBot started' );
            
            if( strlen( $endPoint ) > 0 ) {
                
                $this->setEndPoint( $endPoint );
                
            }
            
            if( strlen( $botUsername ) > 0 && strlen( $botPassword ) > 0 ) {
                
                $this->setBotUser( $botUsername, $botPassword );
                
            }
            
        }
        
        // @param string $url
        // @return bool true
        public function setEndPoint(
            string $url
        ) {
            
            global $endPoint;
            
            $endPoint = $url;
            
            $this->checkAccess( true );
            
            return true;
            
        }
        
        // @param string $botUsername
        // @param string $botPassword
        // @return bool true
        public function setBotUser(
            string $botUsername,
            string $botPassword
        ) {
            
            $this->botUsername = $botUsername;
            
            $this->botPassword = $botPassword;
            
            return true;
            
        }
        
        // @param string $msg status message
        // @return bool true
        protected function status(
            string $msg
        ) {
            
            $datetime = new DateTime();
            
            print '[' . $datetime->format( 'H:i:s.v' ) . '] ' . $msg . PHP_EOL;
            
            return true;
            
        }
        
        // @param bool $abort
        // @return bool
        protected function checkAccess(
            bool $abort = false
        ) {
            
            global $endPoint;
            
            $headers = @get_headers( $endPoint );
            
            if( !$headers || !strpos( $headers[0], '200' ) ) {
                
                $this->status( 'ERROR' );
                $this->status( $endPoint . ' not responding' );
                
                if( $abort ) {
                    
                    $this->status( 'abort script' );
                    
                    exit;
                    
                }
                
                return false;
                
            } else {
                
                return true;
                
            }
            
        }
        
        // @param string $tokenType [createaccount, csrf, login, patrol, rollback, userrights, watch]
        // @return string token
        protected function getToken(
            string $tokenType = 'csrf'
        ) {
            
            global $endPoint;
            
            $this->status( 'get ' . $tokenType . ' token' );
            
            $this->checkAccess();
            
            $params = [
                'action' => 'query',
                'meta' =>   'tokens',
                'format' => 'json',
                'type' =>   $tokenType
            ];
            
            $url = $endPoint . '?' . http_build_query( $params );
            
            $ch = curl_init( $url );
            
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_COOKIEJAR, 'cookie.txt' );
            curl_setopt( $ch, CURLOPT_COOKIEFILE, 'cookie.txt' );
            
            $output = curl_exec( $ch );
            
            curl_close( $ch );
            
            $result = json_decode( $output, true );
            
            if( is_null( $result ) ) {
                
                $this->status( 'error: fetching token failed' );
                
                return false;
                
            } else {
                
                return $result['query']['tokens'][ $tokenType . 'token'];
                
            }
            
        }
        
        // @param array $params
        // @return mixed output
        protected function doRequest(
            array $params
        ) {
            
            global $endPoint;
            
            $this->status( 'try request' . ( isset( $params['action'] ) ? ' action=' . $params['action'] : '' ) );
            
            $params['format'] = 'json';
            
            $ch = curl_init();
            
            curl_setopt( $ch, CURLOPT_URL, $endPoint );
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
        protected function requestStatus(
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
        // @param mixed $requireToken
        // @return array
        public function request(
            array $params,
            $requireToken = false
        ) {
            
            if( $requireToken != false ) {
                
                $params['token'] = $this->getToken(
                    is_string( $requireToken ) ? $requireToken : 'csrf'
                );
                
                if( $params['token'] == false ) {
                    
                    $this->status( 'error: request aborted, because token assumed' );
                    
                    return false;
                    
                }
                
            }
            
            $result = $this->doRequest( $params );
            
            $this->requestStatus( $result );
            
            return $result;
            
        }
        
        // @param string $module
        // @param string $function
        // @param mixed $params
        // @return bool|array
        public function mf(
            string $module,
            string $method,
            ... $params
        ) {
            
            $this->status( 'try ' . $module . ' > ' . $method );
            
            if( !in_array(
                $module,
                $this->loadedModules
            ) ) {
                
                $this->status( 'module ' . $module . ' not loaded' );
                $this->status( 'try autoload module ' . $module );
                
                $this->loadModule( $module );
                
                if( !in_array(
                    $module,
                    $this->loadedModules
                ) ) {
                    
                    $this->status( 'error: autoload failed' );
                    
                    return false;
                    
                } else {
                    
                    $this->status( 'module ' . $module . ' autoloaded successfully' );
                    
                    $this->mf( $module, $method, $params );
                    
                }
                
            } else {
                
                $m = new $module();
                
                if( !method_exists( $m, $method ) ) {
                    
                    $this->status( 'error: method ' . $method . ' of module ' . $module . ' not defined' );
                    
                    return false;
                    
                } else {
                   
                    return $m->$method( $params[0] );
                    
                }
                
            }
            
        }
        
        // @param array $params
        // @param int $cnt
        // @return bool
        public function checkParams(
            array $params,
            int $cnt
        ) {
            
            if( count( $params ) >= $cnt ) {
                
                return true;
                
            } else {
                
                $this->status( 'warning: required parameters not available' );
                
                return false;
                
            }
            
        }
        
    }
    
?>
