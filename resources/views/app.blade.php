<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('frontend.str.admin_panel') }} | @yield('title')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    {!! Html::style('/plugins/fontawesome-free/css/all.min.css') !!}

    {!! Html::style('/plugins/sweetalert2/sweetalert2.min.css') !!}

    <!-- Theme style -->
    {!! Html::style('/dist/css/adminlte.min.css') !!}

    {!! Html::style('/plugins/toastr/toastr.min.css') !!}

    {!! Html::style('/plugins/flag-icon-css/css/flag-icon.min.css') !!}

    @yield('css')

    <script type="text/javascript">
        let SITE_URL = "{{ url('/') }}";
    </script>
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" title="развернуть"
                   href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">

                @if( Config::get('app.locale') == 'ru')
                    <a class="nav-link" data-toggle="dropdown" href="javascript:void(0);">
                        <i class="flag-icon flag-icon-ru"></i>
                    </a>
                @else
                    <a class="nav-link" data-toggle="dropdown" href="javascript:void(0);">
                        <i class="flag-icon flag-icon-us"></i>
                    </a>
                @endif

                <div class="dropdown-menu dropdown-menu-right p-0">
                    <a data-id="en" href="javascript:void(0);" class="dropdown-item select-lang ">
                        <i class="flag-icon flag-icon-us mr-2"></i> English
                    </a>
                    <a data-id="ru" href="javascript:void(0);" class="dropdown-item select-lang " alt="Русский (Russian)">
                        <i class="flag-icon flag-icon-ru mr-2"></i> Русский (Russian)
                    </a>
                </div>
            </li>

            <!-- Notifications Dropdown Menu -->
            <li class="nav-item">
                <a class="nav-link" title="выйти" href="{{ route('logout') }}"
                   role="button">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('admin.dashboard.index') }}" class="brand-link">
            <img src="../../dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
                 style="opacity: .8">
            <span class="brand-text font-weight-light">AdminLTE 3</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="{{ route('admin.admin.edit', ['id' => Auth::user()->id ]) }}"
                       class="d-block">{{ Auth::user()->login }} @if(!empty(Auth::user()->name))
                            ({{ Auth::user()->name }})
                        @endif</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard.index') }}" class="nav-link{{ Request::is('dashboard*') ? ' active' : '' }}"
                           title="dashboard">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.catalog.index') }}" class="nav-link{{ Request::is('catalog*') ? ' active' : '' }}"
                           title="Каталог">
                            <i class="nav-icon fas fa-list"></i>
                            <p>каталог</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission('admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.admin.index') }}" class="nav-link{{ Request::is('admin*') ? ' active' : '' }}"
                               title="пользователи">
                                <i class="nav-icon fas fa-users"></i>
                                <p>пользователи</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}" class="nav-link{{ Request::is('cp/settings*') ? ' active' : '' }}"
                               title="настройки">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>настройки</p>
                            </a>
                        </li>

                    @endif

                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>{{ $title }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Админ панель</a></li>
                            <li class="breadcrumb-item active">{{ $title }}</li>
                        </ol>
                    </div>
                </div>

                @include('notifications')

            </div><!-- /.container-fluid -->
        </section>

        @yield('content')

    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b></b>
        </div>
        <strong>&copy; {{ date('Y') }}</strong>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
{!! Html::script('/plugins/jquery/jquery.min.js') !!}
<!-- Bootstrap 4 -->
{!! Html::script('/plugins/bootstrap/js/bootstrap.bundle.min.js') !!}

{!! Html::script('/plugins/sweetalert2/sweetalert2.min.js') !!}
{!! Html::script('/plugins/toastr/toastr.min.js') !!}

<!-- AdminLTE App -->
{!! Html::script('/dist/js/adminlte.min.js') !!}

@yield('js')

</body>
</html>
