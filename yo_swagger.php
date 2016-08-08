<?php
namespace PMVC\PlugIn\yo_swagger;
use PMVC\PlugIn\swagger;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\yo_swagger';


\PMVC\initPlugin(array('swagger'=>null));

class yo_swagger extends swagger\swagger 
{

    public function init()
    {
        $this->swagger =\PMVC\plug('swagger')->get();
    }

    public function fromYo($yo)
    {
        $routes = $yo->getRoutes();
        return $this->getSpec($routes);
    }

    public function fromMapping($mappings)
    {
        $actions = $mappings->addByKey(
            \PMVC\ACTION_MAPPINGS
        );
        $routes = [];
        $c = \PMVC\plug('controller');
        foreach ($actions as $key=>$action) {
            $routes[] = [
                'uri'=>$key,
                'action'=>$c->getActionCall(
                    $mappings->findMapping($key)
                ),
                'method'=>'get'
            ];
        }
        return $this->getSpec($routes);
    }

    public function getSpec($routes)
    {
        $annotation = \PMVC\plug('annotation');
        $paths = $this->get('paths');
        foreach ($routes as $r) {
            $uri =$r['uri'];
            $method = strtolower($r['method']);
            $doc = $annotation->get($r['action']);
            $this->parseDefinitions($doc);
            $this->parseTags($doc);
            if (!empty($doc['parameters'])) {
                $doc['parameters'] =  $this->parseParameters($doc);
            }
            $doc['tags'] = str_replace('-',' ',$doc['tags']);
            $paths[$uri][$method]->mergeDefault($doc);
        }
        return $this->gen();
    }

    function parseParameters($doc)
    {
        $dataTypes = $doc->getDataType('parameters','description');
        $parameters = new swagger\parameters();
        foreach($dataTypes as $param){
            $parameter = new swagger\parameter();
            $parameter->mergeDefault($param);
            $parameters[] = $parameter; 
        }
        return $parameters;
    }

    function parseTags($doc)
    {
        if (empty($doc)) {
            return;
        }
        foreach ($doc as $k=>$v) {
            if(0 === strpos($k,'tags-')){
                $tags = $this->get('tags');
                $tag = new swagger\tag();
                $arr = array(
                    'name'=>$this->getTagId($k),
                    'description'=>$v
                );
                $tag->mergeDefault($arr);
                $tags[]=$tag;
            }
        }
    }

    function getTagId($k)
    {
        $k = explode('-',$k);
        array_shift($k);
        return join(' ',$k);
    }

    function parseDefinitions($doc)
    {
        if (empty($doc) || !count($doc)) {
            return;
        }
        foreach ($doc as $k=>$v) {
            if(0 === strpos($k,'definitions')){
                $keys = explode('-',$k);
                $this->getDefinitionsKeys(
                    $keys[1]
                    ,$doc
                );
            }
        }
    }

    function getDefinitionsKeys($groupid,$doc){
        $definitions = $this->get('definitions');
        $definition =  $definitions[$groupid];
        $keys = array_keys($definition->getDefault());
        $default = array();
        foreach($keys as $k){
            $default[$groupid.'-'.$k] = '';
        }
        $default = array_replace($default,$doc);
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
        $definition->mergeDefault($arr);
        return $arr;
    }
}
