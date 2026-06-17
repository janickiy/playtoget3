@extends('app')

@section('title', $title)

@section('css')
    {!! Html::style('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') !!}
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>IP</th>
                                    <th>User Agent</th>
                                    <th>Login date</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    {!! Html::script('/plugins/datatables/jquery.dataTables.min.js') !!}
    {!! Html::script('/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/datatables-responsive/js/dataTables.responsive.min.js') !!}
    {!! Html::script('/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/dataTables.buttons.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/pdfmake/pdfmake.min.js') !!}
    {!! Html::script('/plugins/pdfmake/vfs_fonts.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.html5.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.print.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.colVis.min.js') !!}

    <script>
        $(function () {
            $("#itemList").DataTable({
                "oLanguage": {
                    "sLengthMenu": "Show _MENU_ entries per page",
                    "sZeroRecords": "No matching records found",
                    "sInfo": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "sInfoEmpty": "Showing 0 to 0 of 0 entries",
                    "sInfoFiltered": "(filtered from _MAX_ total entries)",
                    "oPaginate": {
                        "sFirst": "First",
                        "sLast": "Last",
                        "sNext": "Next",
                        "sPrevious": "Previous"
                    },
                    "sSearch": ' <i class="fas fa-search" aria-hidden="true"></i>'
                },
                "processing": true,
                "responsive": true,
                "autoWidth": true,
                "serverSide": true,
                "order": [[4, "desc"]],
                "ajax": {
                    url: '{{ route('admin.datatable.logs') }}'
                },
                "columns": [
                    {data: 'id', name: 'logs.id'},
                    {data: 'user', name: 'user', orderable: false},
                    {data: 'ip', name: 'logs.ip'},
                    {data: 'user_agent', name: 'logs.user_agent'},
                    {data: 'last_sign_in_at', name: 'logs.last_sign_in_at'}
                ]
            });
        });
    </script>
@endsection
