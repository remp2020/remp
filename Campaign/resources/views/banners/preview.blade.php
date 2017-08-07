{{--<script type="text/javascript">--}}

var bannerId = 'b-{{ $banner->uuid }}';
var scripts = [];
if (typeof window.remplib.banner === 'undefined') {
    scripts.push('{{ asset(mix('/js/banner.js', '/assets/showtime')) }}');
}

var styles = [];

var waiting = scripts.length + styles.length;
var run = function() {
    if (waiting) {
        return;
    }
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');
    var banner = remplib.banner.fromModel({!! $banner->toJson() !!});

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

    remplib.banner.bindPreview('#' + bannerId, banner, {
        zIndex: 99, //TODO: remove when REMP template is fixed,
        position: 'fixed'
    });

    // TODO: track explicit click and close
    setTimeout(function() {
        // TODO: track show
        banner.show = true;
        if (banner.closeTimeout) {
            setTimeout(function() {
                // TODO: track close
                banner.show = false;
            }, banner.closeTimeout);
        }
    }, banner.displayDelay);
};

for (var i=0; i<scripts.length; i++) {
    remplib.loadScript(scripts[i], function() {
        waiting -= 1;
        run();
    });
}
for (i=0; i<styles.length; i++) {
    remplib.loadStyle(styles[i], function() {
        waiting -= 1;
        run();
    });
}

{{--</script>--}}