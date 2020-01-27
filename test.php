<?php
    
    // This is a test for MWSimpleBot
    // it does not require a bot password and makes no changes
    
    require_once __DIR__ . '/MWSimpleBot.php';
    
    $mwBot = new MWSimpleBot();
    
    $mwBot->setEndPoint( 'https://test.wikipedia.org/w/api.php' );
    
    $mwBot->checkMWVersion();
    
?>