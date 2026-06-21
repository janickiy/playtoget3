@php
    $bulkAlbums = $bulkAlbums ?? collect();
    $currentAlbumId = (int) ($currentAlbumId ?? 0);
    $target = $target ?? '#photo-list';
@endphp

@if (($canManage ?? false) && $bulkAlbums->isNotEmpty())
    <div
        class="photo-bulk-actions"
        data-target="{{ $target }}"
        data-current-album="{{ $currentAlbumId }}"
    >
        <button
            type="button"
            class="photo-bulk-toggle"
        >Select all photos</button>

        <div class="photo-bulk-controls">
            <div class="styled-select styled-select-4 photo-bulk-album">
                <select class="photo-bulk-target-album" aria-label="Target album">
                    <option value="">Choose album</option>
                    @foreach ($bulkAlbums as $bulkAlbum)
                        @if ($currentAlbumId !== (int) $bulkAlbum->id)
                            <option value="{{ $bulkAlbum->id }}">{{ $bulkAlbum->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <button type="button" class="photo-bulk-move" disabled>Move selected</button>
            <button type="button" class="photo-bulk-delete" disabled>Delete selected</button>
        </div>

        <div class="photo-bulk-message" aria-live="polite"></div>
    </div>
@endif
