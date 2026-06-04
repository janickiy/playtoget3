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
                                    <a href="{{ route('admin.catalog.create') }}" class="btn btn-info btn-sm pull-left">
                                        <span class="fa fa-plus"> &nbsp;</span> добавить
                                    </a>
                                </div>

                                <table id="itemList" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>имя</th>
                                        <th style="width: 10%">действия</th>
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
                            "sLengthMenu": "Отображено _MENU_ записей на страницу",
                            "sZeroRecords": "Ничего не найдено - извините",
                            "sInfo": "Показано с _START_ по _END_ из _TOTAL_ записей",
                            "sInfoEmpty": "Показано с 0 по 0 из 0 записей",
                            "sInfoFiltered": "(отфильтровано  _MAX_ всего записей)",
                            "oPaginate": {
                                "sFirst": "Первая",
                                "sLast": "Посл.",
                                "sNext": "След.",
                                "sPrevious": "Пред.",
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
                            url: '{{ route('admin.datatable.catalogs') }}'
                        },
                        'columns': [
                            {data: 'name', name: 'name'},
                            {data: 'actions', name: 'actions', orderable: false, searchable: false}
                        ]
                    });

                    $('#itemList').on('click', 'a.deleteRow', function (event) {
                        event.preventDefault();

                        let rowid = $(this).data('id');
                        let deleteUrl = $(this).attr('href');

                        Swal.fire({
                            title: "Вы уверены?",
                            text: "Вы не сможете восстановить эту информацию!",
                            showCancelButton: true,
                            icon: 'warning',
                            cancelButtonText: "Отмена",
                            confirmButtonText: "Да, удалить!",
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
                                        Swal.fire("Сделано!", "Данные успешно удалены!", 'success');
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        Swal.fire("Ошибка при удалении!", (xhr.responseJSON && xhr.responseJSON.message) || "Попробуйте еще раз", 'error');
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
