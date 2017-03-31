<?php

namespace dee\angularjs;

use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;
use yii\helpers\Inflector;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

/**
 * Description of Angular
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Module extends \yii\base\Widget
{
    public $name;
    public $tag = 'div';
    public $options = [];
    public $varName;
    public $controllers = [];
    public $components = [];
    public $factories = [];
    public $services = [];
    public $filters = [];
    public $directives = [];
    public $values = [];
    public $preJs;
    public $preJsFile;
    public $postJs;
    public $postJsFile;
    public $depends = [];
    public $run;
    public $config;
    public static $moduleAssets = [];
    protected static $registeredModules = [];
    protected $js = [];

    public function init()
    {
        if ($this->name === null) {
            throw new InvalidConfigException('Property "' . get_called_class() . '::$name" is required.');
        }
        if ($this->varName === null) {
            $this->varName = Inflector::variablize($this->name);
        }
        echo Html::beginTag($this->tag, $this->options);
    }

    public function run()
    {
        $view = $this->getView();
        $js = $this->renderJsAll();

        $sName = Json::htmlEncode($this->name);
        $depends = empty($this->depends) ? '[]' : Json::htmlEncode(array_unique($this->depends));

        // register asset and dependencies
        AngularAsset::register($view);
        $am = $view->getAssetManager();
        foreach ($this->depends as $moduleName) {
            if (isset(static::$moduleAssets[$moduleName]) && !isset(static::$registeredModules[$moduleName])) {
                $asset = static::$moduleAssets[$moduleName];
                if (is_string($asset)) {
                    if (class_exists($asset)) {
                        $asset::register($view);
                    } else {
                        $view->registerJsFile(\Yii::getAlias($asset), [
                            'depends' => ['dee\angularjs\AngularAsset']
                        ]);
                    }
                } elseif (!isset($am->bundles[$moduleName])) {
                    $am->bundles = \Yii::createObject(array_merge($asset, [
                            'class' => 'yii\web\AssetBundle',
                    ]));
                    $view->registerAssetBundle($moduleName);
                }
                static::$registeredModules[$moduleName] = true;
            }
        }


        $js = <<<JS
var {$this->varName} = (function(angular){
    var {$this->varName} = angular.module($sName, $depends);
    $js
    return {$this->varName};
})(window.angular);
JS;
        $view->registerJs($js, View::POS_END);
        echo Html::endTag($this->tag);
    }

    protected function renderJsAll()
    {
        $js = [];
        $js[] = $this->renderPreJs();
        $js[] = $this->renderServices();
        $js[] = $this->renderConfigs();
        $js[] = $this->renderComponents();
        $js[] = $this->renderPostJs();
        return implode("\n", $js);
    }

    protected function injectFunctionArgs($func)
    {
        if (preg_match('/^\s*function[^(]*\(\s*([^)]*)\)/m', $func, $matches)) {
            if (!empty($matches[1])) {
                $args = preg_split('/\s*,\s*/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
                $args[] = new JsExpression($func);
                return Json::htmlEncode($args);
            }
        }
        return $func;
    }

    protected function appendScript($func, $added)
    {
        return preg_replace('/(function[^{]*\{)/m', "$1\n$added\n", $func, 1);
    }

    protected function renderPreJs()
    {
        $js = [];
        if ($this->preJs) {
            $js[] = $this->preJs;
        }
        if ($this->preJsFile) {
            $js[] = $this->getView()->render($this->preJsFile);
        }
        return implode("\n", $js);
    }

    protected function renderPostJs()
    {
        $js = [];
        if ($this->postJs) {
            $js[] = $this->postJs;
        }
        if ($this->postJsFile) {
            $js[] = $this->getView()->render($this->postJsFile);
        }
        return implode("\n", $js);
    }

    protected function renderComponents()
    {
        $view = $this->getView();
        $result = [];
        foreach ($this->components as $name => $config) {
            if (is_string($config)) {
                $config = ['template' => $config];
            }
            $registeredJs = [];
            if (empty($config['template']) && isset($config['templateFile'])) {
                $oldJs = $view->js;
                $view->js = [];
                $config['template'] = $view->render($config['templateFile']);
                foreach ($view->js as $pieces) {
                    $registeredJs[] = implode("\n", $pieces);
                }
                $view->js = $oldJs;
            }

            if (isset($config['controller']) || isset($config['controllerFile'])) {
                $script = isset($config['controller']) ? $config['controller'] : $view->render($config['controllerFile']);
                $script = $this->injectFunctionArgs($script);
                $registeredJs = "function registeredScript(){\n" . implode("\n", $registeredJs) . "\n}";
                $script = $this->appendScript($script, $registeredJs);
            } else {
                $script = 'function(){}';
            }
            foreach (['templateFile', 'controllerFile'] as $f) {
                unset($config[$f]);
            }
            $config['controller'] = new JsExpression($script);
            $config = Json::htmlEncode($config);
            $name = Json::htmlEncode(lcfirst(Inflector::id2camel($name)));
            $result[] = "{$this->varName}.component($name, $config);";
        }
        return implode("\n", $result);
    }

    protected function renderServices()
    {
        $parts = [
            'factories' => 'factory',
            'services' => 'service',
            'filters' => 'filter',
            'controllers' => 'controller',
            'directives' => 'directive',
            'values' => 'value',
        ];
        $view = $this->getView();
        $result = [];
        foreach ($parts as $part => $funcName) {
            foreach ($this->$part as $name => $function) {
                if ($part === 'values') {
                    $script = json_encode($function);
                } else {
                    if (is_string($function)) {
                        $function = ['source' => $function];
                    }
                    if (isset($function['source']) || isset($function['sourceFile'])) {
                        $script = isset($function['source']) ? $function['source'] : $view->render($function['sourceFile']);
                        $script = $this->injectFunctionArgs($script);
                    } else {
                        $script = 'function(){}';
                    }
                }
                $name = Json::htmlEncode($name);
                $result[] = "{$this->varName}.{$funcName}({$name}, $script);";
            }
        }
        return implode("\n", $result);
    }

    protected function renderConfigs()
    {
        $view = $this->getView();
        $parts = ['config', 'run'];
        $result = [];
        foreach ($parts as $part) {
            $function = $this->$part;
            if (empty($function)) {
                continue;
            }
            if (isset($function['source'])) {
                $function = $function['source'];
            }elseif (isset ($function['sourceFile'])) {
                $function = $view->render($function['sourceFile']);
            }
            $script = $this->injectFunctionArgs($function);
            $result[] = "{$this->varName}.{$part}($script);";
        }
        return implode("\n", $result);
    }
}

Module::$moduleAssets = require 'assets.php';
