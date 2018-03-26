$.extend( $.fn.dataTable.defaults, {
    'language': {
        "processing": '<div class="preloader pl-lg pls-teal">' +
        '<svg class="pl-circular" viewBox="25 25 50 50">' +
        '<circle class="plc-path" cx="50" cy="50" r="20"></circle>' +
        '</svg>' +
        '</div>'
    },
    'stateSave': true,
    'stateLoaded': function(settings, data) {
        // set filter state
        if (data.search.search) {
            $('#dt-search-' + settings.sInstance).val(data.search.search);
            $('[data-ma-action="ah-search-open"]').trigger('click');
        }

        // set page state
        var page = (data.start / data.length) + 1;
        $('.ah-curr button').text(page);

        // set items-per-page state
        $('.ah-length button').text(data.length);
        $('.ah-length li').each(function() {
            $(this).removeClass('active');
            if ($(this).data('value') === data.length.toString()) {
                $(this).addClass('active');
            }
        })
    },
});

$.fn.dataTables = {
    pagination: function (settings, navId) {
        let start = settings._iDisplayStart;
        let length = settings._iDisplayLength;
        let count = settings._iRecordsDisplay;
        let nav = '#' + navId;

        $(nav + ' .ah-pagination button').removeAttr('disabled');

        if (start == 0) {
            $(nav + ' .ah-prev button').attr('disabled', 'disabled');
        }

        if (start + length >= count) {
            $(nav + ' .ah-next button').attr('disabled', 'disabled');
        }
    },

    navigation: function (dataTable, navId) {
        let nav = '#' + navId;

        $(nav + ' .ah-search input').on('change', function () {
            dataTable.search($(this).val()).draw();
        });

        $(nav + ' .ah-length li').on('click', function () {
            var value = $(this).data('value');

            $(nav + ' .ah-length button').html($(this).find('a').text());
            $(nav + ' .ah-length li').removeClass('active');
            $(this).addClass('active');

            dataTable.page.len(value).draw();
            $(this).closest('.ah-actions').find('.ah-curr button').text(dataTable.page.info().page + 1)
        });

        $(nav + ' .ah-prev').on('click', function () {
            if ($(this).find('button').is(':disabled')) {
                return;
            }
            dataTable.page('previous').draw('page');
            $(this).closest('.ah-actions').find('.ah-curr button').text(dataTable.page.info().page + 1)
        });

        $(nav + ' .ah-next').on('click', function () {
            if ($(this).find('button').is(':disabled')) {
                return;
            }
            dataTable.page('next').draw('page');
            $(this).closest('.ah-actions').find('.ah-curr button').text(dataTable.page.info().page + 1)
        });
    },

    selectFilters: function (column, filterData, state) {
        // create select box
        var select = $('<select multiple class="selectpicker" data-live-search="true" data-live-search-normalize="true"></select>')
            .appendTo( $(column.header()) )
            .on( 'change', function() {
                column
                    .search($(this).val())
                    .draw();
            });

        // restore state and set selected options
        var searchValues = state[column.index()].search.search.split(",");
        $.each(filterData, function (value, label) {
            var newOption = $('<option value="'+value+'">'+label+'</option>');
            if (searchValues.indexOf(value) !== -1) {
                newOption.prop("selected", true);
            }
            select.append(newOption);
        });

        select.selectpicker();
    },

    upsertQueryStringParam(url, key, value) {
        if (!value) {
            value = "";
        }
        let re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        let separator = url.indexOf('?') !== -1 ? "&" : "?";
        if (url.match(re)) {
            return url.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return url + separator + key + "=" + value;
        }
    },

    durationToText(d) {
        if (d.asSeconds() === 0) {
            return '0s';
        }
        let durationString = ""
        if (d.asHours() >= 1) {
            durationString += Math.floor(d.asHours()) + "h&nbsp;"
        }
        if (d.asMinutes() >= 1) {
            durationString += Math.floor(d.minutes()) + "m&nbsp;"
        }
        if (d.asSeconds() >= 1) {
            durationString += Math.floor(d.seconds()) + "s"
        }
        return durationString;
    },

    render: {
        date: function () {
            return function(data) {
                if (data === null) {
                    return "";
                }
                return "<span title='" + moment(data).format('LLL') + "'>" + moment(data).locale('en').fromNow() + "</span>";
            }
        },
        number: function () {
            return function(data) {
                return data.toLocaleString();
            }
        },
        boolean: function () {
            return function(data) {
                if (data === 1 || data === true) {
                    return 'Yes';
                }
                if (data === 0 || data === false) {
                    return 'No';
                }
                return '';
            }
        },
        link: function () {
            return function(data) {
                return '<a href="' + data.url + '">' + data.text + '</a>';
            }

        },
        bytes: function () {
            return function (data) {
                if (data === null) {
                    return '';
                }

                var k = 1024;
                var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                var i = Math.floor(Math.log(data) / Math.log(k));

                return parseFloat((data / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };
        },
        array: function (config) {
            let column = null;
            if (typeof config !== 'undefined' && config.hasOwnProperty('column')) {
                column = config["column"];
            }
            return function(data) {
                var result = '';
                for (var i=0; i<data.length; i++) {
                    if (column === null) {
                        result += data[i] + '<br/>';
                    } else {
                        result += data[i][column] + '<br/>';
                    }
                }
                return result;
            }
        },
        badge: function () {
            return function(data) {
                var string = '';
                $.each(data, function (index, badge) {
                    if (badge !== '') {
                        string += '<div class="badge m-r-5 ' + badge.class + '">' + badge.text + '</div>';
                    }
                });

                return string;
            }
        },
        actions: function (actionSettings) {
            return function(data, type, row) {
                var actions = '<span class="actions">';
                $.each(actionSettings, function (key, action) {
                    if (row.actions[action['name']] === null) {
                        actions += '<a class="btn btn-sm palette-Cyan bg waves-effect" disabled="disabled" href=""><i class="zmdi ' + action['class'] + '"></i></a>\n';
                        return;
                    }
                    if (row.action_methods && row.action_methods[action['name']]) {
                        var tokenVal = $('meta[name="csrf-token"]').attr('content');
                        actions += '<form method="POST" action="' + row.actions[action['name']] + '">';
                        actions += '<button type="submit" class="btn btn-sm palette-Cyan bg waves-effect"><i class="zmdi ' + action['class'] + '"></i></button>\n';
                        actions += '<input type="hidden" name="_token" value="' + tokenVal + '" />\n';
                        actions += '<input type="hidden" name="_method" value="' + row.action_methods[action['name']] + '" />\n';
                        actions += '</form>';
                        return;
                    }
                    actions += '<a class="btn btn-sm palette-Cyan bg waves-effect" href="' + row.actions[action['name']] + '"><i class="zmdi ' + action['class'] + '"></i></a>\n';
                });
                actions += '</span>';
                return actions;
            }
        },
        duration: function() {
            return function (data) {
                let duration = parseInt(data);
                if (data === 0) {
                    return '0s';
                }
                let d = moment.duration(duration, 'seconds');
                let durationString = ""
                if (d.asHours() >= 1) {
                    durationString += Math.floor(d.asHours()) + "h&nbsp;"
                }
                if (d.asMinutes() >= 1) {
                    durationString += Math.floor(d.minutes()) + "m&nbsp;"
                }
                if (d.asSeconds() >= 1) {
                    durationString += Math.floor(d.seconds()) + "s"
                }
                return "<span title='" + d.humanize() + "'>" + durationString.trim() + "</span>";
            };
        },
        numberStat: function() {
            return function (data) {
                if (data.length === 1) {
                    return data[0];
                }
                let d0 = parseFloat(data[0]);
                let d1 = parseFloat(data[1]);

                let cls, icon;
                if (d0 >= d1) {
                    cls = 'text-success';
                    icon = 'zmdi-caret-up'
                } else {
                    cls = 'text-danger';
                    icon = 'zmdi-caret-down';
                }

                return "<span>" + data[0] + "</span> <small style='white-space: nowrap;' class='" + cls + "'>(<i class='zmdi " + icon + "'></i>" + Math.abs(d0 - d1).toFixed(2) + ")</small>";
            };
        },
        multiNumberStat: function() {
            return function (data) {
                let result = "";
                for (item of data) {
                    result += $.fn.dataTables.render.numberStat()(item) + '<br/>';
                }
                return result.trim();
            }
        },
        durationStat: function() {
            return function (data) {
                if (data.length === 1) {
                    return data[0];
                }
                data[0] = parseFloat(data[0]);
                data[1] = parseFloat(data[1]);

                let d = moment.duration(data[0], 'seconds')
                let durationText = $.fn.dataTables.durationToText(d);

                let sd = moment.duration(Math.abs(data[0] - data[1]), 'seconds')
                let statText = $.fn.dataTables.durationToText(sd);

                let cls, icon;
                if (data[0] >= data[1]) {
                    cls = 'text-success';
                    icon = 'zmdi-caret-up'
                } else {
                    cls = 'text-danger';
                    icon = 'zmdi-caret-down';
                }

                return "<span title='" + d.humanize() + "'>" + durationText.trim() + "</span> <small title='" + sd.humanize() + "' class='" + cls + "'>(<i class='zmdi " + icon + "'></i>" + statText.trim() + ")</small>";
            };
        }
    }
};
