{{--<script type="text/javascript">--}}

function loadScript (src, callback) {
    var s = document.createElement('script');
    s.src = src;
    s.async = true;
    s.onreadystatechange = s.onload = function() {
        if (typeof callback !== 'undefined' && !callback.done && (!s.readyState || /loaded|complete/.test(s.readyState))) {
            callback.done = true;
            callback();
        }
    };
    document.querySelector('head').appendChild(s);
}

function loadStyle (src, callback) {
    var l = document.createElement('link');
    l.href = src;
    l.rel = "stylesheet";
    l.onreadystatechange = l.onload = function() {
        if (typeof callback !== 'undefined' && !callback.done && (!l.readyState || /loaded|complete/.test(l.readyState))) {
            callback.done = true;
            callback();
        }
    };
    document.querySelector('head').appendChild(l);
}

var bannerId = 'b-{{ $banner->uuid }}';
var scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/vue/2.3.2/vue.js',
    '{{ asset('/assets/js/banner.js') }}'
];
var styles = [
    '{{ asset('assets/css/banner.css') }}'
];

var waiting = scripts.length + styles.length;
var run = function() {
    if (waiting) {
        return;
    }
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');
    var banner = Campaign.banner.fromModel({!! $banner->toJson() !!});

    banner.show = false;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    var d = document.createElement('div');
    d.id = bannerId;
    var bp = document.createElement('banner-preview');
    d.appendChild(bp);
    var b = document.getElementsByTagName('body')[0];
    b.appendChild(d);

    Campaign.banner.bindPreview(banner, {
        zIndex: 99, //TODO: remove when REMP template is fixed,
        position: 'fixed'
    });
    new Vue({
        el: '#' + bannerId
    });
    setTimeout(function() {
        banner.show = true;
    }, 2000);
};

for (var i=0; i<scripts.length; i++) {
    loadScript(scripts[i], function() {
        waiting -= 1;
        run();
    });
}
for (i=0; i<styles.length; i++) {
    loadStyle(styles[i], function() {
        waiting -= 1;
        run();
    });
}

{{--</script>--}}