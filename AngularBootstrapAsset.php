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
    public $js = [
        'https://angular-ui.github.io/bootstrap/ui-bootstrap-tpls-2.5.0.min.js',
    ];
    public $depends = [
        'dee\angularjs\AngularAsset'
    ];
}
