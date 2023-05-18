# PHP DDoS Defender
`` Powered by history files ``

## Install
``composer require ilyafreer/php-ddos-defender``

## Example usage
```
require 'vendor/autoload.php';

use Ilyafreer\DdosDefender\Defender;

(new Defender())
    ->setIpList(['172.27.0.1'])
    ->setTimeOutList([0.5, 1, 1.5, 2, 3, 5, 10, 15])
    ->useUserAgent()
    ->run();
 ```

## Possibilities
* Set a list of ip v4 banned addresses 
```
->setIpList([
        '172.27.0.1',
        '172.27.0.2',
        '172.27.0.3'
    ]
)
```
---
* Set a list of user-agent (or their parameters)
```
->setUserAgentList(
    [
        'AppleWebKit/537.36 (KHTML, like Gecko)'
    ]
)
```
---
* Set access by timeout in seconds (the number of arguments is not limited)
```
->setTimeOutList([0.5, 1, 1.5, 2, 3, 5, 10, 20, 30])
```
---
* Set the response code and message text
```
->setBlockCode(401) #(default - 429)
->setBlockMessage('Some message') #(default - Too many requests)
```
---
* Set the interval for deleting the history files (default - 5 days)
```
->setDeleteHistoryInterval(2) 
```
---
* Set path to history file (default - current launch folder)
```
->setPathFile('/app/defender/')
```
---
* Use timeout for blocking by ip + user-agent data (default - off)
```
->useUserAgent()
```
