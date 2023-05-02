<?
declare(strict_types=1);

use DdosDefender\Defender;

(new Defender())
    ->setIpList(['172.27.0.1'])
    ->setTimeOutList([0.5, 1, 1.5, 2, 3, 5, 10, 15])
    ->setUserAgentList([
        'AppleWebKit/537.36 (KHTML, like Gecko)'
    ])
    ->useUserAgent()
    ->run();