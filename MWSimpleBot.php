<?php
    
    /*******************************************************************
     * 
     * MWSimpleBot [ALPHA]
     * MediaWiki Bot class
     * 
     * author     komed3
     * version    0.008
     * date       2020/01/26
     * 
     *******************************************************************/
    
    // define entry point
    define( 'MWSimpleBot', true );
    
    // version check
    if( version_compare( PHP_VERSION, '7.2.0' ) < 0 ) {
        
        die( 'requires php version 7.2.0 or higher' );
        
    }
    
    // @var string $endPoint url to api.php
    $endPoint;
    
    // @var array $logging
    $logging = [];
    
    // @var string $logMode
    $logMode = 'text';
    
    // @var string $logFilePath
    $logFilePath = __DIR__ . '/log_' . date( 'ymdHis' ) . '.log';
    
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
            
            $this->log( 'MWSimpleBot started' );
            
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
            
            $this->checkEndPoint( true );
            
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
        
        // @param string $newLogMode [text, file, both, off]
        // @param string $pathToLogFile
        // @return bool
        public function setLogMode(
            string $newLogMode = 'text',
            string $pathToLogFile = ''
        ) {
            
            global $logMode, $logFilePath;
            
            if( !in_array(
                $newLogMode,
                [ 'text', 'file', 'both', 'off' ]
            ) ) {
                
                $this->log( 'error: set log mode to ' . $newLogMode . ' failed' );
                $this->log( 'log mode remains ' . $logMode );
                
                return false;
                
            } else if( $newLogMode != $logMode ) {
                
                $logMode = $newLogMode;
                
                $this->log( 'success: set log mode to ' . $logMode );
                
                if( in_array( $logMode, [ 'file', 'both' ] ) && strlen( $pathToLogFile ) ) {
                    
                    $logFilePath = $pathToLogFile;
                    
                    $this->log( 'success: set new path to log file' );
                    
                }
                
            }
            
            return true;
            
        }
        
        // @param string $msg logging message
        // @return bool true
        protected function log(
            string $msg
        ) {
            
            global $logging, $logMode, $logFilePath;
            
            if( in_array( $logMode, [ 'text', 'file', 'both' ] ) ) {
                
                $datetime = new DateTime();
                
                $logging[] = '[' . $datetime->format( 'H:i:s.v' ) . '] ' . $msg . PHP_EOL;
                
                if( in_array( $logMode, [ 'text', 'both' ] ) ) {
                    
                    print $logging[ ( count( $logging ) - 1 ) ];
                    
                }
                
                if( in_array( $logMode, [ 'file', 'both' ] ) ) {
                    
                    $file = fopen( $logFilePath, 'a' );
                    
                    fwrite( $file, $logging[ ( count( $logging ) - 1 ) ] );
                    
                    fclose( $file );
                    
                }
                
            }
            
            return true;
            
        }
        
        // @param bool $abort
        // @return bool
        protected function checkEndPoint(
            bool $abort = false
        ) {
            
            global $endPoint;
            
            $headers = @get_headers( $endPoint );
            
            if( !$headers || !strpos( $headers[0], '200' ) ) {
                
                $this->log( 'ERROR' );
                $this->log( $endPoint . ' not responding' );
                
                if( $abort ) {
                    
                    $this->log( 'abort script' );
                    
                    exit;
                    
                }
                
                return false;
                
            } else {
                
                return true;
                
            }
            
        }
        
        // @param mixed $result
        // @return bool
        public function status(
            $result
        ) {
            
            if(
                is_null( $result ) ||
                !is_array( $result ) ||
                isset( $result['error'] ) ||
                isset( $result['warnings'] )
            ) {
                
                $this->log( 'warning: request returned error/warning' );
                
                return false;
                
            } else {
                
                $this->log( 'success: request has not returned any errors' );
                
                return true;
                
            }
            
        }
        
        // @param string $tokenType [createaccount, csrf, login, patrol, rollback, userrights, watch]
        // @return string token
        protected function getToken(
            string $tokenType = 'csrf'
        ) {
            
            global $endPoint;
            
            $this->log( 'get ' . $tokenType . ' token' );
            
            $this->checkEndPoint();
            
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
                
                $this->log( 'error: fetching token failed' );
                
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
            
            $this->log( 'try request' . ( isset( $params['action'] ) ? ' action=' . $params['action'] : '' ) );
            
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
                
                $this->log( 'error: ' . $result['error']['code'] );
                $this->log( 'error info: ' . $result['error']['info'] );
                
            } else if( isset( $result['warnings'] ) ) {
                
                $module = array_key_first( $result['warnings'] );
                
                $this->log( 'warning occurred in module ' . $module );
                $this->log( 'warning info: ' . $result['warnings'][ $module ]['*'] );
                
            } else {
                
                $this->log( 'success' );
                
                if( strlen( $successMsg ) > 0 ) {
                    
                    $this->log( $successMsg );
                    
                }
                
            }
            
        }
        
        // @return bool
        // abort script on error
        public function login() {
            
            $this->log( 'try login' );
            
            $result = $this->doRequest( [
                'action' => 'query',
                'meta' =>   'userinfo'
            ] );
            
            if( isset( $result['query']['userinfo']['id'] ) ) {
                
                $this->log( 'success' );
                $this->log( 'logged in as ' . $result['query']['userinfo']['name'] );
                
                return true;
                
            }
            
            $result = $this->doRequest( [
                'action' =>     'login',
                'lgname' =>     $this->botUsername,
                'lgpassword' => $this->botPassword,
                'lgtoken' =>    $this->getToken( 'login' )
            ] );
            
            if( isset( $result['login']['result'] ) && $result['login']['result'] == 'Success' ) {
                
                $this->log( 'success' );
                $this->log( 'logged in as ' . $result['login']['lgusername'] );
                
                return true;
                
            } else {
                
                $this->log( 'ERROR' );
                $this->log( 'login failed' );
                $this->log( 'abort script' );
                
                exit;
                
            }
            
        }
        
        // @return array
        public function logout() {
            
            $this->log( 'logout' );
            
            $result = $this->doRequest( [
                'action' =>   'logout'/*,
                'token' =>    $this->getToken()*/
            ] );
            
            $this->requestStatus( $result, 'user was logged out' );
            
            return $result;
            
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
                    
                    $this->log( 'error: request aborted, because token assumed' );
                    
                    return false;
                    
                }
                
            }
            
            $result = $this->doRequest( $params );
            
            $this->requestStatus( $result );
            
            return $result;
            
        }
        
        // @return array with loaded modules
        // @param string ... $modules
        public function loadModule(
            string ... $modules
        ) {
            
            foreach( $modules as $module ) {
                
                if( in_array(
                    $module,
                    $this->loadedModules
                ) ) {
                    
                    $this->log( 'skip already loaded module ' . $module );
                    
                    continue;
                    
                }
                
                $this->log( 'try loading module ' . $module );
                
                if( !file_exists( __DIR__ . '/modules/' . $module . '.php' ) ) {
                    
                    $this->log( 'error: module ' . $module . ' could not found' );
                    
                    continue;
                    
                } else {
                    
                    require_once( __DIR__ . '/modules/' . $module . '.php' );
                    
                    $this->log( 'module ' . $module . ' loaded' );
                    $this->log( 'try starting module ' . $module );
                    
                    if( new $module() ) {
                        
                        $this->loadedModules[] = $module;
                        
                        $this->log( 'module ' . $module . ' started successfully' );
                        
                    } else {
                        
                        $this->log( 'error: module ' . $module . ' could not started' );
                        
                    }
                    
                }
                
            }
            
            return $this->loadedModules;
            
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
            
            $this->log( 'try ' . $module . ' > ' . $method );
            
            if( !in_array(
                $module,
                $this->loadedModules
            ) ) {
                
                $this->log( 'module ' . $module . ' not loaded' );
                $this->log( 'try autoload module ' . $module );
                
                $this->loadModule( $module );
                
                if( !in_array(
                    $module,
                    $this->loadedModules
                ) ) {
                    
                    $this->log( 'error: autoload failed' );
                    
                    return false;
                    
                } else {
                    
                    $this->log( 'module ' . $module . ' autoloaded successfully' );
                    
                    $this->mf( $module, $method, $params );
                    
                }
                
            } else {
                
                $m = new $module();
                
                if( !method_exists( $m, $method ) ) {
                    
                    $this->log( 'error: method ' . $method . ' of module ' . $module . ' not defined' );
                    
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
                
                $this->log( 'warning: required parameters not available' );
                
                return false;
                
            }
            
        }
        
    }
    
?>
