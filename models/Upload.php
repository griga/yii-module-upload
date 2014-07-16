<?php

/**
 * This is the model class for table "{{upload}}".
 *
 * The followings are the available columns in table '{{upload}}':
 * @property integer $id
 * @property string $entity
 * @property integer $entity_id
 * @property integer $user_id
 * @property string $filename
 * @property string $meta
 * @property integer $sort
 * @property string $create_time
 * @property string $update_time
 */

class Upload extends CActiveRecord
{

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{upload}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('entity, entity_id, filename, user_id', 'required'),
            array('entity_id, user_id, sort', 'numerical', 'integerOnly'=>true),
            array('entity, filename', 'length', 'max'=>255),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'entity' => 'Entity',
            'entity_id' => 'Entity',
            'user_id' => 'User',
            'filename' => 'Filename',
            'meta' => 'Meta',
            'sort' => 'Order',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        );
    }

    public function behaviors()
    {
        return array(
            'CTimestampBehavior' => array(
                'class' => 'zii.behaviors.CTimestampBehavior',
                'timestampExpression'=> new CDbExpression( DbHelper::timestampExpression()),
                'setUpdateOnCreate'=>true,
            ),
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Upload the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getFilePath(){
        return $this->filePathFromName($this->filename);
    }

    public function filePathFromName($name){
        $name = '/' . ltrim($name, '/');
        $dataroot = Yii::app()->params['dataDir'];
        $webroot = realpath(YiiBase::getPathOfAlias('webroot'));

        $filename = preg_replace('/(\.tmb\/|_\d+x\d+)/','',$name);


        if($dataroot !== $webroot && !file_exists($webroot.$name)){
            $filedir = preg_replace('/[\w\d\.]+$/','',$webroot.$name);
            if(!is_dir($filedir))
                mkdir($filedir, 0755, true);
            copy($dataroot.$filename, $webroot.$filename);
        }

        if(!file_exists($webroot.$name) && strpos($name,'.tmb') !== false){
            preg_match('~_(\d+)x(\d+)~',$name, $matches);
            $thumb = Yii::app()->phpThumb->create($webroot.$filename);
            $thumb->resize($matches[1], $matches[2]);
            $thumb->pad($matches[1], $matches[2]);
            $thumb->save($webroot.$name);
            return $webroot.$name ;
        } else {
            return $webroot.$filename ;
        }

    }


    /**
     * @param SplFileInfo $splFile
     * @return string
     */
    public function getThumbDirPath($splFile)
    {
        $thumbDir = $splFile->getPath() . '/.tmb/';
        if (!is_dir($thumbDir))
            mkdir($thumbDir, 0755, true);
        return $thumbDir;
    }

    public function thumbCreator($filePath, $width, $height){
        if (file_exists($filePath)) {
            $splFile = new SplFileInfo($filePath);
            $extension = pathinfo($splFile->getFilename(), PATHINFO_EXTENSION);

            if ($extension === 'swf') {
                $filePath = YiiBase::getPathOfAlias('webroot') . Yii::app()->getModule('upload')->getAssetsUrl().'/img/swf.png';
                $splFile = new SplFileInfo($filePath);
                $extension = pathinfo($splFile->getFilename(), PATHINFO_EXTENSION);
            }
            if ($extension === 'pdf') {
                $filePath = YiiBase::getPathOfAlias('webroot') . Yii::app()->getModule('upload')->getAssetsUrl().'/img/pdf.png';
                $splFile = new SplFileInfo($filePath);
                $extension = pathinfo($splFile->getFilename(), PATHINFO_EXTENSION);
            }
            $thumbFilePath = $this->getThumbDirPath($splFile) . $splFile->getBasename('.' . $extension) . $this->getThumbSuffix($width, $height) . '.' . $extension;
            if (!file_exists($thumbFilePath)) {
                /** @var /PHPThumb/GD $thumb */
                $thumb = Yii::app()->phpThumb->create($filePath);
                $thumb->resize($width, $height);
                $thumb->pad($width, $height);
                $thumb->save($thumbFilePath);
            }
            return str_replace(realpath(YiiBase::getPathOfAlias('webroot')), '', $thumbFilePath);
        } else {
            return '';
        }
    }

    public function getThumb($fileName, $width, $height)
    {
        $filePath = $this->filePathFromName($fileName);
        return self::thumbCreator($filePath, $width, $height);
    }

    public function thumbUrl($width, $height)
    {
        $filePath = $this->filePathFromName($this->filename);
        return self::thumbCreator($filePath, $width, $height);
    }

    public function globalThumbUrl($width, $height){
        return Yii::app()->request->hostInfo . Yii::app()->baseUrl . $this->thumbUrl($width, $height);
    }

    public function getThumbSuffix($width, $height)
    {
        return '_' . $width . 'x' . $height;
    }

    public function beforeDelete(){
        return (user()->checkAccess('admin') || user()->checkAccess('moderator') || $this->user_id == user()->id);
    }

    public function afterDelete()
    {
        $splFile = new SplFileInfo($this->getFilePath());
        $extension = pathinfo($splFile->getFilename(), PATHINFO_EXTENSION);
        if ($splFile->isFile()) {
            @unlink($splFile->getRealPath());
            $originalFile = Yii::app()->params['dataDir'].'/'.ltrim($this->filename,'/');
            if(is_file($originalFile)){
                @unlink($originalFile);
            }
        }
        $name = $splFile->getBasename('.' . $extension);

        // delete thumbs
        $flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;

        foreach (new FilesystemIterator($this->getThumbDirPath($splFile), $flags) as $thumb) {
            /** @var SplFileInfo $thumb */
            if (strpos($thumb->getFilename(), $name) !== false)
                @unlink($thumb->getRealPath());
        }
        if(is_dir(($splFile->getPath() . '/thumbnail/')))
            foreach (new FilesystemIterator(($splFile->getPath() . '/thumbnail/'), $flags) as $thumb) {
                /** @var SplFileInfo $thumb */
                if (strpos($thumb->getFilename(), $name) !== false)
                    @unlink($thumb->getRealPath());
            }

        UploadService::handleDefaultUploadAfterDelete($this);
        parent::afterDelete();
    }

    /**
     * @return Upload
     */
    public function checkAccess()
    {
        if (!user()->checkAccess('admin') || !user()->checkAccess('moderator')) {
            $this->dbCriteria->addCondition('user_id=' . user()->id);
        }
        return $this;
    }
}
