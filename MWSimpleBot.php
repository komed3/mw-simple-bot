<?php
    
    /*******************************************************************
     * 
     * MWSimpleBot
     * MediaWiki Bot class
     * 
     * author     komed3
     * version    0.011 alpha
     * date       2020/02/01
     * 
     *******************************************************************/
    
    // define entry point
    define( 'MWSimpleBot', '0.010 alpha' );
    
    // version check
    if( version_compare( PHP_VERSION, '7.2.0' ) < 0 ) {
        
        die( 'requires php version 7.2.0 or higher' );
        
    }
    
    // @var string $endPoint url to api.php
    $endPoint = '';
    
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
        // @param string $botPassword,
        // @param string $logMode [text, file, both, off]
        function __construct(
            string $endPoint = '',
            string $botUsername = '',
            string $botPassword = '',
            string $logMode = '';
        ) {
            
            if( strlen( $logMode ) > 0 ) {
                
                $this->setLogMode( $logMode );
                
            }
            
            $this->logging( 'MWSimpleBot build ' . MWSimpleBot . ' started' );
            
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
                
                $this->logging( 'error: set log mode to ' . $newLogMode . ' failed' );
                $this->logging( 'log mode remains ' . $logMode );
                
                return false;
                
            } else if( $newLogMode != $logMode ) {
                
                $logMode = $newLogMode;
                
                $this->logging( 'success: set log mode to ' . $logMode );
                
                if( in_array( $logMode, [ 'file', 'both' ] ) && strlen( $pathToLogFile ) ) {
                    
                    $logFilePath = $pathToLogFile;
                    
                    $this->logging( 'success: set new path to log file' );
                    
                }
                
            }
            
            return true;
            
        }
        
        // @param string $msg logging message
        // @return bool true
        protected function logging(
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
                    
                    if( $file = fopen( $logFilePath, 'a' ) ) {
                        
                        fwrite( $file, $logging[ ( count( $logging ) - 1 ) ] );
                        
                        fclose( $file );
                        
                    } else {
                        
                        $this->setLogMode( 'text' );
                        
                        $this->logging( 'error: could not open log file > set log mode to text' );
                        
                    }
                    
                }
                
            }
            
            return true;
            
        }
        
        // @since build 0.009 alpha
        // @param string $pathToLogFile
        // @return bool
        public function getLogFile(
            string $pathToLogFile
        ) {
            
            global $logging;
            
            if( strlen( $pathToLogFile ) > 0 && $file = fopen( $pathToLogFile, 'a' ) ) {
                
                $this->logging( 'write log file ' . $pathToLogFile );
                
                fwrite( $file, '@ MWSimpleBot' . PHP_EOL .
                               '@ build ' . MWSimpleBot . PHP_EOL .
                               '@ automatically created log file at ' . date( 'Y-m-d H:i:s' ) . PHP_EOL );
                
                foreach( $logging as $log ) {
                    
                    fwrite( $file, $log );
                    
                }
                
                fwrite( $file, '@ END' . PHP_EOL . PHP_EOL );
                
                fclose( $file );
                
                return true;
                
            } else {
                
                $this->logging( 'error: could not open ' . $pathToLogFile );
                $this->logging( 'error info: no log file was output' );
                
                return false;
                
            }
            
        }
        
        // @param bool $abort
        // @return bool
        protected function checkEndPoint(
            bool $abort = false
        ) {
            
            global $endPoint;
            
            $headers = @get_headers( $endPoint );
            
            if( !$headers || !strpos( $headers[0], '200' ) ) {
                
                $this->logging( 'ERROR' );
                $this->logging( $endPoint . ' not responding' );
                
                if( $abort ) {
                    
                    $this->logging( 'abort script' );
                    
                    exit;
                    
                }
                
                return false;
                
            } else {
                
                return true;
                
            }
            
        }
        
        // @since build 0.010 alpha
        // @param null|string $versionToCompare
        // @return bool|string
        public function checkMWVersion(
            $versionToCompare = null
        ) {
            
            $this->logging( 'check MediaWiki version' );
            
            if( $this->status( $res = $this->doRequest( [
                'action' => 'query',
                'meta' =>   'siteinfo',
                'siprop' => 'general'
            ] ) ) ) {
                
                $version = preg_replace( '/[^0-9\.]/', '', $res['query']['general']['generator'] );
                
                $this->logging( 'MediaWiki has version ' . $version );
                
                if( is_null( $versionToCompare ) ) {
                    
                    return $version;
                    
                } else if( version_compare( $version, $versionToCompare ) >= 0 ) {
                    
                    return true;
                    
                }
                
            }
            
            return false;
            
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
                
                $this->logging( 'warning: request returned error/warning' );
                
                return false;
                
            } else {
                
                $this->logging( 'success: request has not returned any errors' );
                
                return true;
                
            }
            
        }
        
        // @param string $tokenType [createaccount, csrf, login, patrol, rollback, userrights, watch]
        // @return string token
        protected function getToken(
            string $tokenType = 'csrf'
        ) {
            
            global $endPoint;
            
            $this->logging( 'get ' . $tokenType . ' token' );
            
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
                
                $this->logging( 'error: fetching token failed' );
                
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
            
            $this->logging( 'try request' . ( isset( $params['action'] ) ? ' action=' . $params['action'] : '' ) );
            
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
                
                $this->logging( 'error: ' . $result['error']['code'] );
                $this->logging( 'error info: ' . $result['error']['info'] );
                
            } else if( isset( $result['warnings'] ) ) {
                
                $module = array_key_first( $result['warnings'] );
                
                $this->logging( 'warning occurred in module ' . $module );
                $this->logging( 'warning info: ' . $result['warnings'][ $module ]['*'] );
                
            } else {
                
                $this->logging( 'success' );
                
                if( strlen( $successMsg ) > 0 ) {
                    
                    $this->logging( $successMsg );
                    
                }
                
            }
            
        }
        
        // @return bool
        // abort script on error
        public function login() {
            
            $this->logging( 'try login' );
            
            $result = $this->doRequest( [
                'action' => 'query',
                'meta' =>   'userinfo'
            ] );
            
            if( isset( $result['query']['userinfo']['id'] ) ) {
                
                $this->logging( 'success' );
                $this->logging( 'logged in as ' . $result['query']['userinfo']['name'] );
                
                return true;
                
            }
            
            $result = $this->doRequest( [
                'action' =>     'login',
                'lgname' =>     $this->botUsername,
                'lgpassword' => $this->botPassword,
                'lgtoken' =>    $this->getToken( 'login' )
            ] );
            
            if( isset( $result['login']['result'] ) && $result['login']['result'] == 'Success' ) {
                
                $this->logging( 'success' );
                $this->logging( 'logged in as ' . $result['login']['lgusername'] );
                
                return true;
                
            } else {
                
                $this->logging( 'ERROR' );
                $this->logging( 'login failed' );
                $this->logging( 'abort script' );
                
                exit;
                
            }
            
        }
        
        // @return array
        public function logout() {
            
            $this->logging( 'logout' );
            
            // since MediaWiki 1.34.0 a token is required
            if( $this->checkMWVersion( '1.34.0' ) ) {
                
                $result = $this->doRequest( [
                    'action' =>   'logout',
                    'token' =>    $this->getToken()
                ] );
                
            } else {
                
                $result = $this->doRequest( [
                    'action' =>   'logout'
                ] );
                
            }
            
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
                    
                    $this->logging( 'error: request aborted, because token assumed' );
                    
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
                    
                    $this->logging( 'skip already loaded module ' . $module );
                    
                    continue;
                    
                }
                
                $this->logging( 'try loading module ' . $module );
                
                if( !file_exists( __DIR__ . '/modules/' . $module . '.php' ) ) {
                    
                    $this->logging( 'error: module ' . $module . ' could not found' );
                    
                    continue;
                    
                } else {
                    
                    require_once( __DIR__ . '/modules/' . $module . '.php' );
                    
                    $this->logging( 'module ' . $module . ' loaded' );
                    $this->logging( 'try starting module ' . $module );
                    
                    if( new $module() ) {
                        
                        $this->loadedModules[] = $module;
                        
                        $this->logging( 'module ' . $module . ' started successfully' );
                        
                    } else {
                        
                        $this->logging( 'error: module ' . $module . ' could not started' );
                        
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
            
            $this->logging( 'try ' . $module . ' > ' . $method );
            
            if( !in_array(
                $module,
                $this->loadedModules
            ) ) {
                
                $this->logging( 'module ' . $module . ' not loaded' );
                $this->logging( 'try autoload module ' . $module );
                
                $this->loadModule( $module );
                
                if( !in_array(
                    $module,
                    $this->loadedModules
                ) ) {
                    
                    $this->logging( 'error: autoload failed' );
                    
                    return false;
                    
                } else {
                    
                    $this->logging( 'module ' . $module . ' autoloaded successfully' );
                    
                    $this->mf( $module, $method, $params );
                    
                }
                
            } else {
                
                $m = new $module();
                
                if( !method_exists( $m, $method ) ) {
                    
                    $this->logging( 'error: method ' . $method . ' of module ' . $module . ' not defined' );
                    
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
                
                $this->logging( 'warning: required parameters not available' );
                
                return false;
                
            }
            
        }
        
    }
    
?>
