<template>
    <div></div>
</template>

<script>
export default {
    props: {
        "url": {
            type: String,
            required: true
        }
    },
    mounted() {
        var self = this;

        $(this.$el).closest('form').on('submit', function () {
            var form = this;

            if ($(form).attr('data-valid')) {
                return true;
            }

            self.validate(form).then(function () {
                $(form).attr('data-valid', true).submit();
            }, self.handleErrors);

            return false;
        });
    },
    methods: {
        validate(el) {
            return new Promise((resolve, reject) => {
                var data = $(el).serializeArray();

                for (var i in data) {
                    if (data[i].name == '_method') {
                        data.splice(i, 1)
                        break;
                    }
                }

                $.ajax({
                    type: 'POST',
                    url: this.url.trim(),
                    data: data,
                    success: function(data) {
                        resolve();
                    },
                    error: function(data) {
                        reject(data.responseJSON.errors);
                    }
                });
            })
        },
        handleErrors(errors) {
            for (var i in errors) {
                $.notify({
                    message: errors[i][0]
                }, {
                    allow_dismiss: false,
                    type: 'danger'
                });
            }
        }
    }
}
</script>

