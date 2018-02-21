/*
 * Detact Mobile Browser
 */
if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
    $('html').addClass('ismobile');
}

$(window).on('load', function () {
    /* --------------------------------------------------------
        Page Loader
     ---------------------------------------------------------*/
    if(!$('html').hasClass('ismobile')) {
        if($('.page-loader')[0]) {
            setTimeout (function () {
                $('.page-loader').fadeOut();
            }, 500);

        }
    }
})

$(document).ready(function(){

    /* --------------------------------------------------------
        Scrollbar
    ----------------------------------------------------------*/
    function scrollBar(selector, theme, mousewheelaxis) {
        $(selector).mCustomScrollbar({
            theme: theme,
            scrollInertia: 100,
            axis:'yx',
            mouseWheel: {
                enable: true,
                axis: mousewheelaxis,
                preventDefault: true
            }
        });
    }

    if (!$('html').hasClass('ismobile')) {
        //On Custom Class
        if ($('.c-overflow')[0]) {
            scrollBar('.c-overflow', 'minimal-dark', 'y');
        }
    }

    /* --------------------------------------------------------
        Top Search
    ----------------------------------------------------------*/

    /* Bring search reset icon when focused */
    $('body').on('focus', '.hs-input', function(){
        $('.h-search').addClass('focused');
    });

    /* Take off reset icon if input length is 0, when blurred */
    $('body').on('blur', '.hs-input', function(){
        var x = $(this).val();

        if (!x.length > 0) {
            $('.h-search').removeClass('focused');
        }
    });


    /* --------------------------------------------------------
        User Alerts
    ----------------------------------------------------------*/
    $('body').on('click', '[data-user-alert]', function(e) {
        e.preventDefault();

        var u = $(this).data('user-alert');
        $('.'+u).tab('show');

    });


    /* ---------------------------------------------------------
         Todo Lists
     ----------------------------------------------------------*/
    if($('#todo-lists')[0]) {

        //Pre checked items
        $('#todo-lists .acc-check').each(function () {
            if($(this).is(':checked')) {
                $(this).closest('.list-group-item').addClass('checked');
            }
        });

        //On check
        $('body').on('click', '#todo-lists .acc-check', function () {
            if($(this).is(':checked')) {
                $(this).closest('.list-group-item').addClass('checked');
            }
            else {
                $(this).closest('.list-group-item').removeClass('checked');
            }
        });
    }

    /*
     * Calendar Widget
     */
    if($('#calendar-widget')[0]) {



        (function(){
            $('#cw-body').fullCalendar({
                contentHeight: 'auto',
                theme: true,
                header: {
                    right: 'next',
                    center: 'title, ',
                    left: 'prev'
                },
                defaultDate: '2014-06-12',
                editable: true,
                events: [
                    {
                        title: 'All Day',
                        start: '2014-06-01',
                        className: 'bgm-cyan'
                    },
                    {
                        title: 'Long Event',
                        start: '2014-06-07',
                        end: '2014-06-10',
                        className: 'bgm-orange'
                    },
                    {
                        id: 999,
                        title: 'Repeat',
                        start: '2014-06-09',
                        className: 'bgm-lightgreen'
                    },
                    {
                        id: 999,
                        title: 'Repeat',
                        start: '2014-06-16',
                        className: 'bgm-lightblue'
                    },
                    {
                        title: 'Meet',
                        start: '2014-06-12',
                        end: '2014-06-12',
                        className: 'bgm-green'
                    },
                    {
                        title: 'Lunch',
                        start: '2014-06-12',
                        className: 'bgm-cyan'
                    },
                    {
                        title: 'Birthday',
                        start: '2014-06-13',
                        className: 'bgm-amber'
                    },
                    {
                        title: 'Google',
                        url: 'http://google.com/',
                        start: '2014-06-28',
                        className: 'bgm-amber'
                    }
                ]
            });
        })();

        //Display Current Date as Calendar widget header
        var mYear = moment().format('YYYY');
        var mDay = moment().format('dddd, MMM D');
        $('#calendar-widget .cwh-year').html(mYear);
        $('#calendar-widget .cwh-day').html(mDay);

    }

    /*
     * Weather Card
     */
    if ($('#c-weather')[0]) {
        $.simpleWeather({
            location: 'Austin, TX',
            woeid: '',
            unit: 'f',
            success: function(weather) {
                var html = '<div class="cw-current media">' +
                    '<div class="pull-left cwc-icon cwci-'+weather.code+'"></div>' +
                    '<div class="cwc-info media-body">' +
                    '<div class="cwci-temp">'+weather.temp+'&deg;'+weather.units.temp+'</div>' +
                    '<ul class="cwci-info">' +
                    '<li>'+weather.city+', '+weather.region+'</li>' +
                    '<li>'+weather.currently+'</li>' +
                    '</ul>' +
                    '</div>' +
                    '</div>' +
                    '<div class="cw-upcoming"></div>';

                $("#c-weather").html(html);

                setTimeout(function() {


                    for(i = 0; i < 5; i++) {
                        var l = '<ul class="clearfix">' +
                            '<li class="m-r-15">' +
                            '<i class="cwc-icon cwci-sm cwci-'+weather.forecast[i].code+'"></i>' +
                            '</li>' +
                            '<li class="cwu-forecast">'+weather.forecast[i].high+'/'+weather.forecast[i].low+'</li>' +
                            '<li>'+weather.forecast[i].text+'</li>' +
                            '</ul>';

                        $('.cw-upcoming').append(l);
                    }
                });
            },
            error: function(error) {
                $("#c-weather").html('<p>'+error+'</p>');
            }
        });
    }

    /*
     * Auto Hight Textarea
     */
    if ($('.auto-size')[0]) {
        autosize($('.auto-size'));
    }

    /*
    * Profile Menu
    */
    $('body').on('click', '.profile-menu > a', function(e){
        e.preventDefault();
        $(this).parent().toggleClass('toggled');
        $(this).next().slideToggle(200);
    });

    /*
     * Text Feild
     */

    //Add blue animated border and remove with condition when focus and blur
    if($('.fg-line')[0]) {
        $('body').on('focus', '.fg-line .form-control', function(){
            $(this).closest('.fg-line').addClass('fg-toggled');
        })

        $('body').on('blur', '.form-control', function(){
            var p = $(this).closest('.form-group, .input-group');
            var i = p.find('.form-control').val();

            if (p.hasClass('fg-float')) {
                if (i.length == 0) {
                    $(this).closest('.fg-line').removeClass('fg-toggled');
                }
            }
            else {
                $(this).closest('.fg-line').removeClass('fg-toggled');
            }
        });
    }

    //Add blue border for pre-valued fg-flot text feilds
    if($('.fg-float')[0]) {
        $('.fg-float .form-control').each(function(){
            var i = $(this).val();

            if (!i.length == 0) {
                $(this).closest('.fg-line').addClass('fg-toggled');
            }

        });
    }

    /*
     * Tag Select
     */
    if($('.chosen')[0]) {
        $('.chosen').chosen({
            width: '100%',
            allow_single_deselect: true
        });
    }

    /*
     * Input Slider
     */
    //Basic
    if($('.input-slider')[0]) {
        $('.input-slider').each(function(){
            var isStart = $(this).data('is-start');

            $(this).noUiSlider({
                start: isStart,
                range: {
                    'min': 0,
                    'max': 100,
                }
            });
        });
    }

    //Range slider
    if($('.input-slider-range')[0]) {
        $('.input-slider-range').noUiSlider({
            start: [30, 60],
            range: {
                'min': 0,
                'max': 100
            },
            connect: true
        });
    }

    //Range slider with value
    if($('.input-slider-values')[0]) {
        $('.input-slider-values').noUiSlider({
            start: [ 45, 80 ],
            connect: true,
            direction: 'rtl',
            behaviour: 'tap-drag',
            range: {
                'min': 0,
                'max': 100
            }
        });

        $('.input-slider-values').Link('lower').to($('#value-lower'));
        $('.input-slider-values').Link('upper').to($('#value-upper'), 'html');
    }

    /*
     * Input Mask
     */
    if ($('input-mask')[0]) {
        $('.input-mask').mask();
    }

    /*
     * Color Picker
     */
    $('.color-picker').each(function(){
        var colorOutput = $(this).closest('.cp-container').find('.cp-value');
        $(this).farbtastic(function() {
            colorOutput.bind('keyup', this.updateValue);

            // Set background/foreground color
            $(colorOutput).css({
                backgroundColor: this.color,
                color: this.hsl[2] > 0.5 ? '#000' : '#fff'
            });

            // Change linked value
            var self = this;
            $(colorOutput).each(function() {
                if (this.value !== self.color) {
                    this.value = self.color;

                    var e = document.createEvent('HTMLEvents');
                    e.initEvent('input', true, true);
                    this.dispatchEvent(e);
                }
            });

            colorOutput.trigger("farbtastic.change");
        });
    });
    $(document).on('click', '.cp-container .dropdown', function (e) {
        // don't close colorpicker when clicked inside
        e.stopPropagation();
    });

    /*
     * HTML Editor
     */
    if ($('.html-editor')[0]) {
        $('.html-editor').each(function () {
            var id = $(this).attr('id');
            var editor = CodeMirror.fromTextArea(document.getElementById(id), {
                mode: 'htmlmixed',
                styleActiveLine: true,
                lineNumbers: true,
                lineWrapping: true,
            });
        });
    }

    /*
     * Date Time Picker
     */

    //Date Time Picker
    if ($('.date-time-picker')[0]) {
        $('.date-time-picker').datetimepicker({
            focusOnShow: false,
            extraFormats: [ 'YYYY-MM-DDTHH:mm:ssZ' ],
            useCurrent: false,
            locale: moment.locale()
            // format: 'YYYY-MM-DD HH:mm:ss'
        });
    }

    //Time
    if ($('.time-picker')[0]) {
        $('.time-picker').datetimepicker({
            format: 'HH:mm:ss'
        });
    }

    //Date
    if ($('.date-picker')[0]) {
        $('.date-picker').datetimepicker({
            locale: moment.locale(),
            format: 'l'
            extraFormats: [ 'YYYY-MM-DD' ],
//            format: 'YYYY-MM-DD'
        });
    }

    /*
     * Form Wizard
     */

    if ($('.form-wizard-basic')[0]) {
        $('.form-wizard-basic').bootstrapWizard({
            tabClass: 'fw-nav',
            'nextSelector': '.next',
            'previousSelector': '.previous'
        });
    }

    /*
     * Bootstrap Growl - Notifications popups
     */
    function notify(message, type){
        $.growl({
            message: message
        },{
            type: type,
            allow_dismiss: false,
            label: 'Cancel',
            className: 'btn-xs btn-inverse',
            placement: {
                from: 'top',
                align: 'right'
            },
            delay: 2500,
            animate: {
                enter: 'animated bounceIn',
                exit: 'animated bounceOut'
            },
            offset: {
                x: 20,
                y: 85
            }
        });
    };

    /*
     * Waves Animation
     */
    (function(){
        Waves.attach('.btn:not(.btn-icon):not(.btn-float)');
        Waves.attach('.btn-icon, .btn-float', ['waves-circle', 'waves-float']);
        Waves.init();
    })();

    /*
     * Lightbox
     */
    if ($('.lightbox')[0]) {
        $('.lightbox').lightGallery({
            enableTouch: true
        });
    }

    /*
     * Link prevent
     */
    $('body').on('click', '.a-prevent', function(e){
        e.preventDefault();
    });

    /*
     * Collaspe Fix
     */
    if ($('.collapse')[0]) {

        //Add active class for opened items
        $('.collapse').on('show.bs.collapse', function (e) {
            $(this).closest('.panel').find('.panel-heading').addClass('active');
        });

        $('.collapse').on('hide.bs.collapse', function (e) {
            $(this).closest('.panel').find('.panel-heading').removeClass('active');
        });

        //Add active class for pre opened items
        $('.collapse.in').each(function(){
            $(this).closest('.panel').find('.panel-heading').addClass('active');
        });
    }

    /*
     * Tooltips
     */
    if ($('[data-toggle="tooltip"]')[0]) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    /*
     * Popover
     */
    if ($('[data-toggle="popover"]')[0]) {
        $('[data-toggle="popover"]').popover();
    }

    /*
     * Message
     */

    //Actions
    if ($('.on-select')[0]) {
        var checkboxes = '.lv-avatar-content input:checkbox';
        var actions = $('.on-select').closest('.lv-actions');

        $('body').on('click', checkboxes, function() {
            if ($(checkboxes+':checked')[0]) {
                actions.addClass('toggled');
            }
            else {
                actions.removeClass('toggled');
            }
        });
    }

    if($('#ms-menu-trigger')[0]) {
        $('body').on('click', '#ms-menu-trigger', function(e){
            e.preventDefault();
            $(this).toggleClass('open');
            $('.ms-menu').toggleClass('toggled');
        });
    }

    /*
     * Login
     */
    if ($('.login')[0]) {
        $('body').on('click', '.l-block [data-block]', function(e){
            e.preventDefault();

            var z = $(this).data('block');
            var t = $(this).closest('.l-block');
            var c = $(this).data('bg');

            t.removeClass('toggled');

            setTimeout(function(){
                $('.login').attr('data-lbg', c);
                $(z).addClass('toggled');
            });

        })
    }

    /*
     * Fullscreen Browsing
     */
    if ($('[data-action="fullscreen"]')[0]) {
        var fs = $("[data-action='fullscreen']");
        fs.on('click', function(e) {
            e.preventDefault();

            //Launch
            function launchIntoFullscreen(element) {

                if(element.requestFullscreen) {
                    element.requestFullscreen();
                } else if(element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if(element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if(element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            }

            //Exit
            function exitFullscreen() {

                if(document.exitFullscreen) {
                    document.exitFullscreen();
                } else if(document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if(document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }

            launchIntoFullscreen(document.documentElement);
            fs.closest('.dropdown').removeClass('open');
        });
    }


    /*
     * Profile Edit Toggle
     */
    if ($('[data-pmb-action]')[0]) {
        $('body').on('click', '[data-pmb-action]', function(e){
            e.preventDefault();
            var d = $(this).data('pmb-action');

            if (d === "edit") {
                $(this).closest('.pmb-block').toggleClass('toggled');
            }

            if (d === "reset") {
                $(this).closest('.pmb-block').removeClass('toggled');
            }


        });
    }

    /*
     * IE 9 Placeholder
     */
    if($('html').hasClass('ie9')) {
        $('input, textarea').placeholder({
            customClass: 'ie9-placeholder'
        });
    }

    /*
     * Print
     */
    if ($('[data-action="print"]')[0]) {
        $('body').on('click', '[data-action="print"]', function(e){
            e.preventDefault();

            window.print();
        })
    }

    /*
     * Typeahead Auto Complete
     */
    if($('.typeahead')[0]) {

        var statesArray = ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California',
            'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii',
            'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
            'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
            'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
            'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota',
            'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island',
            'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
            'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'
        ];
        var states = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: statesArray
        });

        $('.typeahead').typeahead({
                hint: true,
                highlight: true,
                minLength: 1
            },
            {
                name: 'states',
                source: states
            });
    }


    /*
     * Wall
     */
    if ($('.wcc-toggle')[0]) {
        var z = '<div class="wcc-inner">' +
            '<textarea class="wcci-text auto-size" placeholder="Write Something..."></textarea>' +
            '</div>' +
            '<div class="m-t-15">' +
            '<button class="btn btn-sm btn-primary">Post</button>' +
            '<button class="btn btn-sm btn-link wcc-cencel">Cancel</button>' +
            '</div>'


        $('body').on('click', '.wcc-toggle', function() {
            $(this).parent().html(z);
            autosize($('.auto-size')); //Reload Auto size textarea
        });

        //Cancel
        $('body').on('click', '.wcc-cencel', function(e) {
            e.preventDefault();

            $(this).closest('.wc-comment').find('.wcc-inner').addClass('wcc-toggle').html('Write Something...')
        });

    }

    /*
     * Skin Change
     */
    $('body').on('click', '[data-skin]', function() {
        var currentSkin = $('[data-current-skin]').data('current-skin');
        var skin = $(this).data('skin');

        $('[data-current-skin]').attr('data-current-skin', skin)

    });

    $('select.order_after').on('change', function () {
        $('input[type="radio"][name="order"][value="after"]').trigger('click');
    });

    $('input[id^=change-status-batch]').change(function () {
        var url = $(this).data('url');
        var label = $('label[for="' + $(this).attr('id') + '"]');

        $(this).prop('disabled', true)
        window.location.href = url;
    });
});
