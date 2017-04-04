$.fn.dataTables = {
    pagination: function (settings) {
        var start = settings._iDisplayStart;
        var length = settings._iDisplayLength;
        var count = settings._iRecordsDisplay;

        $('.ah-pagination button').removeAttr('disabled');

        if (start == 0) {
            $('.ah-prev button').attr('disabled', 'disabled');
        }

        if (start + length >= count) {
            $('.ah-next button').attr('disabled', 'disabled');
        }

        $('.ah-page .dropdown-menu').empty();
        for (page = 0; page <= count / length; page++) {
            $('.ah-page .dropdown-menu').append('<li data-value="' + page + '"><a class="dropdown-item dropdown-item-button">Page ' + (page+1) + '</a></li>');
            if (start == page * length) {
                $('.ah-page button').html('Page ' + (page+1));
                $('.ah-page li:last').addClass('active')
            }
        }
        if (page <= 1) {
            $('.ah-page button').attr('disabled', 'disabled');
        }
    },

    navigation: function (dataTable) {
        $('.ah-search input').on('keyup', function () {
            dataTable.search($(this).val()).draw();
        });

        $('.ah-length li').on('click', function () {
            var value = $(this).data('value');

            $('.ah-length button').html($(this).find('a').text());
            $('.ah-length li').removeClass('active');
            $(this).addClass('active');

            dataTable.page.len(value).draw();
        });

        $('.ah-prev').on('click', function () {
            dataTable.page('previous').draw('page');
        });

        $('.ah-next').on('click', function () {
            dataTable.page('next').draw('page');
        });

        $('.ah-page').on('click', 'li', function () {
            var value = $(this).data('value');

            $('.ah-page li').removeClass('active');
            $(this).addClass('active');
            $('.ah-page button').html($(this).find('a').text());

            dataTable.page(value).draw('page');
        });
    },

    render: {
        date: function () {
            return function(data) {
                var date = new Date(data);
                return date.toLocaleString();
            }
        },
        number: function () {
            return function(data) {
                return data.toLocaleString();
            }
        },
        boolean: function () {
            return function(data) {
                return data == 1 ? 'Yes' : 'No';
            }

        },
        actions: function (actionSettings) {
            return function(data, type, row) {
                var actions = '';
                $.each(actionSettings, function (key, action) {
                    actions += '<a href="' + action[0] + '/' + row.RowId + '"><i class="btn btn-xs palette-Cyan bg waves-effect zmdi zmdi-' + action[1] + '"></i></a>\n';
                });

                return actions;
            }
        }
    }
};