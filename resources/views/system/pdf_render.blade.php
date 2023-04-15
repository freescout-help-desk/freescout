<table>
@foreach (explode("\n", $table_data) as $key => $row)
    <tr>
        @foreach (explode("\t", $row) as $cell)
            <td>{{ $cell }}</td>
        @endforeach
    </tr>
@endforeach
</table>