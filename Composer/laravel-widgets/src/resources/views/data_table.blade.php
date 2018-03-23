<div class="table-responsive">

    @if ($displaySearchAndPaging)
    <div class="action-header m-0 palette-White bg clearfix">
        <div class="ah-search" style="display: none;">
            <input placeholder="Search" class="ahs-input b-0" type="text" id="dt-search-{{ $tableId }}">
            <i class="ah-search-close zmdi zmdi-long-arrow-left" data-ma-action="ah-search-close"></i>
        </div>

        <ul class="ah-actions actions a-alt">
            <li><button class="btn palette-Cyan bg ah-search-trigger" data-ma-action="ah-search-open"><i class="zmdi zmdi-search"></i></button></li>
            <li class="ah-length dropdown">
                <button class="btn palette-Cyan bg" data-toggle="dropdown">10</button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li data-value="10"><a class="dropdown-item dropdown-item-button">10</a></li>
                    <li data-value="25"><a class="dropdown-item dropdown-item-button">25</a></li>
                    <li data-value="100"><a class="dropdown-item dropdown-item-button">100</a></li>
                    <li data-value="250"><a class="dropdown-item dropdown-item-button">250</a></li>
                </ul>
            </li>

            <li class="ah-pagination ah-prev"><button class="btn palette-Cyan bg"><i class="zmdi zmdi-chevron-left"></i></button></li>
            <li class="ah-pagination ah-curr"><button class="btn palette-Cyan bg disabled">1</button></li>
            <li class="ah-pagination ah-next"><button class="btn palette-Cyan bg"><i class="zmdi zmdi-chevron-right"></i></button></li>
        </ul>
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
            'columns': [
                    @foreach ($cols as $col)
                {
                    data: '{{ $col['name'] }}',
                    name: '{{ $col['name'] }}',
                    @if (isset($col['orderable']))
                    orderable: false,
                    @endif
                            @if (isset($col['render']))
                    render: $.fn.dataTables.render['{!! $col['render'] !!}']({!! isset($col['renderParams']) ? json_encode($col['renderParams']) : '' !!})
                    @endif
                },
                    @endforeach
                    @if (!empty($rowActions))
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
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
                $.fn.dataTables.pagination(settings);
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

        $.fn.dataTables.navigation(dataTable);

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
