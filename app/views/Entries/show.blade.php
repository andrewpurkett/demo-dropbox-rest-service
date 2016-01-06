<h1>{{ basename($entry->path) }}</h1>

<xmp>
    id:                                {{ $entry->id }}

    dropbox_id:                        {{ $entry->dropbox_id }}

    parent_id:                         {{ $entry->parent_id }}

    path:                              {{ $entry->path }}

    rev:                               {{ $entry->rev }}

    size:                              {{ $entry->size }}

    bytes:                             {{ $entry->bytes }}

    icon:                              {{ $entry->icon }}

    mime_type:                         {{ $entry->mime_type }}

    root:                              {{ $entry->root }}

    file_modified:                     {{ $entry->file_modified }}

    client_modified:                   {{ $entry->client_modified }}

    is_dir:                            {{ $entry->is_dir }}

    folder_hash:                       {{ $entry->folder_hash }}

</xmp>