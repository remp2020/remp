<div class="table-responsive">
    <div class="action-header m-0 palette-White bg clearfix">
        <div class="ah-search" style="display: none;">
            <input placeholder="Search" class="ahs-input b-0" type="text">
            <i class="ah-search-close zmdi zmdi-long-arrow-left" data-ma-action="ah-search-close"></i>
        </div>

        <ul class="ah-actions actions a-alt">
            <li><button class="btn palette-Cyan bg ah-search-trigger" data-ma-action="ah-search-open"><i class="zmdi zmdi-search"></i></button></li>
            <li class="ah-length dropdown">
                <button class="btn palette-Cyan bg" data-toggle="dropdown">10</button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li class="active" data-value="2"><a class="dropdown-item dropdown-item-button">10</a></li>
                    <li data-value="25"><a class="dropdown-item dropdown-item-button">25</a></li>
                    <li data-value="50"><a class="dropdown-item dropdown-item-button">50</a></li>
                    <li data-value="-1"><a class="dropdown-item dropdown-item-button">All</a></li>
                </ul>
            </li>
            <li class="ah-pagination ah-prev"><button class="btn palette-Cyan bg"><i class="zmdi zmdi-chevron-left"></i></button></li>
            <li class="ah-pagination ah-page dropdown">
                <button class="btn palette-Cyan bg" data-toggle="dropdown">Page 1</button>
                <ul class="dropdown-menu dropdown-menu-right">
                </ul>
            </li>
            <li class="ah-pagination ah-next"><button class="btn palette-Cyan bg"><i class="zmdi zmdi-chevron-right"></i></button></li>
        </ul>
    </div>

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
            <th>actions</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        var dataTable = $('#{{ $tableId }}').DataTable({
            'columns': [
                @foreach ($cols as $col)
                {
                    name: '{{ $col['name'] }}',
                    @if (isset($col['orderable']))
                        orderable: false,
                    @endif
                    @if (isset($col['render']))
                    render: $.fn.dataTables.render.{!! $col['render'] !!}()
                    @endif
                },
                @endforeach
                {
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            'autoWidth': false,
            'sDom': 't',
            'processing': true,
            'serverSide': true,
            'ajax': '{{ $dataSource }}',
            'drawCallback': function(settings) {
                $.fn.dataTables.pagination(settings);
            }
        });

        $.fn.dataTables.navigation(dataTable);

        @if (!empty($rowLink))
        $('#{{ $tableId }} tbody').on('click', 'tr', function () {
            var data = dataTable.row(this).data();
            window.location.href = '{!!$rowLink !!}' + '/' + data.RowId;
        } );
        @endif
    });
</script>