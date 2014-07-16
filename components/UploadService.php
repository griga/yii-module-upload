<?php
/** Created by griga at 19.06.2014 | 16:19.
 * 
 */

class UploadService {

    public static function updateDefaultUploadField($uploadId, $options)
    {
        $field = self::getDefaultUploadField($options['entity']);
        if ($field) {
            /** @var CActiveRecord $activeRecord */
            $activeRecord = $options['entity'];
            $model = $activeRecord::model()->findByPk($options['entity_id']);
            if ($model && (!$model->$field || (isset($options['force']) && $options['force']))) {
                if ($uploadId) {
                    db()->createCommand()->update($activeRecord::model()->tableName(), array(
                        $field => $uploadId
                    ), 'id=:id', array(':id' => $options['entity_id']));
                } else {
                    db()->createCommand()->update($activeRecord::model()->tableName(), array(
                        $field => new CDbExpression('NULL'),
                    ), 'id=:id', array(':id' => $options['entity_id']));
                }
            }
        }
    }


    /**
     * @param CActiveRecord $class
     * @return mixed
     */
    public static function getDefaultUploadField($class)
    {
        if ($class == 'Upload') {
            return false;
        }
        foreach ($class::model()->behaviors() as $key => $behavior) {
            if (strpos($behavior['class'], 'UploadBehavior') !== false && isset($behavior['defaultUploadField']))
                return $behavior['defaultUploadField'];

        }
        return false;
    }

    /**
     * @param CActiveRecord $class
     * @return string
     */

    public static function getUploadFolder($class)
    {
        $folder = '';
        if ($class == 'Upload') {
            return 'uploads/';
        }
        foreach ($class::model()->behaviors() as $key => $behavior) {
            if (strpos($behavior['class'], 'UploadBehavior') != false)
                $folder = $behavior['folder'] . '/';

        }
        return $folder;
    }

    /**
     * @param Upload $upload
     */
    public static function handleDefaultUploadAfterDelete($upload)
    {
        /** @var CActiveRecord $activeRecord */
        $activeRecord = $upload->entity;

        $activeRecord::model()->findByPk($upload->entity_id);
        $defaultUploadId = db()->createCommand()->select('id')->from(Upload::model()->tableName())->where(
            'entity=:e and entity_id=:eid', array(
                ':e' => $upload->entity,
                ':eid' => $upload->entity_id,
            ))->order('sort')->queryScalar();

        self::updateDefaultUploadField($defaultUploadId, array(
            'entity' => $upload->entity,
            'entity_id' => $upload->entity_id,
            'force' => true,
        ));
    }

    public static function handleSortDefaultUpload($uploadId)
    {
        if (user()->checkAccess('admin') || user()->checkAccess('moderator')) {
            $upload = db()->createCommand()->select('*')->from(Upload::model()->tableName())
                ->where('id = :id', array(':id' => $uploadId))->queryRow();
        } else {
            $upload = db()->createCommand()->select('*')->from(Upload::model()->tableName())
                ->where('id = :id AND user_id = :uid', array(':id' => $uploadId, ':uid' => user()->id))->queryRow();
        }
        if ($upload) {
            self::updateDefaultUploadField($uploadId, array(
                'entity' => $upload['entity'],
                'entity_id' => $upload['entity_id'],
                'force' => true,
            ));
        }
    }


    public static function createCopyToDataRoot($filename ){
        $webroot = realpath(Yii::getPathOfAlias('webroot'));
        $dataroot = Yii::app()->params['dataDir'];
        if ($webroot !== $dataroot) {
            $dirname = dirname($dataroot.$filename);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0755, true);
            }
            @copy($webroot . $filename, $dataroot . $filename);
        }
    }
}