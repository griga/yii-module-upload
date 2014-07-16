<?php
/** Created by griga at 25.01.14 | 0:38.
 * @var UploadImagesWidget $this
 * @var CActiveRecord|UploadBehavior $model
 * @var string $fieldName
 * @var string $inputName
 * @var int $widgetCounter
 */

if ($inputName || $fieldName) {
    $uploads = Upload::model()->findAllByAttributes(array(
        'entity' => 'Upload',
        'id' => $model->{$fieldName},
    ));
} else {
    $uploads = $model->getUploads();
}

$uploadFieldName = $inputName ?: ($fieldName ? get_class($model) . "[$fieldName]" : get_class($model) . "[uploads][$widgetCounter]");

?>

<div class="upload-images-widget-<?= $this->id ?> upload-images-widget-single"
     data-upload-widget-id="<?= $widgetCounter ?>">
    <span class="btn btn-success fileinput-button" <?= (count($uploads)) ? ' style="display:none"' : '' ?>>
        <i class="glyphicon glyphicon-plus"></i>
        <span>Добавить файл...</span>

        <input id="fileupload-<?= $this->id ?>" type="file" name="files[]">

    </span>

    <div id="files-<?= $this->id ?>" class="files">
        <?php foreach ($uploads as $index => $upload): ?>
            <div class="upload-wrapper"><a target="_blank" href="/<?= $upload->file; ?>">
                    <p>
                        <?= CHtml::image($upload->thumbUrl(100, 100)); ?>
                        <?= CHtml::hiddenField($uploadFieldName, $upload->id, array('id' => 'upload-' . $index)) ?>
                        <a class="delete-upload" href="#"><i class="glyphicon glyphicon-trash"></i></a>
                        <a class="edit-upload" href="#"><i class="glyphicon glyphicon-pencil"></i></a>
                    </p></a>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="edit-upload-dialog-<?= $this->id ?>" style="display: none" class="edit-upload-dialog">
        <div class="edit-upload-preview-wrapper">
        </div>
        <div class="edit-upload-actions">
            <div class="edit-upload-control-wrapper"><input type="text" placeholder="alt"/></div>
            <div class="edit-upload-control-wrapper"><input type="text" placeholder="title"/></div>
            <div class="edit-upload-control-wrapper"><input type="text" placeholder="caption"/></div>
            <div class="edit-upload-control-wrapper">
                <button class="btn btn-success">сохранить</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        'use strict';

        var url = '<?= Yii::app()->createUrl('/upload/default/hack')?>'.replace(/hack/, '');
        var $files = $('#files-<?= $this->id ?>');
        var $eud = $("#edit-upload-dialog-<?= $this->id ?>");
        $eud.hideCallback = function(){
            $eud.fadeOut();
            $eud.jcropApi && $eud.jcropApi.destroy();
            $eud.find('img').remove();
            $eud.find('.btn-success').removeAttr('disabled');

            $('body').removeClass('edit-upload-overlay').off('click.editUpload')
        }
        $files.on('click', '.delete-upload',function (e) {
            if (confirm('Удалить изображение?')) {
                var $wrapper = $(this).closest('.upload-wrapper');
                $.post(url + 'delete', {
                    'Upload[id]': $wrapper.find('[type=hidden]').val()
                }, function (data) {
                    if (data.success) {
                        $wrapper.closest('.upload-images-widget-single').find('.fileinput-button').show();
                        $wrapper.remove();
                    }
                }, 'json');
            }
            e.preventDefault();
        }).on('click', '.edit-upload', function (e) {
            var $wrapper = $(this).closest('.upload-wrapper');
            $eud.data('uploadId',$wrapper.find('[type=hidden]').val())
            var $img = $eud.find('.edit-upload-preview-wrapper').append('<img>').find('img');
            $eud.appendTo('body').fadeIn();
            $.getJSON(url + 'edit', {
                'id': $eud.data('uploadId')
            }).success(function (data) {
                data = data || {};
                $eud.find('[placeholder=alt]').val(data.alt);
                $eud.find('[placeholder=title]').val(data.title);
                $eud.find('[placeholder=caption]').val(data.caption);

                $img.attr('src', ($wrapper.find('a[target="_blank"]').attr('href')) + '?' + (new Date()).getTime() ).on('load', function(){
                    $img.parent().css('padding',0).css('padding', ($img.parent().height() - $img.height())/2 + 'px ' + ($img.parent().width() - $img.width())/2 + 'px')
                    $img.Jcrop({
                        onSelect: function(coordinates){
                            coordinates.webWidth = $img.width();
                            $eud.find('.btn-success').data('coordinates',coordinates);
                        }
                    }, function(){  $eud.jcropApi = this;  } )


                    $('body').addClass('edit-upload-overlay').on('click.editUpload', function (e) {
                        if ($(e.target).closest('.edit-upload-preview-wrapper, .edit-upload-actions').length == 0)
                            $eud.hideCallback();
                    })
                })
            })
            e.preventDefault();
            e.stopPropagation();
        });

        $eud.on('click','.btn-success',function(){
            if (!$(this).attr('disabled')) {
                $(this).attr('disabled', 'disabled')
                var data = {
                    'Upload[id]': $eud.data('uploadId'),
                    'Upload[alt]': $eud.find('[placeholder=alt]').val(),
                    'Upload[title]': $eud.find('[placeholder=title]').val(),
                    'Upload[caption]': $eud.find('[placeholder=caption]').val()
                };
                if ($(this).data('coordinates')) {
                    data['Upload[crop]'] = $(this).data('coordinates');
                    $(this).removeData();
                }
                $.post(url + 'edit', data, $eud.hideCallback);
            }
        })
        $('#fileupload-<?= $this->id ?>').fileupload({

            url: url + 'upload',
            dataType: 'json',
            autoUpload: true,
            acceptFileTypes: /(\.|\/)(gif|jpe?g|png|swf|pdf)$/i,
            maxFileSize: 5000000, // 5 MB
            previewMaxWidth: 100,
            previewMaxHeight: 100,
            previewCrop: true,
            singleFileUploads: false,
            formData: {
                entity: '<?= $fieldName ? 'Upload' : get_class($model) ?>',
                entity_id: '<?= $model->id ?>',
                widgetCounter: <?= $widgetCounter?>
            }
        }).on('fileuploadprocessalways',function (e, data) {
            $files.closest('.upload-images-widget-single').find('.fileinput-button').hide();
            var index = data.index,
                file = data.files[index],
                node = $files.find('.upload-wrapper p');
            if (node.length == 0)
                node = $files.append('<div class="upload-wrapper"><p></p></div>').find('.upload-wrapper p');
            if (file.preview) {
                node.prepend(file.preview);
            } else {
                node.prepend('<img width="100" height="100" src="<?=Yii::app()->getModule('upload')->getAssetsUrl() ?>/img/' + (file.name.split('.').pop()) + '.png">')
            }
            if (file.error) {
                node
                    .append('<br>')
                    .append($('<span class="text-danger"/>').text(file.error));
            }
        }).on('fileuploaddone',function (e, data) {
            $.each(data.result.files, function (index, file) {
                if (file.url) {
                    var link = $('<a>')
                        .attr('target', '_blank')
                        .prop('href', file.url);
                    $files.find('.upload-wrapper p')
                        .wrap(link)
                        .append('<input type="hidden" value="' + file.id + '" name="<?= $uploadFieldName ?>">')
                        .append('<a class="delete-upload" href="#"><i class="glyphicon glyphicon-trash"></i></a>')
                        .append('<a class="edit-upload" href="#"><i class="glyphicon glyphicon-pencil"></i></a>');
                } else if (file.error) {
                    var error = $('<span class="text-danger"/>').text(file.error);
                    $files.find('.upload-wrapper p')
                        .append('<br>')
                        .append(error);
                }
            });
        }).on('fileuploadfail',function (e, data) {
            $.each(data.files, function (index, file) {
                var error = $('<span class="text-danger"/>').text('File upload failed.');
                $files.closest('.upload-images-widget-single').find('.fileinput-button').show();
                $files.find('.upload-wrapper p')
                    .append('<br>')
                    .append(error);
            });
        }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');

    })
</script>
