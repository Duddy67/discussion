
@if (auth()->check() && $discussion->getTimeBeforeDiscussion())
    <div class="text-center"><button class="btn btn-success" id="register" type="button">Register</button></div>
@endif
