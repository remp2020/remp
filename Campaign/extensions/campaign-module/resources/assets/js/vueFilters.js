export function registerStripHtmlFilter(_vue) {
    _vue.filter('strip_html', function (value) {
        return value.replace(/<[^>]*>/g, '');
    });
}
