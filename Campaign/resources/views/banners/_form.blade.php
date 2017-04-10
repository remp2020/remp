@push('head')
<link href="/assets/vendor/dropzone/dist/min/dropzone.min.css" rel="stylesheet">
<script src="/assets/vendor/dropzone/dist/dropzone.js"></script>
@endpush

<div class="form-group fg-float m-b-30">
    <div class="fg-line">
        <div class="dropzone dz-message needsclick dz-clickable" id="banner-dropzone"></div>
    </div>

    <div class="fg-line">
        {!! Form::text('name', null, ['class' => 'form-control fg-input']) !!}
        {!! Form::label('name', null, ['class' => 'fg-label']) !!}
    </div>
</div>

{!! Form::button('<i class="zmdi zmdi-mail-send"></i> Save', [
    'class' => 'btn btn-info waves-effect',
    'type' => 'submit',
]) !!}

{!! Form::hidden('storage_uri') !!}

{!! Form::hidden('width') !!}

{!! Form::hidden('height') !!}

@push('scripts')
<script type="text/javascript">
    Dropzone.options.bannerDropzone = {
        paramName: "file",
        maxFilesize: 2,
        uploadMultiple: false,
        maxFiles: 1,
        url: '{!!route('banners.upload') !!}',

        init:function() {
            @if ($banner->id)
            var dz = this;
            $.get('{{ $banner->storage_uri }}', function (data) {
                var mockFile = {
                    name: "{{ $banner->name }}",
                    size: data.length,
                    accepted: true
                };
                dz.files.push(mockFile);
                dz.emit("addedfile", mockFile);
                dz.createThumbnailFromUrl(mockFile, "{{ $banner->storage_uri }}", null, true);
                dz.emit("complete", mockFile);
            });
            @endif

            this.on('maxfilesexceeded', function (file) {
                this.removeAllFiles();
                this.addFile(file);
            });

        },

        sending: function(file, xhr, formData) {
            formData.append("_token", $('[name="_token"]').val()); // Laravel expect the token post value to be named _token by default
        },

        success: function(file, data) {
            $('[name="storage_uri"]').val(data["uri"]);
            $('[name="width"]').val(data["width"]);
            $('[name="height"]').val(data["height"]);
        }
    };
</script>
@endpush