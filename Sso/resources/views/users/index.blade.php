@extends('layouts.app')

@section('title', 'Users')

@section('content')

    <div class="c-header">
        <h2>Users</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of Users <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'id' => [
                    'header' => 'ID',
                    'priority' => 1,
                ],
                'email' => [
                    'header' => 'Email',
                    'priority' => 1,
                ],
                'name' => [
                    'header' => 'Name',
                    'priority' => 1,
                ],
                'created_at' => [
                    'header' => 'Created at',
                    'render' => 'date',
                    'priority' => 2,
                ],
                'updated_at' => [
                    'header' => 'Updated at',
                    'render' => 'date',
                    'priority' => 3,
                ],
            ],
            'dataSource' => route('users.json'),
            'rowActions' => [
                ['name' => 'destroy', 'class' => 'zmdi-palette-Cyan zmdi-delete', 'title' => 'Remove user'],
            ],
            'order' => [0, 'desc'],
        ]) !!}
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $(document).on('submit', 'form[data-action-method="DELETE"]', function(e) {
            var $form = $(this);
            var actionUrl = $form.attr('action');

            // Check if this is a user deletion form
            if (actionUrl && actionUrl.indexOf('/users/') !== -1) {
                if (!confirm('Are you sure you want to remove this user? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });
</script>
@endpush
