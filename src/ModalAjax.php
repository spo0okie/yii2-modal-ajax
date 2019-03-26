<?php

namespace lo\widgets\modal;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/**
 * Class ModalAjax
 *
 * @package lo\widgets\modal
 * @author  Lukyanov Andrey <loveorigami@mail.ru>
 */
class ModalAjax extends Widget
{
    const MODE_SINGLE = 'id';
    const MODE_MULTI = 'multi';

    const BOOTSTRAP_VERSION_3 = 3;
    const BOOTSTRAP_VERSION_4 = 4;

    /**
     * events
     */
    const EVENT_BEFORE_SHOW = 'kbModalBeforeShow';
    const EVENT_MODAL_SHOW = 'kbModalShow';
    const EVENT_BEFORE_SUBMIT = 'kbModalBeforeSubmit';
    const EVENT_MODAL_SUBMIT = 'kbModalSubmit';
    const EVENT_MODAL_SUBMIT_COMPLETE = 'kbModalSubmitComplete';
    const EVENT_MODAL_SHOW_COMPLETE = 'kbModalShowComplete';

    /**
     * @var int Twitter Bootstrap version
     */
    public $bootstrapVersion = self::BOOTSTRAP_VERSION_3;
    /**
     * @var array config for Modal Widget
     * if `class` element is null, yii\bootstrap\Modal or yii\bootstrap4\Modal (according to $bootstrapVersion) will be used
     */
    public $modalWidgetConfig = [];
    /**
     * @var array
     */
    public $events = [];
    /**
     * @var string The selector to get url request when modal is opened for multi mode
     */
    public $selector;
    /**
     * @var string The url to request when modal is opened for single mode
     */
    public $url;
    /**
     * @var string reload pjax container after ajaxSubmit
     */
    public $pjaxContainer;
    /**
     * @var int timeout in milliseconds for pjax call
     */
    public $pjaxTimeout = 1000;
    /**
     * @var boolean Submit the form via ajax
     */
    public $ajaxSubmit = true;
    /**
     * @var boolean Submit the form via ajax
     */
    public $autoClose = false;
    /**
     * @var string
     * @see \yii\bootstrap\Modal
     */
    public $header;
    /**
     * @var array
     * @see \yii\bootstrap\Modal
     */
    public $headerOptions = [];
    /**
     * @var array
     * @see \yii\bootstrap\Modal
     */
    public $bodyOptions = ['class' => 'modal-body'];
    /**
     * @var string
     * @see \yii\bootstrap\Modal
     */
    public $footer;
    /**
     * @var array
     * @see \yii\bootstrap\Modal
     */
    public $footerOptions = [];
    /**
     * @var string
     * @see \yii\bootstrap\Modal
     */
    public $size;
    /**
     * @var array|false
     * @see \yii\bootstrap\Modal
     */
    public $closeButton = [];
    /**
     * @var array|false
     * @see \yii\bootstrap\Modal
     */
    public $toggleButton = false;
    /**
     * @var array
     * @see \yii\bootstrap\Widget
     */
    public $options = [];
    /**
     * @var array
     * @see \yii\bootstrap\BootstrapWidgetTrait
     */
    public $clientOptions = [];
    /**
     * @var array
     * @see \yii\bootstrap\BootstrapWidgetTrait
     */
    public $clientEvents = [];

    /**
     * @var string
     */
    protected $mode = self::MODE_SINGLE;
    /**
     * @var \yii\bootstrap\Modal|\yii\bootstrap4\Modal|null
     */
    protected $_modal = null;

