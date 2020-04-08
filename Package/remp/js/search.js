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
        group: "type",
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
                let displayKeys = new Set();

                data.forEach( searchResult => {
                    let keyOrder = 0;
                    //get relevant search keys from current searchResult
                    const searchKeys = Object.keys(searchResult).filter(isSearchRelevantKey);
                    searchKeys.forEach(searchKey => {
                        //add search keys to displayKeys set (add() method adds only unique items into the set)
                        displayKeys.add(searchKey);
                        //distinguish different types by color
                        if (searchKey !== 'name' && searchKey !== 'title') {
                            searchResult[searchKey] = `<span class="colored_result_${keyOrder}"> ${searchResult[searchKey]}</span>`;
                        }
                        if (keyOrder > 0) {
                            searchResult[searchKey] = ' | ' + searchResult[searchKey];
                        }
                        keyOrder++;
                    });
                });

                this.options.display = Array.from(displayKeys);
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

function isSearchRelevantKey(key) {
    const irrelevantKeys = ['type', 'search_result_url', 'group'];

    return !irrelevantKeys.includes(key);
}
