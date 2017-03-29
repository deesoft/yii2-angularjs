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
    public $ngApp = true;
    public $options = [];
    public $varName;
    public $controllers = [];
    public $components = [];
    public $factories = [];
    public $services = [];
    public $filters = [];
    public $directives = [];
    public $preJs;
    public $preJsFile;
    public $postJs;
    public $postJsFile;
    public $depends = [];
    public $configs = [];
    public static $moduleAssets = [];
    protected static $registeredModules = [];

    public function init()
    {
        if ($this->name === null) {
            throw new InvalidConfigException('Property "' . get_called_class() . '::$name" is required.');
        }
        if ($this->varName === null) {
            $this->varName = Inflector::variablize($this->module);
        }
        if ($this->ngApp) {
            $this->options['ng-app'] = $this->ngApp === true ? $this->name : $this->ngApp;
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
        $view = $this->getView();
        $js = [];
        if ($this->preJs) {
            $js[] = $this->preJs;
        }
        if ($this->preJsFile) {
            $js[] = $view->render($this->preJsFile);
        }
        //
        $js[] = $this->renderFunctions();
        $js[] = $this->renderConfigs();
        $js[] = $this->renderComponents();

        //
        if ($this->postJs) {
            $js[] = $this->postJs;
        }
        if ($this->postJsFile) {
            $js[] = $view->render($this->postJsFile);
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
            if (isset($config['controller'])) {
                $script = $config['controller'];
            } elseif (isset($config['controllerFile'])) {
                $script = $view->render($config['controllerFile']);
            } else {
                $script = '';
            }
            $script .= "\nfunction registeredScript(){\n" . implode("\n", $registeredJs) . "\n}";
            if (empty($config['injection'])) {
                $js = new JsExpression("function(){\n{$script}\n}");
            } else {
                $js = (array) $config['injection'];
                $injectVar = implode(', ', $js);
                $js[] = new JsExpression("function({$injectVar}){\n{$script}\n}");
            }
            foreach (['templateFile', 'controllerFile', 'injection'] as $f) {
                unset($config[$f]);
            }
            $config['controller'] = $js;
            $config = Json::htmlEncode($config);
            $name = Json::htmlEncode(lcfirst(Inflector::id2camel($name)));
            $result[] = "{$this->varName}.component($name, $config);";
        }
        return implode("\n", $result);
    }

    protected function renderFunctions()
    {
        $parts = [
            'factories' => 'factory',
            'services' => 'service',
            'filters' => 'filter',
            'controllers' => 'controller',
            'directives' => 'directive',
        ];
        $result = [];
        $view = $this->getView();
        foreach ($parts as $part => $funcName) {
            foreach ($this->$part as $name => $function) {
                if (is_string($function)) {
                    $function = ['source' => $function];
                }
                if (isset($function['source'])) {
                    $script = $function['source'];
                } elseif (isset($function['sourceFile'])) {
                    $script = $view->render($function['sourceFile']);
                } else {
                    $script = '';
                }
                if (empty($function['injection'])) {
                    $js = "function(){\n{$script}\n}";
                } else {
                    $inject = (array) $function['injection'];
                    $injectVar = implode(', ', $inject);
                    $inject[] = new JsExpression("function({$injectVar}){\n{$script}\n}");
                    $js = Json::htmlEncode($inject);
                }
                $name = Json::htmlEncode($name);
                $result[] = "{$this->varName}.{$funcName}({$name}, $js);";
            }
        }
        return implode("\n", $result);
    }

    protected function renderConfigs()
    {
        $view = $this->getView();
        $for = [];
        $js = [];
        foreach ($this->configs as $function) {
            if (is_string($function)) {
                $function = ['source' => $function];
            }
            if (isset($function['source'])) {
                $script = $function['source'];
            } elseif (isset($function['sourceFile'])) {
                $script = $view->render($function['sourceFile']);
            } else {
                $script = '';
            }
            if (empty($function['injection'])) {
                $js[] = "(function(){\n{$script}\n})();";
            } else {
                $inject = (array) $function['injection'];
                $for = array_merge($for, $inject);
                $injectVar = implode(', ', $inject);
                $js[] = "(function({$injectVar}){\n{$script}\n})($injectVar);";
            }
        }
        $js = implode("\n", $js);
        if (empty($for)) {
            $script = "function(){\n{$js}\n}";
        } else {
            $inject = array_unique($for);
            $injectVar = implode(', ', $inject);
            $inject[] = new JsExpression("function({$injectVar}){\n{$js}\n}");
            $script = Json::htmlEncode($inject);
        }
        return "{$this->varName}.config($script);";
    }
}

Module::$moduleAssets = require 'assets.php';