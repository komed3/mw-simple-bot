# MWSimpleBot

MediaWiki Bot class, project has beta status
__build 0.010 alpha__

The intention of this project is to create an interface with the MediaWiki API to allow users to quickly and easily write bots for their wiki. Without complicated queries and with a minimal understanding of the program code.

Tested with MediaWiki 1.28, 1.29, 1.30, 1.31
Should also work with older and newer versions.

The project is currently in active development. The branch is an early version that already provides many of the later main features. Modules are not yet available.

## Installation

Download the files of this build. To use MWSimpleBot either php or a web server (e.g. Apache xampp) is required on your system.

Überprüfen Sie MWSimpleBot durch den Aufruf von `test.php`. To do this, call the command prompt in the installation directory and execute the following action:
```
php test.php
```

MWSimpleBot works correctly when the bot starts, connects to the API of `test.wikipedia.org` and checks its MediaWiki version. At the time of release build, the call generated:

```
[18:18:16.287] MWSimpleBot build 0.010 alpha started
[18:18:17.059] check MediaWiki version
[18:18:17.059] try request action=query
[18:18:17.448] success: request has not returned any errors
[18:18:17.465] MediaWiki has version 1.35.0.16
```

## Try it

1. Create a bot password in your Wiki project.
__Attention:__ Give the bot the right to edit pages and create non existing pages.
2. Create a php file with the following content:

```php
<?php
    
    // include MWSimpleBot class
    require_once './MWSimpleBot.php';
	
	// start MWSimpleBot
	$mwBot = new MWSimpleBot(
		'https://example/api.php', // replace with your API url
		'Bot@Bot', // replace with your bot username
        'ddkbdisr63o0g4g646iqjod01edo0s6i' // replace with your bot password
	);
    
    // login bot
    $mwbot->login();
    
    // edit page "Sandbox"
    $mwbot->status( $mwbot->request( [
        'action' =>  'edit',
        'title' =>   'Sandbox',
        'text' =>    'Hello World ;)',
        'summary' => 'bot edit'
    ], true ) );
    
    // logout bot
    $mwbot->logout();
    
?>
```

Check the created/edited page "Sandbox" in your Wiki.