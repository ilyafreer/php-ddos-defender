<?php
declare(strict_types=1);

namespace Ilyafreer\DdosDefender;

final class Defender
{
    private const PREFIX_FILE_NAME = 'ddos_filter_';
    private string $pathFile = '.';
    private bool $byUserAgent = false;

    private array $timeOutSecondList = []; 
    private array $ipList = [];
    private array $userAgentList = [];
    private string $blockMessage = 'Too many requests';
    private int $blockCode = 429;
    private int $deleteHistoryIntervalDays = 5;
    
    public function setPathFile(string $pathFile): self
    {
        $this->pathFile = $pathFile;
        return $this;
    }

    public function setBlockMessage(string $message): self
    {
        $this->blockMessage = $message;
        return $this;
    }

    public function setDeleteHistoryInterval(int $deleteHistoryIntervalDays): self
    {
        $this->deleteHistoryIntervalDays = $deleteHistoryIntervalDays;
        return $this;
    }

    public function setBlockCode(int $code): self
    {
        $this->blockCode = $code;
        return $this;
    }

    public function useUserAgent(): self
    {
        $this->byUserAgent = true;
        return $this;
    }

    /**
     * Timeout seconds between requests2, requests3, requests4, requests5
     * if the new request is earlier than the timeout, the request will be blocked 
     * Example: If time between request1 and request2 is smaller whan 0.5 second, request will be block
     * If time between request2 and request3 is smaller whan 1 second, request will be block etc.
     * @param array $timeOutSecondList [0.5, 1, 2]
     * @return self
     */
    public function setTimeOutList(array $timeOutSecondList): self
    {
        $this->timeOutSecondList = $timeOutSecondList;
        return $this;
    }

    public function setIpList(array $ipList): self
    {
        $this->ipList = $ipList;
        return $this;
    }

    public function setUserAgentList(array $userAgentList): self
    {
        $this->userAgentList = $userAgentList;
        return $this;
    }

    /**
     * Run DDos defender
     *
     * @return void
     */
    public function run(): void
    {
        $currentUserIp = $_SERVER['REMOTE_ADDR'];
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'];

        if ($this->ipList && $this->inBlockIpList($currentUserIp)) {
            $this->ban();
        }

        if ($this->userAgentList && $this->inBlockUserAgentList($currentUserAgent)) {
            $this->ban();
        }
        
        if ($this->timeOutSecondList && $this->blockByHistory($currentUserIp, $currentUserAgent)) {
            $this->ban();
        } 
    }

    private function ban(): void
    {
        http_response_code($this->blockCode);
        print($this->blockMessage);
        die;
    }

    private function inBlockIpList(string $currentUserIp): bool
    {
        if (in_array($currentUserIp, $this->ipList)) {
            return true;
        }

        return false;
    }

    private function inBlockUserAgentList(string $currentUserAgent): bool
    {
        array_map(function($item) use ($currentUserAgent) {
            if (strpos($currentUserAgent, $item) !== false) {
                return true;
            }
        }, $this->userAgentList);

        return false;
    }

    private function blockByHistory(string $currentUserIp, string $currentUserAgent): bool
    {
        $this->removeHistoryFiles();
        $fullFilePath = $this->pathFile.DIRECTORY_SEPARATOR.self::PREFIX_FILE_NAME.date('Y-m-d');
        $timeNow = microtime(true);

        if (!file_exists($fullFilePath)) {
            $file = fopen($fullFilePath, "w+");
            fclose($file);
            $rowParams = $this->setRowParams($currentUserIp, $currentUserAgent, $timeNow);
            $this->insertNewRow($rowParams, $fullFilePath);

            return false;
        }

        $content = file_get_contents($fullFilePath);

        if ($row = $this->getRecord(
                    $currentUserIp,
                    $currentUserAgent,
                    $content
                )
            ) {
                $rowParams = $this->fetchAssocRowParams($row); 
                $newRowParams = $this->setRowParams(
                    $currentUserIp,
                    $currentUserAgent,
                    $timeNow,
                    $rowParams['count']+1
                );
                $this->updateRow($rowParams, $newRowParams, $fullFilePath, $content);

                $timeOut = isset($this->timeOutSecondList[$rowParams['count']-1]) ? 
                    $this->timeOutSecondList[$rowParams['count']-1] : 
                    $this->timeOutSecondList[count($this->timeOutSecondList)-1];

                if ($timeNow - (float)$rowParams['time'] < $timeOut) {
                    return true;
                }
        } else {
            $rowParams = $this->setRowParams($currentUserIp, $currentUserAgent, $timeNow);
            $this->insertNewRow($rowParams, $fullFilePath, $content);
        }

        return false;
    }

    

    private function buildPatternRow(string $currentUserIp, string $currentUserAgent): string
    {
        if ($this->byUserAgent) {
            $currentUserAgent = preg_quote($currentUserAgent, '/');
            return "/(ip={$currentUserIp};)(time=\d{10,}\.\d{1,};)(count=\d{1,};)(user_agent={$currentUserAgent};)/";
        }
        
        return "/(ip={$currentUserIp};)(time=\d{10,}\.\d{1,};)(count=\d{1,};)(user_agent=.+;)/";
    }

    private function getRecord(string $currentUserIp, string $currentUserAgent, string $content): array
    {
        $pattern = $this->buildPatternRow($currentUserIp, $currentUserAgent);

        if (preg_match($pattern, $content, $match)) {
            return array_slice($match, 1);
        }

        return [];
    }

    private function setRowParams(
        string $currentUserIp, 
        string $currentUserAgent,
        float $time,
        int $count = 1,
        ): array
    {
        return [
            'ip' => $currentUserIp,
            'time' => $time,
            'count' => $count,
            'user_agent' => $currentUserAgent,
        ];
    }

    private function fetchAssocRowParams(array $params): array
    {
        $result = [];
        foreach($params as $row){
            $split = explode('=', trim($row, ';'));
            $result[$split[0]] = $split[1];
        }

        return $result;
    }

    private function insertNewRow(array $rowParams, string $fullFilePath, string $content = ''): void
    {
        $newRow = $this->buildRow($rowParams);
        $content = $content ? $content.PHP_EOL.$newRow : $newRow;
        file_put_contents($fullFilePath, $content);
    }

    private function buildRow(array $params): string
    {
        $resutl = '';
        foreach($params as $key => $value){
            $resutl .= "{$key}={$value};";
        }

        return $resutl;
    }

    private function updateRow(array $oldRowParams,array $newRowParams, string $fullFilePath, string $content): void
    {
        $oldRow = $this->buildRow($oldRowParams);
        
        $pattern = [
            '/time='.$oldRowParams['time'].';/',
            '/count='.$oldRowParams['count'].';/'
        ];

        $replacements = [
            'time='.$newRowParams['time'].';',
            'count='.$newRowParams['count'].';',
        ];

        $content = preg_replace($pattern, $replacements, $oldRow);
        file_put_contents($fullFilePath, $content);
    }

    private function removeHistoryFiles(): void
    {
        $currentDate = new \Datetime('now');

        /* get files in dir */
        $files = scandir($this->pathFile);
        foreach ($files as $fileName){
            if (preg_match('/'.self::PREFIX_FILE_NAME.'(\d{4,}-\d{2,}-\d{2,})/', $fileName, $match)) {
                $fileDate = new \Datetime($match[1]);
                if ( $currentDate->diff($fileDate)->days > $this->deleteHistoryIntervalDays) {
                    unlink($this->pathFile.DIRECTORY_SEPARATOR.$fileName);
                }
            }
        }
    }
}

