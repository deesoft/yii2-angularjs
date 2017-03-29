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
    public $sourcePath = '@bower/angular';
    public $js = [
        'angular.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