    /**
     * @inheritdocs
     */
    public function init()
    {
        parent::init();

        if ($this->selector) {
            $this->mode = self::MODE_MULTI;
        }

        $this->modalWidgetConfig = ArrayHelper::merge([
            'class' => $this->_isBs4() ? 'yii\bootstrap4\Modal' : 'yii\bootstrap\Modal',
            'id' => $this->getId(false),
            'headerOptions' => $this->headerOptions,
            'bodyOptions' => $this->bodyOptions,
            'footer' => $this->footer,
            'footerOptions' => $this->footerOptions,
            'size' => $this->size,
            'closeButton' => $this->closeButton,
            'toggleButton' => $this->toggleButton,
            'options' => $this->options,
            'clientOptions' => $this->clientOptions,
            'clientEvents' => $this->clientEvents,
        ], $this->modalWidgetConfig);

        if ($this->_isBs4()) {
            $this->modalWidgetConfig = ArrayHelper::merge([
                'title' => $this->header,
            ], $this->modalWidgetConfig);
        } else {
            $this->modalWidgetConfig = ArrayHelper::merge([
                'header' => Html::tag('span', $this->header, ['class' => 'modal-title']),
            ], $this->modalWidgetConfig);
        }

        $this->_modal = \Yii::createObject($this->modalWidgetConfig);
    }

    /**
     * @inheritdocs
     */
    public function run()
    {
        $this->_modal->run();

        /** @var View */
        $view = $this->getView();
        $id = $this->_modal->options['id'];

        ModalAjaxAsset::register($view);

        if (!$this->url && !$this->selector) {
            return;
        }

        switch ($this->mode) {
            case self::MODE_SINGLE:
                $this->registerSingleModal($id, $view);
                break;

            case self::MODE_MULTI:
                $this->registerMultiModal($id, $view);
                break;
        }

        if (!isset($this->events[self::EVENT_MODAL_SUBMIT])) {
            $this->defaultSubmitEvent();
        }

        $this->registerEvents($id, $view);
    }

    /**
     * @param      $id
     * @param View $view
     */
    protected function registerSingleModal($id, $view)
    {
        $url = is_array($this->url) ? Url::to($this->url) : $this->url;

        $view->registerJs("
            jQuery('#$id').kbModalAjax({
                url: '$url',
                ajaxSubmit: ".($this->ajaxSubmit ? "true" : "false")."
            });
        ");
    }

    /**
     * @param      $id
     * @param View $view
     */
    protected function registerMultiModal($id, $view)
    {
        $view->registerJs("
            jQuery('body').on('click', '$this->selector', function(e) {
                e.preventDefault();
                $(this).attr('data-toggle', 'modal');
                $(this).attr('data-target', '#$id');
                
                var bs_url = $(this).attr('href');
                var title = $(this).attr('title');
                
                if (!title) title = ' ';
                
                jQuery('#$id').find('.modal-title').html(title);
                
                jQuery('#$id').kbModalAjax({
                    selector: $(this),
                    url: bs_url,
                    ajaxSubmit: $this->ajaxSubmit
                });
            });
        ");
    }

    /**
     * register pjax event
     */
    protected function defaultSubmitEvent()
    {
        $expression = [];

        if ($this->autoClose) {
            $expression[] = "$(this).modal('toggle');";
        }

        if ($this->pjaxContainer) {
            $expression[] = "$.pjax.reload({container : '$this->pjaxContainer', timeout : $this->pjaxTimeout });";
        }

        $script = implode("\r\n", $expression);

        $this->events[self::EVENT_MODAL_SUBMIT] = new JsExpression("
                function(event, data, status, xhr) {
                    if(status){
                        $script
                    }
                }
            ");
    }

    /**
     * @param      $id
     * @param View $view
     */
    protected function registerEvents($id, $view)
    {
        $js = [];
        foreach ($this->events as $event => $expression) {
            $js[] = ".on('$event', $expression)";
        }

        if ($js) {
            $script = "jQuery('#$id')" . implode("\r\n", $js);
            $view->registerJs($script);
        }
    }

    /**
     * @return bool
     */
    private function _isBs3()
    {
        return $this->bootstrapVersion === self::BOOTSTRAP_VERSION_3;
    }

    /**
     * @return bool
     */
    private function _isBs4()
    {
        return $this->bootstrapVersion === self::BOOTSTRAP_VERSION_4;
    }

}
