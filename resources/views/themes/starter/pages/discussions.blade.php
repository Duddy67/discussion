<div><h5>{{ \Carbon\Carbon::parse($daypicker, $page['timezone'])->isoFormat('Do MMM YY') }}</h5></div>

@if (!count($discussions))
    <div>No discussion</div>
@else
    <table class="table table-striped">
        <thead>
          <tr>
            <th scope="col">Discussion</th>
            <th scope="col">Theme</th>
            <th scope="col">Time</th>
            <th scope="col">Organizer</th>
            <th scope="col">Attendees</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($discussions as $discussion)
            @include ('themes.starter.partials.discussion')
        @endforeach
        </tbody>
    </table>
@endif
