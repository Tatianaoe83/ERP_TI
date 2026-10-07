@props([
    'title',
    'subtitle' => null,
    'icon' => 'fa-sitemap',
    'listTitle',
    'listCount' => null,
    'createUrl' => null,
    'createPermission' => null,
    'createLabel' => 'Nuevo',
])

@php
    $canCreate = $createUrl && (
        empty($createPermission) || (auth()->check() && auth()->user()->can($createPermission))
    );
@endphp

@once
    <style>
            .cat-org { display: flex; flex-direction: column; gap: 1.25rem; }
            .cat-org > .index-page { padding-bottom: 0; }
            .cat-org > .index-page .index-page__header { margin-bottom: 0; }
            .cat-org__card {
                background: #fff; border-radius: 1rem; overflow: hidden;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                border: 1px solid rgba(226, 232, 240, 0.9);
            }
            .cat-org__card-head {
                display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
                padding: 0.9rem 1.15rem; border-bottom: 1px solid #f1f5f9;
            }
            .cat-org__card-icon {
                width: 2.25rem; height: 2.25rem; border-radius: 0.55rem;
                display: inline-flex; align-items: center; justify-content: center;
                background: #eff6ff; color: #1d4ed8; flex-shrink: 0;
                box-shadow: inset 0 0 0 1px #dbeafe;
            }
            .cat-org__card-title { margin: 0; font-size: 0.9rem; font-weight: 650; color: #0f172a; }
            .cat-org__card-count { margin: 0; font-size: 0.75rem; color: #64748b; }
            .cat-org__filters { margin-left: auto; display: flex; gap: 0.5rem; flex-wrap: wrap; }
            .cat-org__filters select {
                border: 1px solid #e2e8f0; border-radius: 0.55rem; background: #fff;
                color: #334155; font-size: 0.8125rem; padding: 0.45rem 0.7rem; min-width: 12rem;
            }
            .cat-org__table { width: 100%; border-collapse: collapse; }
            .cat-org__table th {
                text-align: left; font-size: 0.68rem; letter-spacing: 0.06em; text-transform: uppercase;
                color: #64748b; font-weight: 650; padding: 0.75rem 1.15rem; background: rgba(248, 250, 252, 0.9);
                border-bottom: 1px solid #f1f5f9; white-space: nowrap;
            }
            .cat-org__table td {
                padding: 0.85rem 1.15rem; font-size: 0.875rem; color: #475569;
                border-bottom: 1px solid #f1f5f9; vertical-align: middle;
            }
            .cat-org__table tr:last-child td { border-bottom: 0; }
            .cat-org__table tbody tr:hover td { background: #f8fafc; }
            .cat-org__name { display: flex; align-items: center; gap: 0.7rem; color: #0f172a; font-weight: 600; }
            .cat-org__avatar {
                width: 2.15rem; height: 2.15rem; border-radius: 0.55rem; flex-shrink: 0;
                display: inline-flex; align-items: center; justify-content: center;
                background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e3a8a;
                font-size: 0.85rem; font-weight: 700;
            }
            .cat-org__pill {
                display: inline-flex; align-items: center; gap: 0.25rem;
                border-radius: 0.4rem; padding: 0.15rem 0.5rem; font-size: 0.75rem; font-weight: 600;
                background: #eff6ff; color: #1e40af; box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.15);
                white-space: nowrap;
            }
            .cat-org__pill--muted {
                background: #f1f5f9; color: #334155; box-shadow: inset 0 0 0 1px rgba(100, 116, 139, 0.16);
            }
            .cat-org__path { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
            .cat-org__chev { color: #94a3b8; font-size: 0.7rem; }
            .cat-org__estado {
                display: inline-flex; align-items: center; gap: 0.35rem;
                border-radius: 0.4rem; padding: 0.15rem 0.5rem; font-size: 0.75rem; font-weight: 600;
                background: #ecfdf5; color: #047857; box-shadow: inset 0 0 0 1px rgba(5, 150, 105, 0.16);
            }
            .cat-org__estado i { font-size: 0.45rem; }
            .cat-org__estado--off { background: #f1f5f9; color: #475569; box-shadow: inset 0 0 0 1px rgba(100, 116, 139, 0.16); }
            .cat-org__actions { display: flex; justify-content: flex-end; }
            .cat-org__actions .index-action--edit { color: #6366f1; }
            .cat-org__actions .index-action--edit:hover { background: #eef2ff; color: #4338ca; }
            .cat-org__actions .index-action--delete { color: #f87171; }
            .cat-org__empty { text-align: center; color: #64748b; padding: 2.5rem 1rem !important; }
            .cat-org__pages { padding: 0.75rem 1.15rem; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.7); }
            html.dark .cat-org__card { background: #1c1f26; border-color: #2a2f3a; }
            html.dark .cat-org__card-head,
            html.dark .cat-org__table td,
            html.dark .cat-org__pages { border-color: #2a2f3a; }
            html.dark .cat-org__card-title,
            html.dark .cat-org__name { color: #f9fafb; }
            html.dark .cat-org__card-count,
            html.dark .cat-org__table td { color: #d1d5db; }
            html.dark .cat-org__table th { background: #242933; color: #9ca3af; border-bottom-color: #2a2f3a; }
            html.dark .cat-org__table tbody tr:hover td { background: #273244; }
            html.dark .cat-org__filters select {
                background: #111827; color: #f9fafb; border-color: #374151;
            }
            html.dark .cat-org__card-icon {
                background: #1e3a5f; color: #93c5fd;
                box-shadow: inset 0 0 0 1px rgba(147, 197, 253, 0.35);
            }
            html.dark .cat-org__avatar {
                background: #1e3a5f; color: #dbeafe;
            }
            html.dark .cat-org__pill {
                background: rgba(37, 99, 235, 0.22); color: #bfdbfe;
                box-shadow: inset 0 0 0 1px rgba(147, 197, 253, 0.4);
            }
            html.dark .cat-org__pill--muted {
                background: #242933; color: #e5e7eb;
                box-shadow: inset 0 0 0 1px #374151;
            }
            html.dark .cat-org__chev { color: #9ca3af; }
            html.dark .cat-org__estado {
                background: rgba(16, 185, 129, 0.16); color: #6ee7b7;
                box-shadow: inset 0 0 0 1px rgba(110, 231, 183, 0.35);
            }
            html.dark .cat-org__estado--off {
                background: #242933; color: #d1d5db;
                box-shadow: inset 0 0 0 1px #374151;
            }
            html.dark .cat-org__actions .index-action--edit { color: #a5b4fc; }
            html.dark .cat-org__actions .index-action--edit:hover { background: rgba(99, 102, 241, 0.18); color: #c7d2fe; }
            html.dark .cat-org__actions .index-action--delete { color: #fca5a5; }
            html.dark .cat-org__pages { background: #161920; }
            html.dark .cat-org__empty { color: #9ca3af; }
    </style>
@endonce

<div class="cat-org">
    <div class="index-page">
        <div class="index-page__header">
            <div class="index-page__heading">
                <span class="index-page__icon" aria-hidden="true">
                    <i class="fas {{ $icon }}"></i>
                </span>
                <div>
                    <h1 class="index-page__title">{{ $title }}</h1>
                    @if($subtitle)
                        <span class="index-page__count">{{ $subtitle }}</span>
                    @endif
                </div>
            </div>
            @if($canCreate)
                <div class="index-page__header-actions">
                    <a href="{{ $createUrl }}" class="index-page__btn-primary">+ {{ $createLabel }}</a>
                </div>
            @endif
        </div>
    </div>

    <section class="cat-org__card">
        <header class="cat-org__card-head">
            <span class="cat-org__card-icon"><i class="fas {{ $icon }}"></i></span>
            <div>
                <h3 class="cat-org__card-title">{{ $listTitle }}</h3>
                @if($listCount)
                    <p class="cat-org__card-count">{{ $listCount }}</p>
                @endif
            </div>
            @isset($filters)
                <div class="cat-org__filters">{{ $filters }}</div>
            @endisset
        </header>
        <div style="overflow-x:auto;">
            {{ $slot }}
        </div>
        @isset($pages)
            <div class="cat-org__pages">{{ $pages }}</div>
        @endisset
    </section>
</div>
