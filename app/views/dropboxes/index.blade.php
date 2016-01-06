<h1>Dropbox Trees</h1>
@foreach ($dropboxes as $dropbox)
	<li>
		<a href="{{ route('dropboxes.show', $dropbox->id) }}">
			Dropbox #{{ $dropbox->dropbox_authorized_id }} belonging to Demo user #{{ $dropbox->id }}
		</a>
	</li>
@endforeach