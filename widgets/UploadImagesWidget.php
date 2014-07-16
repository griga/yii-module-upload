<?php

/** Created by griga at 25.01.14 | 0:04.
 *
 */
class UploadImagesWidget extends CWidget
{

    public $model;
    public $view = 'uploadImages';

    public $inputName = false;
    public $fieldName = false;

    public static $widgetCounter;
    public $counter;

    public $forceAssets = false;

    public function run()
    {
        if (empty(self::$widgetCounter))
            self::$widgetCounter = 1;
        else
            self::$widgetCounter++;
        $this->id = uniqid();

        if (!empty($this->fieldName)) {
            $this->view = "uploadImage";
        }

        if(!Yii::app()->request->isAjaxRequest || $this->forceAssets)
            self::registerAssets();
        $this->render($this->view, array(
            'model' => $this->model,
            'inputName' => $this->inputName,
            'fieldName' => $this->fieldName,
            'widgetCounter' => $this->counter ? : self::$widgetCounter,
        ));
    }

    public static function registerAssets()
    {


        $cs = Yii::app()->clientScript;
        $cs->registerCoreScript('jquery');
        $cs->registerCoreScript('jquery.ui');
        $assetsPath = __DIR__ . '/../vendor/assets';

        $url = Yii::app()->assetManager->publish($assetsPath);

        $cs->registerScriptFile($url . '/js/jquery.iframe-transport.js');
        $cs->registerScriptFile($url . '/js/load-image.min.js');
        $cs->registerScriptFile($url . '/js/jquery.fileupload.js');
        $cs->registerScriptFile($url . '/js/jquery.fileupload-process.js');
        $cs->registerScriptFile($url . '/js/jquery.fileupload-image.js');
        $cs->registerScriptFile($url . '/js/jquery.fileupload-validate.js');
        $cs->registerScriptFile($url . '/js/jquery.Jcrop.min.js');
        $cs->registerCssFile($url . '/css/bootstrap.fileupload.min.css');
        $cs->registerCssFile($url . '/css/jquery.fileupload.css');
        $cs->registerCssFile($url . '/css/jquery.Jcrop.min.css');
        $assetsPath = __DIR__ . '/../assets';
//        if (YII_DEBUG) {
//            $url = Yii::app()->assetManager->publish($assetsPath, false, -1, true);
//        } else {
            $url = Yii::app()->assetManager->publish($assetsPath);
//        }
        $cs->registerCssFile($url . '/css/upload.css');

    }
} 