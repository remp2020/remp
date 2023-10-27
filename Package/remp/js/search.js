require('jquery-typeahead');

let config = {
    url: "/search",
}

module.exports = {
    setUrl: function(url) {
        config.url = url;
    }
}

$(document).ready(function() {
    $('.js-typeahead').typeahead({
        dynamic: true,
        filter: false,
        highlight: true,
        maxItem: false,
        cancelButton: false,
        loadingAnimation: true,
        emptyTemplate: "No results found for <strong>{{query}}</strong>",
        source: {
            ajax: {
                url: config.url,
                data: {
                    term: '{{query}}'
                },
            }
        },
        callback: {
            onPopulateSource: function (node, data, group, path) {
                data.forEach( searchResult => {
                    // TODO: applications should register icons and formatting themselves
                    switch (searchResult['type']) {
                        case 'article':
                            searchResult['title'] = '<i class="zmdi zmdi-library"></i> ' + searchResult['title'];
                            break;
                        case 'author':
                            searchResult['name'] = '<i class="zmdi zmdi-account-box"></i> ' + searchResult['name'];
                            break;
                        case 'segment':
                            searchResult['name'] = '<i class="zmdi zmdi-accounts-list-alt"></i> ' + searchResult['name'];
                            break;
                        case 'campaign':
                            searchResult['name'] = '<i class="zmdi zmdi-ticket-star"></i> ' + searchResult['name'];
                            break;
                        case 'banner':
                            searchResult['name'] = '<i class="zmdi zmdi-collection-folder-image"></i> ' + searchResult['name'];
                            break;
                        case 'snippet':
                            searchResult['name'] = '<i class="zmdi zmdi-code"></i> ' + searchResult['name'];
                            break;

                        // Mailer
                        case 'email':
                            searchResult['name'] = '<span class="label label-default"><i class="zmdi zmdi-view-quilt"></i> email</span> ' + searchResult['name'];
                            break;
                        case 'layout':
                            searchResult['name'] = '<span class="label label-default"><i class="zmdi zmdi-widgets"></i> layout</span> ' + searchResult['name'];
                            break;
                        case 'list':
                            searchResult['title'] = '<span class="label label-default"><i class="zmdi zmdi-arrow-split"></i> list</span> ' + searchResult['title'];
                            break;
                        case 'job':
                            searchResult['name'] = '<span class="label label-default"><i class="zmdi zmdi-refresh"></i> job</span> ' + searchResult['name'];
                            break;
                    }

                    // format tags
                    if (searchResult['tags']) {
                        let tags = '';
                        searchResult['tags'].forEach(tag => {
                            tags += `<span class="label label-default palette-Blue-Grey-50 bg">${tag}</span> `;
                        });
                        searchResult['tags'] = tags;
                    }

                    // format sections
                    if (searchResult['sections']) {
                        let sections = '';
                        searchResult['sections'].forEach(section => {
                            sections += `<span class="label label-default palette-Deep-Orange-50 bg">${section}</span> `;
                        });
                        searchResult['sections'] = sections;
                    }

                    // format banners
                    if (searchResult['banners']) {
                        let banners = '';
                        searchResult['banners'].forEach(banner => {
                            banners += `<span class="label label-default palette-Blue-Grey-50 bg">${banner}</span> `;
                        });
                        searchResult['banners'] = banners;
                    }

                    // format statuses
                    if (searchResult['statuses']) {
                        let statuses = '';
                        searchResult['statuses'].forEach(status => {
                            statuses += `<span class="label label-default palette-Blue-Grey-50 bg">${status}</span> `;
                        });
                        searchResult['statuses'] = statuses;
                    }

                    // format codes
                    if (searchResult['codes']) {
                        let codes = '';
                        searchResult['codes'].forEach(code => {
                            codes += `<span class="label label-default palette-Blue-Grey-50 bg">${code}</span> `;
                        });
                        searchResult['codes'] = codes;
                    }

                    // format mail types
                    if (searchResult['mail_types']) {
                        let mail_types = '';
                        searchResult['mail_types'].forEach(mail_type => {
                            mail_types += `<span class="label label-default palette-Purple-50 bg">${mail_type}</span> `;
                        });
                        searchResult['mail_types'] = mail_types;
                    }

                    // format mail types
                    if (searchResult['date']) {
                        searchResult['date'] = `<span class="label label-default palette-Amber-50 bg">${moment.utc(searchResult['date']).local().format('LLL')}</span> `;
                    }
                });

                this.options.display = [
                    'title',
                    'name',
                    'tags',
                    'sections',
                    'code',
                    'banners',
                    'statuses',
                    'codes',
                    'mail_types',
                    'date',
                ];
                $('.typeahead__field .preloader').css('visibility', 'hidden');
                return data;
            },
            onClickBefore: function (node, a, item, event) {
                event.preventDefault();
                window.location = item.search_result_url;
            },
            onSubmit: function (node, form, item, event) {
                event.preventDefault();
            },
            onCancel: function () {
                $('.typeahead__field .preloader').css('visibility', 'hidden');
            },
            onSendRequest: function () {
                $('.typeahead__field .preloader').css('visibility', 'visible');
            }
        }
    });
});
