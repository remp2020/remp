<div class="table-responsive">
    @if ($displaySearchAndPaging)
        <div class="action-header m-0 palette-White bg clearfix">
            <div id="dt-search-{{ $tableId }}" class="ah-search" style="display: none;">
                <input placeholder="Search" class="ahs-input b-0" type="text" id="dt-search-{{ $tableId }}">
                <i class="ah-search-close zmdi zmdi-long-arrow-left" data-ma-action="ah-search-close"></i>
            </div>

            <div class="ah-right">
                <ul id="dt-nav-{{ $tableId }}" class="ah-actions actions a-alt">
                    <li><button class="btn palette-Cyan bg ah-search-trigger" data-ma-action="ah-search-open"><i class="zmdi zmdi-search"></i></button></li>
                    <li class="ah-length dropdown">
                        <button class="btn palette-Cyan bg" data-toggle="dropdown">{{ $paging[1] }}</button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @foreach($paging[0] as $pageCount)
                                <li data-value="{{$pageCount}}"><a class="dropdown-item dropdown-item-button">{{$pageCount}}</a></li>
                            @endforeach
                        </ul>
                    </li>

                    <li class="ah-pagination ah-prev"><button class="btn palette-Cyan bg"><i class="zmdi zmdi-chevron-left"></i></button></li>
                    <li class="ah-pagination ah-curr"><button class="btn palette-Cyan bg disabled">1</button></li>
                    <li class="ah-pagination ah-next"><button class="btn palette-Cyan bg"><i class="zmdi zmdi-chevron-right"></i></button></li>
                </ul>

                <div id="dt-buttons-{{ $tableId }}" class="ah-buttons"></div>
            </div>
        </div>
    @endif

    <table id="{{ $tableId }}" class="table table-striped table-bordered table-hover" aria-busy="false">
        <thead>
        <tr>
            @foreach ($cols as $col)
                <th>
                    @if (isset($col['header']))
                        {{ $col['header'] }}
                    @else
                        {{ $col['name'] }}
                    @endif

                    @if (isset($col['tooltip']))
                        <i class="zmdi zmdi-info" data-toggle="tooltip" data-placement="top" title="{{ $col['tooltip'] }}"></i>
                    @endif
                </th>
            @endforeach
            @if (!empty($rowActions))
                <th>actions</th>
            @endif
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script>
    var filterData = filterData || {};
    filterData['{{ $tableId }}'] = {
        @foreach ($cols as $col)
                @continue(!isset($col['filter']))
        '{{ $col['name'] }}': {!! json_encode($col['filter']) !!},
        @endforeach
    };

    $(document).ready(function() {
        var dataTable = $('#{{ $tableId }}').DataTable({
            'pageLength': {{ $paging[1] }},
            'responsive': true,
            'columns': [
                @foreach ($cols as $col)
                {
                    data: {!! @json($col['name']) !!},
                    name: {!! @json($col['name']) !!},
                    @isset($col['orderable'])
                    orderable: {!! @json($col['orderable']) !!},
                    @endisset
                    @isset($col['searchable'])
                    searchable: {!! @json($col['searchable']) !!},
                    @endisset
                    @isset($col['className'])
                    className: {!! @json($col['className']) !!},
                    @endisset
                    @isset($col['priority'])
                    responsivePriority: {!! @json($col['priority']) !!},
                    @endisset
                    @isset($col['orderSequence'])
                    orderSequence: {!! @json($col['orderSequence']) !!},
                    @endisset
                    render: function () {
                        @if (isset($col['render']))
                        if (typeof $.fn.dataTables.render[{!! @json($col['render']) !!}] === 'function') {
                            return $.fn.dataTables.render[{!! @json($col['render']) !!}]({!! @json($col['renderParams'] ?? '') !!});
                        }
                        if (typeof $.fn.dataTable.render[{!! @json($col['render']) !!}] === 'function') {
                            return $.fn.dataTable.render[{!! @json($col['render']) !!}]({!! @json($col['renderParams'] ?? '') !!});
                        }
                        @endif
                        return $.fn.dataTable.render.text({!! @json($col['renderParams'] ?? '') !!});
                    }(),
                },
                @endforeach
                @if (!empty($rowActions))
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 1,
                    render: $.fn.dataTables.render.actions({!! @json($rowActions) !!})
                },
                @endif
            ],
            'autoWidth': false,
            'sDom': 'tr',
            'processing': true,
            'serverSide': true,
            'order': {!! @json($order) !!},
            'ajax': {
                'url': '{{ $dataSource }}',
                'data': function (data) {
                    var url = window.location.href
                    var param = null;
                    @foreach ($requestParams as $var => $def)
                    param = '{!! $var !!}';
                    data[param] = {!! $def !!};

                    // update browser URL to persist the selection
                    url = $.fn.dataTables.upsertQueryStringParam(url, param, data[param]);
                    @endforeach
                    window.history.pushState(null, null, url);
                }
            },
            'createdRow': function (row, data, index) {
                var highlight = true;
                @forelse($rowHighlights as $column => $value)
                if (!data.hasOwnProperty({!! @json($column) !!}) || data['{{ $column }}'] !== {!! @json($value) !!}) {
                    highlight = false;
                }
                @empty
                highlight = false;
                @endforelse
                if (highlight) {
                    $(row).addClass('highlight');
                }
            },
            'drawCallback': function(settings) {
                $.fn.dataTables.pagination(settings, 'dt-nav-{{ $tableId }}');
            },
            'initComplete': function (a,b,c,d) {
                var state = this.api().state().columns;

                this.api().columns().every( function () {
                    var column = this;
                    var columns = column.settings().init().columns;
                    var columnDef = columns[column.index()];

                    if (filterData['{{ $tableId }}'][columnDef.name]) {
                        $.fn.dataTables.selectFilters(column, filterData['{{ $tableId }}'][columnDef.name], state);
                    }
                } );
            }
        });

        @if (!empty($exportColumns))
        new $.fn.dataTable.Buttons(dataTable, {
            buttons: [ {
                extend: 'csvHtml5',
                text: 'Download CSV',
                className: 'btn palette-Cyan bg',
                exportOptions: {
                    format: {
                        header: function (data) {
                            var html = $.parseHTML(data);
                            return html[0].data.replace(/(\r\n|\n|\r)/gm,"");
                        },
                        body: function (data, row, column, node) {
                            var $dateItem = $(node).find('.datatable-exportable-item');

                            if ($dateItem.length) {
                                return $dateItem.attr('title');
                            }
                            return $(node).text().replace(/(\r\n|\n|\r)/gm,"");
                        }
                    },
                    columns: {!! json_encode($exportColumns) !!}
                }
            } ]
        });

        dataTable.buttons().container()
            .appendTo( $('#dt-buttons-{{ $tableId }}') );
        @endif

        $.fn.dataTables.search(dataTable, 'dt-search-{{ $tableId }}');
        $.fn.dataTables.navigation(dataTable, 'dt-nav-{{ $tableId }}');

        $.fn.dataTableExt.errMode = function (e, settings, techNote, message) {
            console.warn(techNote);
            alert('Error while loading data table, try again later please.');
        };

        @foreach ($refreshTriggers as $def)
            var triggerEvent = '{!! $def['event'] !!}';

            @if ($def['selector'] === 'document')
                var triggerElement = $(document);
            @else
                var triggerElement = $('{!! $def['selector'] !!}');
            @endif

            triggerElement.on(triggerEvent, function() {
                dataTable.draw();
            });
        @endforeach
    });
</script>
