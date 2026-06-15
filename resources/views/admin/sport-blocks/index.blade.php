@extends('app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
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
                                    <th>Тип</th>
                                    <th>Название</th>
                                    <th>Место</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Рекомендовано</th>
                                    <th>Статус</th>
                                    <th>Создано</th>
                                    <th style="width: 12%">Действия</th>
                                </tr>
                                </thead>
                                <tfoot>

                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
        $(function () {
            let table = $("#itemList").DataTable({
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
                        "sPrevious": "Пред."
                    },
                    "sSearch": ' <i class="fas fa-search" aria-hidden="true"></i>'
                },
                "createdRow": function (row, data) {
                    $(row).attr('id', 'rowid_' + data['id']);

                    if (data['status_css']) {
                        $(row).addClass(data['status_css']);
                    }
                },
                "processing": true,
                "responsive": true,
                "autoWidth": true,
                "serverSide": true,
                "ajax": {
                    url: '{{ route('admin.datatable.sport-blocks') }}'
                },
                "columns": [
                    {data: 'id', name: 'id'},
                    {data: 'type', name: 'type'},
                    {data: 'name', name: 'name'},
                    {data: 'place', name: 'place'},
                    {data: 'email', name: 'email'},
                    {data: 'phone', name: 'phone'},
                    {data: 'recommended', name: 'recommended'},
                    {data: 'status', name: 'status'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ]
            });

            $('#itemList').on('click', 'a.deleteRow', function (event) {
                event.preventDefault();

                let deleteUrl = $(this).attr('href');

                Swal.fire({
                    title: "Вы уверены?",
                    text: "Вы не сможете восстановить эту информацию!",
                    showCancelButton: true,
                    icon: 'warning',
                    cancelButtonText: "Отмена",
                    confirmButtonText: "Да, удалить!",
                    reverseButtons: true,
                    confirmButtonColor: "#DD6B55"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: "DELETE",
                            dataType: "json",
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function (response) {
                                table.ajax.reload(null, false);
                                Swal.fire("Сделано!", response.message || "Данные успешно удалены!", 'success');
                            },
                            error: function (xhr) {
                                Swal.fire("Ошибка при удалении!", (xhr.responseJSON && xhr.responseJSON.message) || "Попробуйте еще раз", 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

@endsection
