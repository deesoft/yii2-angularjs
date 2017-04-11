<?php

namespace dee\angularjs;

use Yii;
use yii\web\View;
use yii\helpers\Json;
use yii\web\JsExpression;

/**
 * Description of NgRoute
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class NgRoute extends Module
{
    public $tag = 'ng-view';
    public $routes = [];
    public $html5Mode = false;
    public $baseUrl;

    public function init()
    {
        if ($this->tag !== 'ng-view') {
            $this->options['ng-view'] = true;
        }
        $this->depends[] = 'ngRoute';
        parent::init();
    }

    protected function renderConfigs()
    {
        return $this->renderRoutes() . "\n" . parent::renderConfigs();
    }

    protected function renderRoutes()
    {
        $view = $this->getView();
        $result = [];
        foreach ($this->routes as $name => $config) {
            if (is_string($config)) {
                $config = ['template' => $config];
            }
            $registeredJs = '';
            if (empty($config['template']) && isset($config['templateFile'])) {
                list($config['template'], $registeredJs) = $this->renderTemplate($config['templateFile']);
            }
            if (isset($config['controller']) || isset($config['controllerFile'])) {
                $script = isset($config['controller']) ? $config['controller'] : $view->render($config['controllerFile']);
                $script = $this->injectFunctionArgs($script);
                $registeredJs = "function registeredScript(){\n$registeredJs\n}";
                $script = $this->appendScript($script, $registeredJs);
            } elseif (isset($config['template'])) {
                list($config['template'], $script) = $this->extractController($config['template']);
                if (!empty($script)) {
                    $script = $this->injectFunctionArgs($script);
                    $registeredJs = "function registeredScript(){\n$registeredJs\n}";
                    $script = $this->appendScript($script, $registeredJs);
                } else {
                    $script = 'function(){}';
                }
            } else {
                $script = 'function(){}';
            }

            foreach (['templateFile', 'controllerFile'] as $f) {
                unset($config[$f]);
            }
            $config['controller'] = new JsExpression($script);
            if (!empty($config['resolve'])) {
                foreach ($config['resolve'] as $key => $value) {
                    $config['resolve'][$key] = new JsExpression($value);
                }
            }
            $config = Json::htmlEncode($config);
            if ($name === 'otherwise' || $name === '*') {
                $otherwise = "\$routeProvider.otherwise($config);";
            } else {
                $name = Json::htmlEncode($name);
                $result[] = "\$routeProvider.when($name, $config);";
            }
        }
        if (isset($otherwise)) {
            $result[] = $otherwise;
        }
        $result[] = '$locationProvider.html5Mode(' . json_encode($this->html5Mode) . ');';
        if ($this->html5Mode === true || !isset($this->html5Mode['enabled']) || $this->html5Mode['enabled'] != false) {
            $urlManager = Yii::$app->getUrlManager();
            $baseUrl = $urlManager->showScriptName ? $urlManager->getScriptUrl() : $urlManager->getBaseUrl() . '/';
            if ($this->baseUrl !== null) {
                if (strncmp($this->baseUrl, '/', 1) === 0) {
                    $baseUrl = $this->baseUrl;
                } else {
                    $baseUrl = rtrim($baseUrl, '/') . '/' . $this->baseUrl;
                }
                $jsBase = <<<JS
(function(){
    var e = document.createElement('base');
    e.setAttribute('href', '$baseUrl');
    document.querySelector('head').appendChild(e);
})();
JS;
                $view->registerJs($jsBase, View::POS_HEAD);
            }
        }

        $script = implode("\n", $result);
        return "{$this->varName}.config(['\$routeProvider','\$locationProvider',function(\$routeProvider,\$locationProvider){\n$script\n}]);";
    }
}
