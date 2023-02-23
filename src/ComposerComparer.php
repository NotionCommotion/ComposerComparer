<?php
declare(strict_types=1);

namespace NotionCommotion\ComposerComparer;

defined('PHP_TAB') or define('PHP_TAB', "\t");

class ComposerComparer  //extends Command
{
    //private const FIELDS = ['type', 'license', 'require', 'require-dev', 'config', 'autoload', 'autoload-dev', 'replace', 'scripts', 'conflict', 'extra'];

    private array $json1, $json2;
    //private array $unsupported1, $unsupported2;

    public function __construct(private ?string $path1, private ?string $path2, private ?bool $dockers = null)
    {
        if(!$path1 || !$path2) {
            throw new \Exception('Path 1 and path 2 is required');            
        }
        $this->json1 = $this->getJson($path1);
        $this->json2 = $this->getJson($path2);
    }

    public function compare(): array
    {
        return $this->_compare($this->json1, $this->json2);
    }

    public function merge(): array
    {
        // first file will prevail.
        $json1 = $this->json1;
        $diff = $this->_compare($this->json1, $this->json2);
        return array_merge($this->json1, [
            'require'=>$this->_merge($diff['common']['require']),
            'require-dev'=>$this->_merge($diff['common']['require-dev']),
            'replace'=>$this->_merge($diff['common']['replace'])
        ]);
    }

    public function getRequired(?bool $dockers=null): array
    {
        // first file will have packages added to second.
        $dockers = $dockers??$this->dockers??false;
        $diff = $this->_compare($this->json1, $this->json2);
        return array_merge($this->_getRequired($diff['common']['require']['file1'], false, $dockers), $this->_getRequired($diff['common']['require-dev']['file1'], true, $dockers));
    }
    private function _getRequired(array $packages, bool $dev, bool $dockers=false): array
    {
        ksort($packages);
        $commands = [];
        foreach($packages as $name=>$revision) {
            $commands[] = sprintf('%scomposer require%s %s', $dockers?'docker compose exec php \\'.PHP_EOL.PHP_TAB:'', $dev?' --dev':'', $name);
        }
        return $commands;
    }

    public function getUpgraded(): array
    {
        // TBD how this works.
    }

    private function _compare(array $json1, array $json2): array
    {
        $rs = [
            'file1' => array_diff_key($json1, $json2),
            'file2' => array_diff_key($json2, $json1),
            'common' => []
        ];
        foreach(array_intersect_key($json1, $json2) as $key=>$value) {
            $rs['common'][$key] = is_array($value)
            ?$this->_compare($value, $json2[$key])
            :($value===$json2[$key]?[$value]:[$value, $json2[$key]]);
        }
        return $rs;;
    }

    private function _merge(array $data): array
    {
        $rs = array_merge($data['file1'], $data['file2']);
        foreach($data['common'] as $n=>$v) {
            $rs[$n] = count($v)===1?$v[0]:$this->getNewest(...$v);
        }
        ksort($rs);
        return $rs;
    }

    private function getNewest(string $v1, string $v2): string
    {
        return preg_replace('/\D/', '', $v1) > preg_replace('/\D/', '', $v2)?$v1:$v2;
    }

    private function getJson(string $path): array
    {
        if(!$content = file_get_contents($path)) {
            throw new \Exception($path.' does not exist.');            
        }
        $json = json_decode($content, true);
        $err = json_last_error();
        if ($err !== JSON_ERROR_NONE) {
            throw new \Exception('JSON ERROR: '.$err);
        }
        return $json;
    }

    private function getUnsupported (array &$json): array
    {
        $unsupported = array_diff_key($json, array_flip(self::FIELDS));
        $json = array_intersect_key($json, array_flip(self::FIELDS));
        return $unsupported;
    }
}