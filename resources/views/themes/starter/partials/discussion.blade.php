<tr>
    <th scope="row"><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject }}</a></th>
    <td>@date ($discussion->discussion_date->tz($timezone))</td>
    <td>{{ $discussion->organizer }}</td>
    <td>{{ $discussion->getAttendees()->count() }}/{{ $discussion->max_attendees }}</td>
</tr>
