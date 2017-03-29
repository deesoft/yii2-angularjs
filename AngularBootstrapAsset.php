<?php

namespace dee\angularjs;

/**
 * Description of AngularAsset
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AngularBootstrapAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/angular-bootstrap';
    public $js = [
        'ui-bootstrap.min.js',
    ];
    public $depends = [
        'dee\angularjs\AngularAsset'
    ];
}
