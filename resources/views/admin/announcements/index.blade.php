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
                            <div class="pb-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <a href="{{ route('admin.announcements.create') }}" class="btn btn-info btn-sm pull-left">
                                            <span class="fa fa-plus"> &nbsp;</span> Add
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Content</th>
                                    <th>Slug</th>
                                    <th>Published</th>
                                    <th>Created</th>
                                    <th style="width: 10%">Actions</th>
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
                'createdRow': function (row, data) {
                    $(row).attr('id', 'rowid_' + data['id']);
                },
                "processing": true,
                "responsive": true,
                "autoWidth": true,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.announcements') }}'
                },
                'columns': [
                    {data: 'id', name: 'id'},
                    {data: 'title', name: 'title'},
                    {data: 'text', name: 'text'},
                    {data: 'slug', name: 'slug'},
                    {data: 'published', name: 'published'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
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
                        cancelButton: 'order-1'
                    }
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
