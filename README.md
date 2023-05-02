# ddos_defender
Simple protects from ddos attacks only php. Blocks requests by a list of timeouts. Deny by ipList for ip v4. Deny by userAgentList. It is possible to set a partial match

###install###
``composer require ilyafreer/ddos_defender``

###Usage###
```use DdosDefender\Defender;

(new Defender())
    ->setIpList(['172.27.0.1'])
    ->setTimeOutList([0.5, 1, 1.5, 2, 3, 5, 10, 15])
    ->userUserAgent()
    ->run();```


###Methods###
*setPathFile* - path for history file for deny access by timeout list

*setTimeOutList* - array ips v4

*setIpList* - array deny ips

*setUserAgentList* - array user agents. May only pass
```->setUserAgentList([
        'AppleWebKit/537.36 (KHTML, like Gecko)'
    ])```

*setBlockMessage* - set text message
Default message - 'Too many requests'

*setBlockResponseCode* - set http code 
Default code - 429

*useUserAgent* - If use it, timeout will be find equals ip + user agent

*run* - start defender