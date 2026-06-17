@extends('app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
    {!! Html::style('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') !!}

@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">

                        <div class="card">
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="pb-3">
                                    <a href="{{ route('admin.admin.create') }}" class="btn btn-info btn-sm pull-left">
                                        <span class="fa fa-plus"> &nbsp;</span> add
                                    </a>
                                </div>

                                <table id="itemList" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>login</th>
                                        <th>name</th>
                                        <th>role</th>
                                        <th style="width: 10%">actions</th>
                                    </tr>
                                    </thead>
                                    <tfoot>

                                    </tfoot>
                                </table>

                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->

        </section>
        <!-- /.content -->

        @endsection

        @section('js')

            <!-- DataTables  & Plugins -->
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

                $(function (){

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
                                "sPrevious": "Previous",
                            },
                            "sSearch": ' <i class="fas fa-search" aria-hidden="true"></i>'
                        },
                        'createdRow': function (row, data, dataIndex) {
                            $(row).attr('id', 'rowid_' + data['id']);
                        },
                        "processing": true,
                        "responsive": true,
                        "autoWidth": true,
                        'serverSide': true,
                        'ajax': {
                            url: '{{ route('admin.datatable.admin') }}'
                        },
                        'columns': [
                            {data: 'login', name: 'login'},
                            {data: 'name', name: 'name'},
                            {data: 'role', name: 'role'},
                            {data: 'action', name: 'action', orderable: false, searchable: false}
                        ]
                    });

                    $('#itemList').on('click', 'a.deleteRow', function (event) {
                        event.preventDefault();

                        let rowid = $(this).data('id');
                        let deleteUrl = $(this).attr('href');

                        Swal.fire({
                            title: "Are you sure?",
                            text: "You will not be able to restore this information!",
                            showCancelButton: true,
                            icon: 'warning',
                            cancelButtonText: "Cancel",
                            confirmButtonText: "Yes, delete!",
                            reverseButtons: true,
                            confirmButtonColor: "#DD6B55",
                            customClass: {
                                actions: 'my-actions',
                                cancelButton: 'order-1',
                            },
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: deleteUrl,
                                    type: "DELETE",
                                    dataType: "json",
                                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                    success: function () {
                                        $("#rowid_" + rowid).remove();
                                        Swal.fire("Done!", "Data deleted successfully!", 'success');
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        Swal.fire("Deletion error!", (xhr.responseJSON && xhr.responseJSON.message) || "Please try again", 'error');
                                        console.log(ajaxOptions);
                                        console.log(thrownError);
                                    }
                                });
                            }
                        });
                    });
                });

            </script>

@endsection
