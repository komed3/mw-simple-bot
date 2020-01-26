<?php
    
    /*******************************************************************
     * 
     * MWSimpleBot
     * module: Edit
     * 
     * author     komed3
     * version    0.005
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
        //    string $hash [md5 hash]
        // @return bool|array result
        public function appendText(
            array $params
        ) {
            
            if( !parent::checkParams( $params, 2 ) ) {
                
                return false;
                
            }
            
            $requestParams = [
                'action' =>     'edit',
                'title' =>      $params[0],
                'appendtext' => $params[1],
                'summary' =>    ( isset( $params[2] ) ? $params[2] : 'edit by MWSimpleBot action' ),
                'minor' =>      ( isset( $params[3] ) ? $params[3] : false ),
                'bot' =>        ( isset( $params[4] ) ? $params[4] : true ),
                'notminor' =>   ( isset( $params[5] ) ? $params[5] : true )
            ];
            
            if( isset( $params[6] ) ) {
                
                $requestParams['md5'] = $params[6];
                
            }
            
            return parent::request(
                $requestParams,
                true
            );
            
        }
        
    }
    
?>
