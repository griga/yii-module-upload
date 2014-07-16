<?php
/** Created by griga at 25.01.14 | 0:38.
 * @var UploadImagesWidget $this
 * @var CActiveRecord|UploadBehavior $model
 * @var int $widgetCounter
 * @var string $inputName
 */

$uploadFieldName = get_class($model) . "[uploads][]";

?>


<div class="upload-images-widget-<?= $this->id ?> upload-images-widget-multiple"
     data-upload-widget-id="<?= $widgetCounter ?>">
    <span class="btn btn-success fileinput-button">
        <i class="glyphicon glyphicon-plus"></i>
        <span><?= t('Add files...') ?></span>

        <input id="fileupload-<?= $this->id ?>" type="file" name="files[]" multiple>

    </span>

    <div id="progress-<?= $this->id ?>" class="progress">
        <div class="progress-bar progress-bar-success"></div>
    </div>
    <div id="files-<?= $this->id ?>" class="files">
        <?php foreach ($model->getUploads() as $index => $upload): ?>
            <div class="upload-wrapper"><a target="_blank" href="/<?= $upload->filename; ?>">
                    <p>
                        <?= CHtml::image($upload->thumbUrl(100, 100)); ?>
                        <?= CHtml::hiddenField($uploadFieldName, $upload->id , array('id' => 'upload-' . $index)) ?>
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
                <button class="btn btn-success"><?= t('save') ?></button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        var url = '<?= app()->createUrl('/upload/default/') ?>/';
        var $files = $('#files-<?= $this->id ?>');
        var sortableStopCallback = function () {
            var data = [];
            $files.find('.upload-wrapper').each(function () {
                data.push($(this).find('[type=hidden]').val());
            });
            $.post(url + 'sort', {
                uploads: data
            });
        };
        $files.sortable({
            stop: sortableStopCallback
        });
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
                        $wrapper.fadeOut('fast', function () {
                            $(this).remove();
                        });
                    }
                }, 'json');
            }
            e.preventDefault();
        }).on('click', '.edit-upload', function (e) {
            var $wrapper = $(this).closest('.upload-wrapper');
            $eud.data('uploadId',$wrapper.find('[type=hidden]').val())
            var $img = $eud.find('.edit-upload-preview-wrapper').append('<img>').find('img');
            $eud.appendTo('body').css('top', $(window).scrollTop() + 50 + 'px').fadeIn();
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

        var uploadButton = $('<button/>')
            .addClass('btn btn-primary')
            .prop('disabled', true)
            .text('Process...')
            .on('click', function (e) {
                var $this = $(this),
                    data = $this.data();
                $this
                    .off('click')
                    .text('Cancel')
                    .on('click', function () {
                        $this.remove();
                        data.abort();
                    });
                data.submit().always(function () {
                    $this.remove();
                });
                e.preventDefault();
            });
        $('#fileupload-<?= $this->id ?>').fileupload({
            url: url + 'upload',
            dataType: 'json',
            autoUpload: false,
            acceptFileTypes: /(\.|\/)(gif|jpe?g|png|swf|pdf)$/i,
            maxFileSize: 5000000, // 5 MB
            previewMaxWidth: 100,
            previewMaxHeight: 100,
            previewCrop: true,
            formData: {
                entity: '<?= get_class($model) ?>',
                entity_id: '<?= $model->id ?>',
                widgetCounter: <?= $widgetCounter?>
            }
        }).on('fileuploadadd',function (e, data) {
            data.context = $('<div/>').addClass('upload-wrapper').appendTo($files);
            $.each(data.files, function (index, file) {
                var node = $('<p/>');
                if (!index) {
                    node
                        .append('<br>')
                        .append(uploadButton.clone(true).data(data));
                }
                node.appendTo(data.context);
            });
        }).on('fileuploadprocessalways',function (e, data) {
            var index = data.index,
                file = data.files[index],
                node = $(data.context.children()[index]);

            if (file.preview) {
                node
                    .prepend(file.preview);
            } else {
                node.prepend('<img width="100" height="100" src="<?=Yii::app()->getModule('upload')->getAssetsUrl() ?>/img/' + (file.name.split('.').pop()) + '.png">')
            }
            if (file.error) {
                node
                    .append('<br>')
                    .append($('<span class="text-danger"/>').text(file.error));
            }
            if (index + 1 === data.files.length) {
                data.context.find('button')
                    .text('<?= t('Upload')?>')
                    .prop('disabled', !!data.files.error);
            }
        }).on('fileuploadprogressall',function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress-<?= $this->id ?> .progress-bar').css(
                'width',
                progress + '%'
            );
        }).on('fileuploaddone',function (e, data) {
            $.each(data.result.files, function (index, file) {
                if (file.url) {
                    var link = $('<a>')
                        .attr('target', '_blank')
                        .prop('href', file.url);
                    $(data.context.children()[index])
                        .wrap(link)
                        .append('<input type="hidden" value="' + file.id + '" name="<?= $uploadFieldName ?>">')
                        .append('<a class="delete-upload" href="#"><i class="glyphicon glyphicon-trash"></i></a>')
                        .append('<a class="edit-upload" href="#"><i class="glyphicon glyphicon-pencil"></i></a>');
                    $files.sortable({
                        stop: sortableStopCallback
                    });
                } else if (file.error) {
                    var error = $('<span class="text-danger"/>').text(file.error);
                    $(data.context.children()[index])
                        .append('<br>')
                        .append(error);
                }
            });
        }).on('fileuploadfail',function (e, data) {
            $.each(data.files, function (index, file) {
                var error = $('<span class="text-danger"/>').text('File upload failed.');
                $(data.context.children()[index])
                    .append('<br>')
                    .append(error);
            });
        }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
    });

</script>
