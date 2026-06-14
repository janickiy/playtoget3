<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Админ панель | @yield('title')</title>

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

    @yield('css')

    <script>
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

                    <!-- Sidebar Menu -->
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                            data-accordion="true">
                            <!-- Add icons to the links using the .nav-icon class
                                 with font-awesome or any other icon font library -->

                            <li class="nav-item">
                                <a href="{{ route('admin.dashboard.index') }}"
                                   class="nav-link{{ Request::is('cp') ? ' active' : '' }}"
                                   title="Главная">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>Главная</p>
                                </a>
                            </li>

                            <li class="nav-item{{ Request::is('cp/content*') ? ' menu-open' : '' }}">
                                <a href="#" class="nav-link{{ Request::is('cp/content*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-book"></i>
                                    <p>
                                        Контент
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.menu.index') }}"
                                           class="nav-link{{ Request::is('cp/content/manage-menus*') ? ' active' : '' }}"
                                           title="Меню">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Меню</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('admin.content.index') }}"
                                           class="nav-link{{ Request::is('cp/content/content*') ? ' active' : '' }}"
                                           title="Страницы и разделы">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Страницы и разделы</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>


                    @if(PermissionsHelper::has_permission('admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.admin.index') }}" class="nav-link{{ Request::is('cp/admin*') ? ' active' : '' }}"
                               title="Администраторы">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Администраторы</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link{{ Request::is('cp/users*') ? ' active' : '' }}"
                               title="Пользователи">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Пользователи</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.communities.index') }}" class="nav-link{{ Request::is('cp/communities*') ? ' active' : '' }}"
                               title="Комьюнити">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>Комьюнити</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.events.index') }}" class="nav-link{{ Request::is('cp/events*') ? ' active' : '' }}"
                               title="Мероприятия">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Мероприятия</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.announcements.index') }}" class="nav-link{{ Request::is('cp/announcements*') ? ' active' : '' }}"
                               title="Объявления">
                                <i class="nav-icon fas fa-bullhorn"></i>
                                <p>Объявления</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.feedback.index') }}" class="nav-link{{ Request::is('cp/feedback*') ? ' active' : '' }}"
                               title="Обратная связь">
                                <i class="nav-icon fas fa-envelope"></i>
                                <p>Обратная связь</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.sport-blocks.index') }}" class="nav-link{{ Request::is('cp/sport-blocks*') ? ' active' : '' }}"
                               title="Спортивные блоки">
                                <i class="nav-icon fas fa-running"></i>
                                <p>Спортивные блоки</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}" class="nav-link{{ Request::is('cp/settings*') ? ' active' : '' }}"
                               title="Настройки">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Настройки</p>
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
        <strong>&copy; {{ date('Y') }} {{ config('app.name') }}</strong>
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
