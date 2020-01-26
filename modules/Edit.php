<?php
    
    /*******************************************************************
     * 
     * MWSimpleBot
     * module: Edit
     * 
     * author     komed3
     * version    0.004
     * date       2020/01/26
     * 
     *******************************************************************/
    
    class Edit extends MWSimpleBot {
        
        // @return bool true
        function __construct() {
            
            return true;
            
        }
        
        // @param array params:
        //    string $title
        //    string $appendText
        //    string $summary
        //    bool $minor
        //    bool $bot
        //    bool $notminor
        //    bool $recreate
        //    bool $createonly
        //    bool $nocreate
        //    string $hash [md5 hash]
        // @return bool|array result
        public function appendText(
            array $params
        ) {
            
            if( !parent::checkParams( $params, 2 ) ) {
                
                return false;
                
            }
            
            $requestParams = [
                'title' =>      $params[0],
                'appendtext' => $params[1],
                'summary' =>    ( isset( $params[2] ) ? $params[2] : 'edit by MWSimpleBot action' ),
                'minor' =>      ( isset( $params[3] ) ? $params[3] : false ),
                'bot' =>        ( isset( $params[4] ) ? $params[4] : true ),
                'notminor' =>   ( isset( $params[5] ) ? $params[5] : true ),
                'recreate' =>   ( isset( $params[6] ) ? $params[6] : true ),
                'createonly' => ( isset( $params[7] ) ? $params[7] : false ),
                'nocreate' =>   ( isset( $params[8] ) ? $params[8] : false )
            ];
            
            if( isset( $params[9] ) ) {
                
                $requestParams['md5'] = $params[9];
                
            }
            
            return parent::request(
                $requestParams,
                true
            );
            
        }
        
    }
    
?>
