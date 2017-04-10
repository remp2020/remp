CKEDITOR.editorConfig = function( config ) {
    config.toolbarGroups = [
        { name: 'format', groups: [ 'styles', 'document' ] },
        { name: 'colors' },
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
        { name: 'paragraph', groups: [ 'list', 'indent', 'align' ] },
        { name: 'document', groups: [ 'mode' ] },
        '/',
        { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
        { name: 'links' },
        { name: 'insert' },
        { name: 'others' }
    ];

    config.forcePasteAsPlainText = true;
    config.allowedContent = true;

    config.format_tags = 'p;h3;h4;h5;h6';
    config.removeButtons = 'PasteText,PasteFromWord,Anchor,Subscript,Superscript,Styles';
    config.removeDialogTabs = 'link:advanced';
    config.removePlugins = 'resize';

    config.filebrowserImageBrowseUrl = ''; // @todo add presenter for upload images
    config.filebrowserImageUploadUrl = ''; // @todo add presenter for image browser

    config.autoGrow_onStartup = true;
    config.autoGrow_minHeight = 300;
    config.autoGrow_maxHeight = 600;
};
