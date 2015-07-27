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

    public function getSpec($yo)
    {
        $routes = $yo->getRoutes();
        $annotation = \PMVC\plug('annotation');
        $paths = $this->get('paths');
        foreach ($routes as $r) {
            $uri =$r['uri'];
            $method = strtolower($r['method']);
            $doc = $annotation->get($r['action']);
            $this->parseDefinitions($doc);
            if (!empty($doc['parameters'])) {
                $doc['parameters'] =  $this->parseParameters($doc);
            }
            $paths[$uri][$method]->mergeDefault($doc);
        }
        return $this->gen();
    }

    function parseParameters($doc)
    {
        $doc['parameters'] = $doc->parseDataTypes($doc['parameters'],'example');
        $parameters = new swagger\parameters();
        foreach($doc['parameters'] as $param){
            $parameter = new swagger\parameter();
            $parameter->mergeDefault($param);
            $parameters[] = $parameter; 
        }
        return $parameters;
    }

    function parseDefinitions($doc)
    {
        if (empty($doc)) {
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
        $definition->mergeDefault($arr);
        return $arr;
    }
}
