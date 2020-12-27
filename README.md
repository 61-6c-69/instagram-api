# instagram-api
```php

$cli = new instagramClient('browser session');
$info = $cli->getInfo('eminem');
//https://www.instagram.com/p/CJNDjO2h39V/
$post = $cli->getPost('CJNDjO2h39V');
$media = $cli->getPostMedia('CJNDjO2h39V');
$infoUser = $cli->getUserInfo($cli->getID());
$stories = $cli->getStories('eminem');
```
check all function in class
