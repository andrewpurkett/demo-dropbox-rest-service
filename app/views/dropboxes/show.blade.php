<h1>Tree Entries</h1>
@foreach ($entries as $entry)
	<li>
		<a href="{{ route('entries.show', $entry->id) }}">
			{{ $entry->path }} ({{ $entry->size}})
		</a>
	</li>
@endforeach