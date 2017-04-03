<?php

namespace dee\angularjs;

/**
 * Description of AngularAsset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AngularAsset extends \yii\web\AssetBundle
{
    public $js = [
        'https://ajax.googleapis.com/ajax/libs/angularjs/1.6.3/angular.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
