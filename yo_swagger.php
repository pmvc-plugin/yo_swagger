<?php
namespace PMVC\PlugIn\yo_swagger;

// \PMVC\l(__DIR__.'/xxx.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\yo_swagger';

class yo_swagger extends \PMVC\PlugIn
{
    public $swagger;

    public function setInfo($title, $version='1.0')
    {
        $this->swagger['info'] = array(
            'title'=>$title,
            'version'=>$version
        );
        return $this;
    }

    public function setUrl($url)
    {
        $uri = parse_url($url);
        $this->swagger['host']=$uri['host'];
        $this->swagger['schemes']=array($uri['scheme']);
        $this->swagger['basePath']=$uri['path'];
        return $this;
    }

    /**
     *    {
     *        type: "apiKey",
     *        name: "api_key",
     *        in: "header"
     *    }
     */
    public function setSecurity($key,$arr)
    {
        $this->swagger['securityDefinitions'][$key] = $arr;
        return $this;
    }

    public function getSpec($yo)
    {
        $routes = $yo->getRoutes();
        $arr = array(
            'swagger'=>'2.0',
            'consumes'=>array(
                'application/json'
            ),
            'produces'=>array(
                'application/json'
            ),
            'paths'=>array(),
            'definitions'=>array()
        );
        $paths = \PMVC\plug('swagger')->get('paths');
        $annotation = \PMVC\plug('annotation');
        foreach ($routes as $r) {
            $uri =$r['uri'];
            $method = strtolower($r['method']);
            if (!isset($root['paths'][$uri])){ 
                $arr['paths'][$uri] = array();
            }
            $doc = $annotation->get($r['action']);
            $arr['definitions'] = \PMVC\array_merge(
                $arr['definitions'],
                $this->parseDefinitions($doc)
            );
            $default = array(
                'description'=>null
                ,'parameters'=>null
                ,'security'=> null 
                ,'responses'=> array ( 
                    '200' => array(
                        'description'=>'success'
                    )
                )
                ,'summary'=> null
                ,'tags'=> null
            );
            $default = \PMVC\mergeDefault($default,$doc);
            foreach ($default as $defk=>$defv) {
                if (empty($default[$defk])) {
                    unset($default[$defk]);
                }
            }
            if (!empty($default['parameters'])) {
                $default['parameters'] = $doc->parseDataTypes($default['parameters'],'example');
            }
            if (!empty($default['security'])) {
                $default['security'] = \PMVC\toArray($default['security']);
            }
            $arr['paths'][$uri][$method] = $default;
        }
        if (empty($arr['definitions'])) {
            unset($arr['definitions']);
        } else {
            $arr = $this->parseParametersType($arr);
        }
        $arr = \PMVC\array_merge($arr,$this->swagger);
        return $arr;
    }

    function parseParametersType($arr)
    {
        foreach ($arr['paths'] as &$methods) {
            foreach ($methods as &$method) {
                if (empty($method['parameters'])) {
                    continue;
                }
                foreach ($method['parameters'] as &$param) {
                    if (isset($arr['definitions'][$param['type']])) {
                        $param['schema'] = array(
                            '$ref'=>'#/definitions/'.$param['type']
                        );
                        unset($param['type']);
                    }
                }
            }
        }
        return $arr;
    }
    
    function parseDefinitions($doc)
    {
        $arr = array();
        if (empty($doc)) {
            return;
        }
        foreach ($doc as $k=>$v) {
            if(0 === strpos($k,'definitions')){
                $keys = explode('-',$k);
                $arr[$keys[1]] = $this->getDefinitionsKeys(
                    $keys[1]
                    ,$doc
                );
            }
        }
        return $arr;
    }

    function getDefinitionsKeys($groupid,$doc){
        $keys = array ('required','properties');
        $default = array();
        foreach($keys as $k){
            $default[$groupid.'-'.$k] = '';
        }
        $default = \PMVC\mergeDefault($default,$doc);
        $arr = array();
        foreach($keys as $k){
            if (!empty($default[$groupid.'-'.$k])) {
                $arr[$k] = $default[$groupid.'-'.$k];
            }
        }
        $properties = $doc->parseDataTypes($arr['properties'],'example');
        $properties_arr = array();
        foreach ($properties as $v) {
           $name = $v['name'];
           unset($v['name']);
           $properties_arr[$name] = $v; 
        }
        $arr['properties'] = $properties_arr;
        return $arr;
    }
}
