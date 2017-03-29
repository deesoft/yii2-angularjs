<?php

namespace dee\angularjs;

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
        $this->renderRoutes();
        $this->depends[] = 'ngRoute';
        parent::init();
    }

    protected function renderRoutes()
    {
        $view = $this->getView();
        $result = [];
        foreach ($this->routes as $name => $config) {
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
        if ($this->html5Mode) {
            $result[] = '$locationProvider.html5Mode(true);';
            if ($this->baseUrl === null) {
                $this->baseUrl = \Yii::$app->homeUrl;
            }
            $view->registerJs("jQuery('head').append('<base href=\"{$this->baseUrl}\">');", \yii\web\View::POS_END);
        }
        $this->configs[] = [
            'source' => implode("\n", $result),
            'injection' => ['$routeProvider', '$locationProvider'],
        ];
    }
}
