@php
    $realInventoryHeading = (string) ($realInventoryHeading ?? '');
@endphp
@foreach($rows as $row)
    <tr>
        @foreach($row as $index => $cell)
            @php
                $isRealCol = $realInventoryHeading !== '' && isset($headings[$index]) && $headings[$index] === $realInventoryHeading;
            @endphp
            <td class="{{ $isRealCol ? 'fv-pdf-col-real' : '' }}">{{ $cell }}</td>
        @endforeach
    </tr>
@endforeach
