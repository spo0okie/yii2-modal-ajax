<?php

namespace ivankff\yii2ModalAjax;

use yii\web\AssetBundle;

class JqueryFormAsset extends AssetBundle
{
    public $sourcePath = '@npm/jquery-form';
    public $js = [
        YII_ENV_PROD ? 'dist/jquery.form.min.js' : 'src/jquery.form.js'
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
