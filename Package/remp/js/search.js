require('jquery-typeahead');

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
                url: '/search',
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
                        searchResult['banners'].forEach(section => {
                            banners += `<span class="label label-default palette-Blue-Grey-50 bg">${section}</span> `;
                        });
                        searchResult['banners'] = banners;
                    }
                });

                this.options.display = [
                    'title',
                    'name',
                    'tags',
                    'sections',
                    'code',
                    'banners',
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
