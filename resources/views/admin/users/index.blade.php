@extends('app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
    {!! Html::style('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') !!}

    <style>
        #itemList thead th.users-checkbox-column {
            background-image: none !important;
            cursor: default;
            text-align: center;
        }

        #itemList thead th.users-checkbox-column::before,
        #itemList thead th.users-checkbox-column::after {
            display: none !important;
            content: '' !important;
        }

        #itemList .users-checkbox-column,
        #itemList .users-checkbox-cell {
            text-align: center;
            vertical-align: middle;
        }
    </style>

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
                                    <th class="users-checkbox-column" style="width: 3%">
                                        <input type="checkbox" id="checkAllUsers" title="Отметить / снять отметку у всех">
                                    </th>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Имя</th>
                                    <th>Город</th>
                                    <th>Статус</th>
                                    <th>Создан</th>
                                    <th style="width: 14%">Действия</th>
                                </tr>
                                </thead>
                                <tfoot>

                                </tfoot>
                            </table>

                            <div class="row mt-3">
                                <div class="col-md-4 col-lg-3">
                                    <select id="bulkAction" class="custom-select custom-select-sm">
                                        <option value="">Выберите действие</option>
                                        <option value="block">Заблокировать</option>
                                        <option value="unblock">Разблокировать</option>
                                        <option value="delete">Удалить</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-lg-2 mt-2 mt-md-0">
                                    <button type="button" id="bulkApply" class="btn btn-primary btn-sm">
                                        Применить
                                    </button>
                                </div>
                            </div>
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
                "order": [[1, 'desc']],
                "ajax": {
                    url: '{{ route('admin.datatable.users') }}'
                },
                "columnDefs": [
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        className: 'users-checkbox-cell'
                    },
                    {
                        targets: -1,
                        orderable: false,
                        searchable: false
                    }
                ],
                "columns": [
                    {data: 'checkbox', name: 'checkbox'},
                    {data: 'id', name: 'id'},
                    {data: 'email', name: 'email'},
                    {data: 'name', name: 'firstname'},
                    {data: 'city', name: 'city'},
                    {data: 'status', name: 'status'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions'}
                ]
            });

            function updateCheckAllUsersState() {
                let all = $('.js-user-checkbox').length;
                let checked = $('.js-user-checkbox:checked').length;

                $('#checkAllUsers')
                    .prop('checked', all > 0 && all === checked)
                    .prop('indeterminate', checked > 0 && checked < all);
            }

            table.on('draw', function () {
                $('#checkAllUsers').prop('checked', false).prop('indeterminate', false);
            });

            $('#checkAllUsers').on('click', function (event) {
                event.stopPropagation();
            }).on('change', function () {
                $('.js-user-checkbox').prop('checked', this.checked);
                updateCheckAllUsersState();
            });

            $('#itemList').on('change', '.js-user-checkbox', function () {
                updateCheckAllUsersState();
            });

            $('#itemList').on('click', 'a.statusRow', function (event) {
                event.preventDefault();

                let statusUrl = $(this).attr('href');
                let action = $(this).data('action');
                let text = action === 'block'
                    ? 'Пользователь будет заблокирован.'
                    : 'Пользователь будет разблокирован.';

                Swal.fire({
                    title: "Вы уверены?",
                    text: text,
                    showCancelButton: true,
                    icon: 'warning',
                    cancelButtonText: "Отмена",
                    confirmButtonText: "Да, применить!",
                    reverseButtons: true,
                    confirmButtonColor: "#DD6B55"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: statusUrl,
                            type: "PATCH",
                            dataType: "json",
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function (response) {
                                table.ajax.reload(null, false);
                                Swal.fire("Сделано!", response.message || "Данные успешно обновлены!", 'success');
                            },
                            error: function (xhr) {
                                Swal.fire("Ошибка!", (xhr.responseJSON && xhr.responseJSON.message) || "Попробуйте еще раз", 'error');
                            }
                        });
                    }
                });
            });

            $('#itemList').on('click', 'a.deleteRow', function (event) {
                event.preventDefault();

                let deleteUrl = $(this).attr('href');

                Swal.fire({
                    title: "Вы уверены?",
                    text: "Пользователь будет удален из списка.",
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
                                Swal.fire("Сделано!", response.message || "Пользователь удален!", 'success');
                            },
                            error: function (xhr) {
                                Swal.fire("Ошибка при удалении!", (xhr.responseJSON && xhr.responseJSON.message) || "Попробуйте еще раз", 'error');
                            }
                        });
                    }
                });
            });

            $('#bulkApply').on('click', function () {
                let action = $('#bulkAction').val();
                let ids = $('.js-user-checkbox:checked').map(function () {
                    return $(this).val();
                }).get();

                if (!action) {
                    Swal.fire("Выберите действие", "Нужно выбрать действие для применения.", 'warning');
                    return;
                }

                if (ids.length === 0) {
                    Swal.fire("Выберите пользователей", "Отметьте хотя бы одного пользователя.", 'warning');
                    return;
                }

                Swal.fire({
                    title: "Вы уверены?",
                    text: "Действие будет применено к выбранным пользователям.",
                    showCancelButton: true,
                    icon: 'warning',
                    cancelButtonText: "Отмена",
                    confirmButtonText: "Да, применить!",
                    reverseButtons: true,
                    confirmButtonColor: "#DD6B55"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('admin.users.bulk') }}',
                            type: "POST",
                            dataType: "json",
                            data: {
                                action: action,
                                ids: ids
                            },
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function (response) {
                                $('#bulkAction').val('');
                                table.ajax.reload(null, false);
                                Swal.fire("Сделано!", response.message || "Действие выполнено!", 'success');
                            },
                            error: function (xhr) {
                                Swal.fire("Ошибка!", (xhr.responseJSON && xhr.responseJSON.message) || "Попробуйте еще раз", 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

@endsection
