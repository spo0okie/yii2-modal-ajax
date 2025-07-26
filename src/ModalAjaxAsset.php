<?php

namespace spo0okie\yii2ModalAjax;

use yii\web\AssetBundle;

/**
 * Class ModalAjaxAsset
 * @author Lukyanov Andrey <loveorigami@mail.ru>
 */
class ModalAjaxAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/kb-modal-ajax.js'
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/modal-colors.css',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . "/assets";
        parent::init();
    }
}
