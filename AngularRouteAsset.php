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
    public $js = [
        'https://ajax.googleapis.com/ajax/libs/angularjs/1.6.3/angular-route.min.js',
    ];
    public $depends = [
        'dee\angularjs\AngularAsset'
    ];
}
