<?php

namespace dee\angularjs;

/**
 * Description of AngularAsset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AngularRouteAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/angular-route';
    public $js = [
        'angular-route.min.js',
    ];
    public $depends = [
        'dee\angularjs\AngularAsset'
    ];
}
