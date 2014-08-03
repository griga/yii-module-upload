<?php

/** Created by griga at 25.01.14 | 2:25.
 *
 * @property Upload $defaultPicture
 * @property Upload[] $uploads
 */
class UploadBehavior extends CActiveRecordBehavior
{

    public $defaultPictureUrl = 'images/ejik_grey.png';
    public $defaultUploadField;
    public $folder = '';

    private $cache;

    /**
     * @return Upload[]
     */
    public function getUploads()
    {
        if (!is_array($this->cache)) {
            if (!$this->owner->isNewRecord) {
                $criteria = $this->getCriteria();
                $criteria->compare('entity_id', $this->owner->id);
                $this->cache = Upload::model()->findAll($criteria);
            } else if ($this->owner->isNewRecord && isset($_POST[get_class($this->owner)]['uploads'])) {
                $criteria = $this->getCriteria();
                $criteria->addInCondition('id', $_POST[get_class($this->owner)]['uploads']);
                $this->cache = Upload::model()->findAll($criteria);
            } else {
                $this->cache = array();
            }
        }
        return $this->cache;
    }

    public function getDefaultPicture()
    {

        if (isset($this->owner->defaultUpload)) {
            $upload = $this->owner->defaultUpload;
        } else {
            $upload = new Upload();
            $upload->filename = $this->defaultPictureUrl;
        }

        return $upload;
    }

    public function afterDelete($event)
    {
        foreach ($this->getUploads() as $upload)
            $upload->delete();
    }

    public function afterSave($event)
    {
        if ($this->owner->isNewRecord && isset($_POST[get_class($this->owner)]['uploads'])) {
            $uploads = $_POST[get_class($this->owner)]['uploads'];
            UploadService::updateDefaultUploadField($uploads[0], array(
                'entity' => get_class($this->owner),
                'entity_id' => $this->owner->primaryKey,
            ));
            $command = Yii::app()->db->createCommand();
            foreach ($uploads as $index => $id)
                $command->reset()->update(Upload::model()->tableName(), array(
                    'entity_id' => $this->owner->primaryKey
                ), 'id = :id', array(':id' => $id));
        }
    }


    /**
     * @return CDbCriteria
     */
    private function getCriteria()
    {
        $criteria = new CDbCriteria();
        $criteria->order = 'sort';

        $criteria->compare('entity', get_class($this->owner));
        return $criteria;
    }

    public function attach($owner)
    {
        if ($this->defaultUploadField) {
            $owner->metaData->addRelation(
                'defaultUpload', array(CActiveRecord::BELONGS_TO, 'Upload', $this->defaultUploadField)
            );
        } else {
            $owner->metaData->addRelation(
                'defaultUpload', array(CActiveRecord::HAS_ONE, 'Upload', 'entity_id', 'condition' => 'defaultUpload.entity="' . get_class($owner) . '"', 'group' => 'defaultUpload.entity_id')
            );
        }

        parent::attach($owner);
    }


}