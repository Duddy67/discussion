@php $format = (isset($daypicker)) ? 'HH:m' : 'Do MMM YY'; @endphp
<tr class="{{ $discussion->isUserRegistered() ? 'table-primary' : '' }}">
    <th scope="row"><a href="{{ url($discussion->getUrl()) }}{{ isset($daypicker) ? '?_day_picker='.$daypicker : '' }}">{{ $discussion->subject }}</a></th>
    @if (isset($daypicker))
        <td><span class="badge bg-secondary">{{ $discussion->category->name }}</span></td>
    @endif
    <td>{{ \Carbon\Carbon::parse($discussion->discussion_date, $page['timezone'])->isoFormat($format) }}</td>
    <td>{{ $discussion->organizer }}</td>
    <td>{{ $discussion->getAttendees()->count() }}/{{ $discussion->max_attendees }}</td>
</tr>
