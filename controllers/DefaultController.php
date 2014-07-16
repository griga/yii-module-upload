<?php

class DefaultController extends Controller
{
    public function filters()
    {
        return array(
            'accessControl',
        );
    }

    public function accessRules()
    {
        return array(
            array('allow', 'roles' => array('admin', 'moderator')),
            array('allow', 'actions' => array('upload')),
        );
    }

    /**
     *
     */
    public function actionUpload()
    {
        if (isset($_POST['entity']) and is_subclass_of($_POST['entity'], 'CActiveRecord')) {
            foreach ($_FILES['files']['name'] as $index => $name)
                $_FILES['files']['name'][$index] = uniqid();

            $folder = UploadService::getUploadFolder($_POST['entity']);
            require_once __DIR__ . '/../vendor/UploadHandler.php';

            $handler = new UploadHandler(array(
                'upload_dir' => Yii::app()->getBasePath() . '/../images/' . $folder,
                'upload_url' => Yii::app()->baseUrl . '/images/' . $folder,
                'image_file_types' => '/\.(gif|jpe?g|png)$/i',
            ), false);
            $out = $handler->post(false);
            $webroot = realpath(Yii::getPathOfAlias('webroot'));
            $typesToResize = array(); // 'image/jpeg' etc
            foreach ($out['files'] as $index => $file) {
                if (!isset($file->error)) {
                    if (in_array($file->type, $typesToResize) && isset($file->mediumUrl) && file_exists($webroot . $file->mediumUrl)) {
                        @rename($webroot . $file->mediumUrl, $webroot . '/images/' . $folder . $file->name);
                        @unlink($webroot . $file->url);
                        $out['files'][$index]->url = '/images/' . $folder . $file->name;
                    }
                    UploadService::createCopyToDataRoot($file->url);
                    $upload = new Upload();
                    $upload->entity = $_POST['entity'];
                    $upload->entity_id = $_POST['entity_id'] ? : $_POST['widgetCounter'];
                    $upload->user_id = user()->id;
                    $upload->filename = '/'.ltrim($file->url, '/');
                    if ($upload->save()) {
                        $file->id = $upload->id;
                        UploadService::updateDefaultUploadField($upload->id, $_POST);
                    }
                }
            }
            $handler->generate_response($out);
        }
        Yii::app()->end();
    }

    public function actionDelete()
    {
        $out = array();
        if (isset($_POST['Upload']['id'])) {
            $upload = Upload::model()->checkAccess()->findByPk($_POST['Upload']['id']);
            if ($upload && $upload->delete()) {
                $out['success'] = 'Изображение удалено успешно';
            } else {
                $out['error'] = 'Ошибка. Файл не найден';
            }
        } else {
            $out['error'] = 'Ошибка. Не правильный запрос';
        }
        $this->renderJson($out);
    }

    /**
     *
     */
    public function actionEdit()
    {
        if (isset($_POST['Upload']['id'])) {
            /** @var Upload $upload */
            $upload = Upload::model()->checkAccess()->findByPk($_POST['Upload']['id']);
            if (isset($_POST['Upload']['crop'])) {
                $filePath = $upload->getFilePath();
                if (file_exists($filePath)) {
                    /** @var EThumbnail $crop */
                    $crop = $upload->getPhpThumb()->create($filePath);
                    $size = getimagesize($filePath);
                    $aspect = $size[0] / $_POST['Upload']['crop']['webWidth'];
                    $crop->crop(
                        $_POST['Upload']['crop']['x'] * $aspect,
                        $_POST['Upload']['crop']['y'] * $aspect,
                        $_POST['Upload']['crop']['w'] * $aspect,
                        $_POST['Upload']['crop']['h'] * $aspect);
                    $crop->save($filePath);
                }
            }
            $meta = array(
                'alt' => $_POST['Upload']['alt'] ? : '',
                'caption' => $_POST['Upload']['caption'] ? : '',
                'title' => $_POST['Upload']['title'] ? : '',
            );
            $upload->meta = json_encode($meta);
            $upload->save();
        }
        if (isset($_GET['id'])) {
            /** @var Upload $upload */
            $upload = Upload::model()->checkAccess()->findByPk($_GET['id']);
            if ($upload) {
                $this->renderJson($upload->meta);
            } else {
                $this->renderJson('{}');
            }
        }
    }


    public function actionSort()
    {
        if (isset($_POST['uploads'])) {
            $command = Yii::app()->db->createCommand();
            if (count($_POST['uploads']) > 0) {
                UploadService::handleSortDefaultUpload($_POST['uploads'][0]);
            }

            foreach ($_POST['uploads'] as $index => $id) {
                if (user()->checkAccess('admin') || user()->checkAccess('moderator'))
                    $command->reset()->update(Upload::model()->tableName(), array(
                        'sort' => $index
                    ), 'id = :id', array(':id' => $id));
                else
                    $command->reset()->update(Upload::model()->tableName(), array(
                        'sort' => $index
                    ), 'id = :id AND user_id = :uid', array(':id' => $id, ':uid' => user()->id));
            }

        }
    }


    private function renderJson($data)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        if (is_array($data)) {
            echo CJavaScript::jsonEncode($data);
        } else {
            echo $data;
        }
        Yii::app()->end();
    }
}