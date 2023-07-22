<tr>
    <th scope="row">1</th>
    <td><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject }}</a></td>
    <td>@date ($discussion->discussion_date->tz($timezone))</td>
    <td>{{ $discussion->getAttendees()->count() }}/{{ $discussion->max_attendees }}</td>
</tr>
