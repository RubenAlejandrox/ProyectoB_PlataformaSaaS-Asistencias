@extends('layouts.app')

@section('title', 'Notificaciones - GAMA Solutions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}">
@endpush

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>Notificaciones</h1>
                <p>Historial de alertas, justificantes y sesiones</p>
            </div>
            @if($stats['unread'] > 0)
            <div class="header-actions">
                <form method="POST" action="{{ route('notificaciones.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-md">
                        <i class="fas fa-check-double"></i> Marcar todas leídas
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div style="display:flex;align-items:center;gap:.6rem;background:#d1fae5;border-left:4px solid #28A745;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;color:#065f46;">
            <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="kpi-grid" style="margin-bottom:1.25rem;">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-bell"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['total'] }}</span>
                <span class="kpi-label">Total</span>
            </div>
        </div>
        <div class="kpi-card kpi-card--highlight">
            <div class="kpi-icon"><i class="fas fa-envelope"></i></div>
            <div class="kpi-content">
                <span class="kpi-value">{{ $stats['unread'] }}</span>
                <span class="kpi-label">Sin leer</span>
            </div>
        </div>
    </div>

    <div class="notif-filters">
        <a href="{{ route('notificaciones.index', ['filter' => 'all']) }}"
           class="notif-filter {{ $filter === 'all' ? 'active' : '' }}">Todas</a>
        <a href="{{ route('notificaciones.index', ['filter' => 'unread']) }}"
           class="notif-filter {{ $filter === 'unread' ? 'active' : '' }}">Sin leer</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($notifications->isEmpty())
                <p style="text-align:center;padding:2.5rem;color:#6b7280;">
                    <i class="fas fa-bell-slash" style="font-size:2rem;display:block;margin-bottom:.75rem;"></i>
                    No hay notificaciones{{ $filter === 'unread' ? ' sin leer' : '' }}.
                </p>
            @else
                <ul class="notif-list">
                    @foreach($notifications as $notif)
                        @php
                            $iconClass = match($notif->type) {
                                \App\Models\StudentNotification::TYPE_TRAFFIC_LIGHT => 'notif-icon--traffic',
                                \App\Models\StudentNotification::TYPE_JUSTIFICATION_APPROVED => 'notif-icon--approved',
                                \App\Models\StudentNotification::TYPE_JUSTIFICATION_REJECTED => 'notif-icon--rejected',
                                default => 'notif-icon--session',
                            };
                            $icon = match($notif->type) {
                                \App\Models\StudentNotification::TYPE_TRAFFIC_LIGHT => 'fa-exclamation-triangle',
                                \App\Models\StudentNotification::TYPE_JUSTIFICATION_APPROVED => 'fa-check-circle',
                                \App\Models\StudentNotification::TYPE_JUSTIFICATION_REJECTED => 'fa-times-circle',
                                default => 'fa-calendar-check',
                            };
                        @endphp
                        <li class="notif-item {{ $notif->isUnread() ? 'notif-item--unread' : '' }}">
                            <div class="notif-icon {{ $iconClass }}">
                                <i class="fas {{ $icon }}"></i>
                            </div>
                            <div class="notif-body">
                                <p class="notif-title">{{ $notif->title }}</p>
                                <p class="notif-message">{{ $notif->message }}</p>
                                <p class="notif-meta">
                                    {{ $notif->created_at->diffForHumans() }}
                                    @if($notif->classroom)
                                        · {{ $notif->classroom->subject_name }}
                                    @endif
                                </p>
                            </div>
                            @if($notif->isUnread())
                            <div class="notif-actions">
                                <form method="POST" action="{{ route('notificaciones.read', $notif) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline btn-sm" title="Marcar leída">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div style="padding:1rem;">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
