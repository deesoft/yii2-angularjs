<?php

namespace dee\angularjs;

use yii\helpers\Json;

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
        $this->configs[] = [
            'source' => implode("\n", $result),
            'injection' => ['$routeProvider'],
        ];
    }
}
