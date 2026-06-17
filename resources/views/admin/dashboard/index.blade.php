@extends('app')

@section('title', $title)

@section('css')
    <style>
        .dashboard-quick-links .list-group-item {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .dashboard-table td,
        .dashboard-table th {
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                @foreach($stats as $item)
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="small-box {{ $item['class'] }}">
                            <div class="inner">
                                <h3>{{ $item['value'] }}</h3>
                                <p class="mb-0">{{ $item['label'] }}</p>
                                <small>{{ $item['description'] }}</small>
                            </div>
                            <div class="icon">
                                <i class="{{ $item['icon'] }}"></i>
                            </div>
                            <a href="{{ $item['url'] }}" class="small-box-footer">
                                Open section <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick actions</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush dashboard-quick-links">
                                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
                                    <span><i class="fas fa-user mr-2"></i> Manage users</span>
                                    @if($newUsersCount > 0)
                                        <span class="badge badge-success">{{ $newUsersCount }} new</span>
                                    @endif
                                </a>
                                <a href="{{ route('admin.feedback.index') }}" class="list-group-item list-group-item-action">
                                    <span><i class="fas fa-envelope mr-2"></i> Review feedback</span>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </a>
                                <a href="{{ route('admin.content.index') }}" class="list-group-item list-group-item-action">
                                    <span><i class="fas fa-file-alt mr-2"></i> Edit pages</span>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </a>
                                <a href="{{ route('admin.settings.index') }}" class="list-group-item list-group-item-action">
                                    <span><i class="fas fa-cogs mr-2"></i> Settings</span>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Latest feedback</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.feedback.index') }}" class="btn btn-tool" title="Open feedback">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover dashboard-table mb-0">
                                <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Sender</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestFeedback as $feedback)
                                    <tr>
                                        <td>{{ $feedback->subject ?: '-' }}</td>
                                        <td>
                                            <div>{{ $feedback->name ?: '-' }}</div>
                                            <small class="text-muted">{{ $feedback->email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $feedback->statusEnum()->cssColor() ?: 'badge-light' }}">
                                                {{ $feedback->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>{{ optional($feedback->time)->format('d.m.Y H:i') ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.feedback.show', ['id' => $feedback->id]) }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No feedback yet.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">New users</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-tool" title="Open users">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover dashboard-table mb-0">
                                <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestUsers as $user)
                                    @php
                                        $name = trim($user->firstname . ' ' . $user->lastname);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $name !== '' ? $name : 'User #' . $user->id }}</div>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $user->statusEnum()->cssColor() ?: 'badge-light' }}">
                                                {{ $user->statusEnum()->label() }}
                                            </span>
                                        </td>
                                        <td>{{ optional($user->created_at)->format('d.m.Y H:i') ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.users.show', ['id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No users yet.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Latest sign-ins</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.logs.index') }}" class="btn btn-tool" title="Open logs">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover dashboard-table mb-0">
                                <thead>
                                <tr>
                                    <th>User</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestLogs as $log)
                                    @php
                                        $logUserName = $log->user ? trim($log->user->firstname . ' ' . $log->user->lastname) : '';
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($log->user)
                                                <div>{{ $logUserName !== '' ? $logUserName : 'User #' . $log->user->id }}</div>
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            @else
                                                <span class="text-muted">Unknown user</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->ip ?: '-' }}</td>
                                        <td>{{ optional($log->last_sign_in_at)->format('d.m.Y H:i') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No sign-ins yet.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
@endsection
